<?php
/*
 * Copyright 2013 Rnix Valentine
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Selenium2php;

/**
 * Converts HTML text of Selenium test case recorded from Selenium IDE into
 * PHP code for PHPUnit_Extensions_SeleniumTestCase as TestCase file.
 */
class Converter {
    
    protected $_testName = '';
    protected $_testUrl = '';
    
    protected $_defaultTestName = 'some';
    protected $_defaultTestUrl = 'http://example.com';
    
    protected $_selenium2 = false;
    
    protected $_commands = array();
    
    protected $_tplEOL = PHP_EOL;
    protected $_tplCommandEOL = '';
    protected $_tplFirstLine = '<?php';
    
    /**
     * Array of strings with text before class defenition
     * @var array 
     */
    protected $_tplPreClass = array();
    protected $_tplClassPrefix = '';
    protected $_tplParentClass = 'PHPUnit_Extensions_SeleniumTestCase';
    
    /**
     * Array of strings with some methods in class
     * @var array
     */
    protected $_tplAdditionalClassContent = array();
    
    protected $_browser = '*firefox';
    
    /**
     * Address of Selenium Server
     * @var string
     */
    protected $_remoteHost = '';
    
    /**
     * Port of Selenium Server
     * @var type 
     */
    protected $_remotePort = '';
    
    /**
     *
     * @var string 
     */
    protected $_tplCustomParam1 = '';
    
    /**
     *
     * @var string
     */
    protected $_tplCustomParam2 = '';

    /**
     * Parses HTML string into array of commands, 
     * determines testHost and testName. 
     * 
     * @param string $htmlStr
     * @throws \Exception
     */
    protected function _parseHtml($htmlStr){
        require_once 'libs/simple_html_dom.php';
        $html = str_get_html($htmlStr);
        if ($html && $html->find('link')){
            
            if (!$this->_testUrl){
                $this->_testUrl = $html->find('link', 0)->href;
            }
            if (!$this->_testName) {
                $title = $html->find('title', 0)->innertext;
                $this->_testName = preg_replace('/[^A-Za-z0-9]/', '_', ucwords($title));
            }
            
            foreach ($html->find('table tr') as $row) {
                if ($row->find('td', 2)) {
                    $command = $row->find('td', 0)->innertext;
                    $target = $row->find('td', 1)->innertext;
                    $value = $row->find('td', 2)->innertext;

                    $this->_commands[] = array(
                        'command' => $command,
                        'target' => $target,
                        'value' => $value
                    );
                }
            }
            
        } else {
            throw new \Exception("HTML parse error");
        }
    }    
    
    /**
     * Converts HTML text of Selenium test case into PHP code
     * 
     * @param string $htmlStr content of html file with Selenium test case
     * @param string $testName test class name (leave blank for auto)
     * @return string PHP test case file content
     */
    public function convert($htmlStr, $testName = '', $tplFile = ''){
        $this->_testName = $testName;
        $this->_commands = array();
        $this->_parseHtml($htmlStr);
        if ($tplFile){
            if (is_file($tplFile)){
                return $this->_convertToTpl($tplFile);
            } else {
                echo "Template file $tplFile is not accessible.";
                exit;
            }
        } else {
            $lines = $this->_composeLines();
            return $this->_composeStr($lines);
        }
    }
        
    /**
     * Implodes lines of file into one string
     * 
     * @param array $lines
     * @return string
     */
    protected function _composeStr($lines){
        return implode($this->_tplEOL, $lines);
    }
    
    /**
     * Adds indents to each line except first
     * and implodes lines into one string
     * 
     * @param array $lines array of strings
     * @param int $indentSize
     * @return string
     */
    protected function _composeStrWithIndents($lines, $indentSize){
        foreach ($lines as $i=>$line){
            if ($i != 0){
                $lines[$i] = $this->_indent($indentSize) . $line;
            }
        }
        return $this->_composeStr($lines);
    }
    
    /**
     * Uses tpl file for output result.
     * 
     * @param string $tplFile filepath
     * @return string output content
     */
    protected function _convertToTpl($tplFile){
        $tpl = file_get_contents($tplFile);
        $replacements = array(
            '{$comment}' => $this->_composeComment(),
            '{$className}' => $this->_composeClassName(),
            '{$browser}' => $this->_browser,
            '{$testUrl}' => $this->_testUrl ? $this->_testUrl : $this->_defaultTestUrl,
            '{$remoteHost}' => $this->_remoteHost ? $this->_remoteHost : '127.0.0.1',
            '{$remotePort}' => $this->_remotePort ? $this->_remotePort : '4444',
            '{$testMethodName}' => $this->_composeTestMethodName(),
            '{$testMethodContent}' => $this->_composeStrWithIndents($this->_composeTestMethodContent(), 8),
            '{$customParam1}' => $this->_tplCustomParam1,
            '{$customParam2}' => $this->_tplCustomParam2,
        );
        foreach ($replacements as $s=>$r){
            $tpl = str_replace($s, $r, $tpl);
        }
        return $tpl;
    }
    
