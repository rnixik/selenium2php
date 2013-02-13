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
 * Handles CLI commands.
 */
class CliController {
    
    protected $_converter;
    
    protected $_htmlPattern = "*.html";
    protected $_recursive = false;
    protected $_phpFilePrefix = '';
    protected $_phpFilePostfix = 'Test';
    protected $_destFolder = '';
    protected $_sourceBaseDir = '';

    public function __construct() {
        require_once 'Converter.php';
        $this->_converter = new Converter;
    }
    
    protected function _printTitle() {
        print "Selenium2php converts Selenium HTML tests into PHPUnit test case code.";
        print "\n";
        print "\n";
    }
    
    protected function _printHelp() {
        print "Usage: selenium2php [switches] Test.html [Test.php]";
        print "\n";
        print "       selenium2php [switches] <directory>";
        print "\n";
        print "\n";      
        print "  --dest=<path>                  Destination folder.\n";
        print "  --php-prefix=<string>          Add prefix to php filenames.\n";
        print "  --php-postfix=<string>         Add postfix to php filenames.\n";
        print "  --browser=<browsers string>    Set browser for tests.\n";
        print "  --browser-url=<url>            Set URL for tests.\n";
        print "  --remote-host=<host>           Set Selenium server address for tests.\n";
        print "  --remote-port=<port>           Set Selenium server port for tests.\n";
        print "  -r|--recursive                 Use subdirectories for converting.\n";
        print "  --class-prefix=<prefix>        Set TestCase class prefix.\n";
    }
    
    protected function _applyOptionsAndFlags($options, $flags){
        if (is_array($options)){
            foreach ($options as $opt){
                if (is_string($opt)){
                    switch ($opt){
                        case 'recursive':
                            $this->_recursive = true;
                            break;
                        default:
                            print "Unknown option \"$opt\".\n";
                            exit(1);
                    }
                } else if (is_array($opt)){
                    switch ($opt[0]){
                        case 'php-prefix':
                            $this->_phpFilePrefix = $opt[1];
                            break;
                        case 'php-postfix':
                            $this->_phpFilePostfix = $opt[1];
                            break;
                        case 'browser':
                            $this->_converter->setBrowser($opt[1]);
                            break;
                        case 'browser-url':
                            $this->_converter->setTestUrl($opt[1]);
                            break;
                        case 'remote-host':
                            $this->_converter->setRemoteHost($opt[1]);
                            break;
                        case 'remote-port':
                            $this->_converter->setRemotePort($opt[1]);
                            break;
                        case 'dest':
                            $this->_destFolder = $opt[1];
                            break;
                        case 'class-prefix':
                            $this->_converter->setTplClassPrefix($opt[1]);
                            break;
                        default:
                            print "Unknown option \"{$opt[0]}\".\n";
                            exit(1);
                    }
                }
            }
        }
        
        if (is_array($flags)){
            foreach ($flags as $flag){
                switch($flag){
                    case 'r':
                        $this->_recursive = true;
                        break;
                    default:
                        print "Unknown flag \"$flag\".\n";
                        exit(1);
                }
            }
        }
    }
    
    public function run($arguments, $options, $flags) {
        $this->_printTitle();
        $this->_applyOptionsAndFlags($options, $flags);
        if (empty($arguments)) {
            $this->_printHelp();
        } else if (!empty($arguments)) {
            $first = array_shift($arguments);
            $second = array_shift($arguments);
            if ($first && is_string($first)) {
                if (is_file($first)) {
                    $htmlFileName = $first;
                    if (is_readable($htmlFileName)) {
                        if ($second && is_string($second)) {
                            $phpFileName = $second;
                        } else {
                            $phpFileName = '';
                        }
                        $this->_sourceBaseDir = rtrim(dirname($htmlFileName), "\\/")."/";
                        $this->convertFile($htmlFileName, $phpFileName);
                        print "OK.\n";
                        exit(0);
                    } else {
                        print "Cannot open file \"$htmlFileName\".\n";
                        exit(1);
                    }
                } else if (is_dir($first)) {
                    $dir = rtrim($first, "\\/")."/";
                    $this->_sourceBaseDir = $dir;
                    $this->convertFilesInDirectory($dir);
                } else {
                    print "\"$first\" is not existing file or directory.\n";
                    exit(1);
                }
            }
        }
    }
    
    protected function convertFilesInDirectory($dir){
        if ($this->_recursive){
            $files = $this->globRecursive($dir . $this->_htmlPattern, GLOB_NOSORT);
        } else {
            $files = glob($dir . $this->_htmlPattern, GLOB_NOSORT);
        }
        if (count($files)){
            foreach ($files as $htmlFile){
                $this->convertFile($htmlFile);
            }
        } else {
            print "Files \"{$this->_htmlPattern}\" not found in \"$dir\".";
        }
    }
    
    public function convertFile($htmlFileName, $phpFileName = '') {
        $htmlContent = file_get_contents($htmlFileName);
        if ($htmlContent) {
            $result = $this->_converter->convert($htmlContent);
            if (!$phpFileName) {
                $fileName = basename($htmlFileName);
                
                if ($this->_destFolder){
                    $filePath = rtrim($this->_destFolder, "\\/") . "/";
                    if (!realpath($filePath)){
                        //path is not absolute
                        $filePath = $this->_sourceBaseDir . $filePath;
                        if (!realpath($filePath)){
                            print "Directory \"$filePath\" not found.\n";
                            exit(1);
                        }
                    }
                } else {
                    $filePath = dirname($htmlFileName) . "/";
                }
                
                $phpFileName = $filePath . $this->_phpFilePrefix 
                        . preg_replace("/\.html$/", '', $fileName) 
                        . $this->_phpFilePostfix . ".php";
            }
            file_put_contents($phpFileName, $result);
            print $phpFileName."\n";
        }
    }
    
    protected function globRecursive($pattern, $flags) {

        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->globRecursive($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }
}