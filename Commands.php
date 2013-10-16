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
 * Provides formatting some special commands into
 * PHPUnit_Extensions_SeleniumTestCase analogues.
 * 
 * Example:
 * $this->waitForElementPresent("css=div.route-view > span")
 * converts into:
 * 
 * for ($second = 0;; $second++) {
 *     if ($second >= 60) $this->fail("timeout");
 *           try {
 *               if ($this->isElementPresent("css=div.route-view > span")) break;
 *           } catch (Exception $e) {}
 *           sleep(1);
 *      }
 * 
 * 
 * Magic method __call leaves unmentioned commads as is.
 */
class Commands {
    
    protected $_obj = '$this';
    
    /**
     * 
     * @param string $name
     * @param string $arguments
     * @return string
     */
    public function __call($name, $arguments) {
        if (isset($arguments[1]) && false !== $arguments[1]){
            $line = "{$this->_obj}->$name(\"{$arguments[0]}\", \"{$arguments[1]}\");";
        } else if (false !== $arguments[0]) {
            $line = "{$this->_obj}->$name(\"{$arguments[0]}\");";
        } else {
            $line = "{$this->_obj}->$name();";
        }
        return $line;
    }
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function clickAndWait($target) {
        $lines = array();
        $lines[] = "{$this->_obj}->click(\"$target\");";
        $lines[] = "{$this->_obj}->waitForPageToLoad(\"30000\");";
        return $lines;
    }
    
    /**
     * 
     * @param string $target
     * @param string $value
     * @return string
     */
    public function assertText($target, $value){
        return "{$this->_obj}->assertEquals(\"$value\", {$this->_obj}->getText(\"$target\"));";
    }
    
    /**
     * 
     * @param string $target
     * @return string
     */
    public function assertElementPresent($target){
        return $this->_assertTrue("{$this->_obj}->isElementPresent(\"$target\")");
    }
    
    /**
     * 
     * @param string $target
     * @return string
     */
    public function assertElementNotPresent($target){
        return $this->_assertFalse("{$this->_obj}->isElementPresent(\"$target\")");
    }
    
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForElementPresent($target){   
        return $this->_waitWrapper("{$this->_obj}->isElementPresent(\"$target\")");
    }
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForElementNotPresent($target){   
        return $this->_waitWrapper("!{$this->_obj}->isElementPresent(\"$target\")");
    }
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForTextPresent($target){
        return $this->_waitWrapper("{$this->_obj}->isTextPresent(\"$target\")");
    }
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForTextNotPresent($target){
        return $this->_waitWrapper("!{$this->_obj}->isTextPresent(\"$target\")");
    }
    
    /**
     * 
     * @param string $expression
     * @return array
     */
    protected function _waitWrapper($expression){
        $lines = array();
        $lines[] = 'for ($second = 0; ; $second++) {';
        $lines[] = '    if ($second >= 60) '.$this->_obj.'->fail("timeout");'; 
        $lines[] = '    try {';        
        $lines[] = "        if ($expression) break;";  
        $lines[] = '    } catch (Exception $e) {}';     
        $lines[] = '    sleep(1);';       
        $lines[] = '}';    
        return $lines;
    }
    
    /**
     * 
     * @param string $expression
     * @return string
     */
    protected function _assertFalse($expression){
        return "{$this->_obj}->assertFalse($expression);";
    }
    
    /**
     * 
     * @param string $expression
     * @return string
     */
    protected function _assertTrue($expression){
        return "{$this->_obj}->assertTrue($expression);";
    }
    
    protected function _assertPattern($target, $string){
        $target = str_replace("?", "[\s\S]", $target);
        $expression = "(bool)preg_match('/^$target$/', " . $string . ")";
        return $expression;
    }
    
    /**
     * 
     * @param string $target
     * @return string
     */
    public function assertConfirmation($target){
        $target = str_replace("?", "[\s\S]", $target);
        $expression = $this->_assertPattern($target, '$this->getConfirmation()');
        return $this->_assertTrue($expression);
    }
    
    /**
     * 
     * @return array
     */
    public function verifyConfirmation($target) {
        $expression = $this->_assertTrue($this->_assertPattern($target, '$this->getConfirmation()'));
        $lines = array();
        $lines[] = 'try {';
        $lines[] = '    '.$expression;
        $lines[] = '} catch (PHPUnit_Framework_AssertionFailedError $e) {';
        $lines[] = '    array_push($this->verificationErrors, $e->toString());';
        $lines[] = '}';
        return $lines;
    }
    
    public function assertTextPresent($target){
        return $this->_assertTrue("{$this->_obj}->isTextPresent(\"$target\")");
    }
    
    public function assertTextNotPresent($target){
        return $this->_assertFalse("{$this->_obj}->isTextPresent(\"$target\")");
    }
}