    protected function _composeLines() {
        $lines = array();

        $lines[] = $this->_tplFirstLine;
        $lines[] = $this->_composeComment();
        
        if (count($this->_tplPreClass)) {
            $lines[] = "";
            foreach ($this->_tplPreClass as $mLine) {
                $lines[] =  $mLine;
            }
            $lines[] = "";
        }
        
        $lines[] = "class " . $this->_composeClassName() . " extends " . $this->_tplParentClass . "{";
        $lines[] = "";
        
        if (count($this->_tplAdditionalClassContent)) {
            foreach ($this->_tplAdditionalClassContent as $mLine) {
                $lines[] = $this->_indent(4) . $mLine;
            }
            $lines[] = "";
        }
        
        
        $lines[] = $this->_indent(4) . "function setUp(){";
        foreach ($this->_composeSetupMethodContent() as $mLine){
            $lines[] = $this->_indent(8) . $mLine;
        }
        $lines[] = $this->_indent(4) . "}";
        $lines[] = "";
        
        
        $lines[] = $this->_indent(4) . "function " . $this->_composeTestMethodName() . "(){";
        foreach ($this->_composeTestMethodContent() as $mLine){
            $lines[] = $this->_indent(8) . $mLine;
        }
        $lines[] = $this->_indent(4) . "}";
        $lines[] = "";
        
        
        $lines[] = "}";
        
        return $lines;
    }
    
    protected function _indent($size){
        return str_repeat(" ", $size);
    }
    
    protected function _composeClassName(){
        return $this->_tplClassPrefix . $this->_testName . "Test";
    }
    
    protected function _composeTestMethodName(){
        return "test" . $this->_testName;
    }
    
    protected function _composeSetupMethodContent(){
        $mLines = array();
        $mLines[] = '$this->setBrowser("' . $this->_browser . '");';
        if ($this->_testUrl){
            $mLines[] = '$this->setBrowserUrl("' . $this->_testUrl . '");';
        } else{
            $mLines[] = '$this->setBrowserUrl("' . $this->_defaultTestUrl . '");';
        }
        if ($this->_remoteHost) {
            $mLines[] = '$this->setHost("' . $this->_remoteHost . '");';
        }
        if ($this->_remotePort) {
            $mLines[] = '$this->setPort("' . $this->_remotePort . '");';
        }
        return $mLines;
    }
    
    protected function _composeTestMethodContent(){
        if ($this->_selenium2){
            require_once 'Commands2.php';
            $commands = new Commands2;
        } else {
            require_once 'Commands.php';
            $commands = new Commands;
        }
        
        $mLines = array();
        
        
        foreach ($this->_commands as $row){
            $command = $row['command'];
            $target  = $this->_prepareHtml($row['target']);
            $value   = $this->_prepareHtml($row['value']);
            $res = $commands->$command($target, $value);
            if (is_string($res)){
                if ($this->_tplCommandEOL !== ''){
                    $res .= $this->_tplCommandEOL;
                }
                $mLines[] = $res;
            } else if (is_array($res)){
                $size = count($res);
                $i = 0;
                foreach ($res as $subLine){
                    $i++;
                    if ($size === $i && $this->_tplCommandEOL !== ''){
                        $subLine .= $this->_tplCommandEOL;
                    }
                    
                    $mLines[] = $subLine;
                }
            }
            
        }
        
        return $mLines;
    }
    
    protected function _prepareHtml($html){
        $res = $html;
        $res = str_replace('&nbsp;', ' ', $res);
        $res = html_entity_decode($res);
        $res = str_replace('<br />', '\n', $res);
        $res = str_replace('"', '\\"', $res);
        return $res;
    }
    
    protected function _composeComment(){
        $lines = array();
        $lines[] = "/*";
        $lines[] = "* Autogenerated from Selenium html test case by Selenium2php.";
        $lines[] = "* " . date("Y-m-d H:i:s");
        $lines[] = "*/";
        $line = implode($this->_tplEOL, $lines);
        return $line;
    }
    
    public function setTestUrl($testUrl){
        $this->_testUrl = $testUrl;
    }
    
    public function setRemoteHost($host){
        $this->_remoteHost = $host;
    }
    
    public function setRemotePort($port){
        $this->_remotePort = $port;
    }
    
    /**
     * Sets browser where test runs
     * 
     * @param string $browser example: *firefox 
     */
    public function setBrowser($browser){
        $this->_browser = $browser;
    }
    
    /**
     * Sets lines of text before test class defenition
     * @param string $text
     */
    public function setTplPreClass($linesOfText){
        $this->_tplPreClass = $linesOfText;
    }
    
    public function setTplEOL($tplEOL){
        $this->_tplEOL = $tplEOL;
    }
    
    /**
     * Sets lines of text into test class
     * 
     * @param array $content - array of strings with methods or properties
     */
    public function setTplAdditionalClassContent($linesOfText){
        $this->_tplAdditionalClassContent = $linesOfText;
    }
    
    /**
     * Sets name of class as parent for test class
     * Default: PHPUnit_Extensions_SeleniumTestCase
     * 
     * @param string $className
     */
    public function setTplParentClass($className){
        $this->_tplParentClass = $className;
    }
    
    public function setTplClassPrefix($prefix){
        $this->_tplClassPrefix = $prefix;
    }
    
    public function useSelenium2(){
        $this->_selenium2 = true;
        $this->setTplParentClass('PHPUnit_Extensions_Selenium2TestCase');
        $this->_tplCommandEOL = PHP_EOL;
    }
    
    /**
     * Passes value to template file
     * 
     * @param type $value
     */
    public function setTplCustomParam1($value){
        $this->_tplCustomParam1 = $value;
    }
    
    /**
     * Passes value to template file
     * 
     * @param type $value
     */
    public function setTplCustomParam2($value){
        $this->_tplCustomParam2 = $value;
    }
}