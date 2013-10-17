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
 * PHPUnit_Extensions_Selenium2TestCase analogues.
 * 
 */
class Commands2{
    
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

    public function open($target) {
        return "{$this->_obj}->url('$target');";
    }

    public function type($selector, $value) {
        $lines = array();
        $lines[] = '$input = ' . $this->_byQuery($selector);
        $lines[] = '$input->value("' . $value . '");';
        return $lines;
    }

    protected function _byQuery($selector) {
        if (preg_match('/^\/\/(.+)/', $selector)) {
            /* "//a[contains(@href, '?logout')]" */
            return $this->byXPath($selector);
        } else if (preg_match('/^([a-z]+)=(.+)/', $selector, $match)) {
            /* "id=login_name" */
            switch ($match[1]) {
                case 'id':
                    return $this->byId($match[2]);
                    break;
                case 'name':
                    return $this->byName($match[2]);
                    break;
                case 'link':
                    return $this->byLinkText($match[2]);
                    break;
                case 'xpath':
                    return $this->byXPath($match[2]);
                    break;
                case 'css':
                    $cssSelector = str_replace('..', '.', $match[2]);
                    return $this->byCssSelector($cssSelector);
                    break;
            }
        }
        throw new \Exception("Unknown selector '$selector'");
    }

    public function click($selector) {
        $lines = array();
        $lines[] = '$input = ' . $this->_byQuery($selector);
        $lines[] = '$input->click();';
        return $lines;
    }

    public function select($selectSelector, $optionSelector) {
        $lines = array();
        $lines[] = '$element = ' . $this->_byQuery($selectSelector);
        $lines[] = '$selectElement = ' . $this->_obj . '->select($element);';

        if (preg_match('/label=(.+)/', $optionSelector, $match)) {
            $lines[] = '$selectElement->selectOptionByLabel("' . $match[1] . '");';
        } else if (preg_match('/value=(.+)/', $optionSelector, $match)) {
            $lines[] = '$selectElement->selectOptionByValue("' . $match[1] . '");';
        } else {
            throw new \Exception("Unknown option selector '$optionSelector'");
        }

        return $lines;
    }
    
    /**
     * 
     * @param string $target
     * @return array
     */
    public function clickAndWait($target) {
        return $this->click($target);
    }

    /**
     * 
     * @param string $target
     * @param string $value
     * @return string
     */
    public function assertText($target, $value) {
        $lines = array();
        $lines[] = '$input = ' . $this->_byQuery($target);
        $lines[] = "{$this->_obj}->assertEquals('$value', \$input->text());";
        return $lines;
    }

    /**
     * 
     * @param string $target
     * @return string
     */
    public function assertElementPresent($target) {
        $lines = array();
        $lines[] = 'try {';
        $lines[] = "    " . $this->_byQuery($target);
        $lines[] = "    {$this->_obj}->assertTrue(true, true);";
        $lines[] = '} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {}';
        return $lines;
    }

    /**
     * 
     * @param string $target
     * @return string
     */
    public function assertElementNotPresent($target) {
        $lines = array();
        $lines[] = 'try {';
        $lines[] = "    " . $this->_byQuery($target);
        $lines[] = '} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
        $lines[] = "    {$this->_obj}->assertEquals(PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement, \$e->getCode());";
        $lines[] = '}';
        return $lines;
    }

    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForElementPresent($target) {
        $localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target));
        $lines = array();
        $lines[] = $this->_obj . '->waitUntil(function($testCase) {';
        $lines[] = '    try {';
        $lines[] = "        $localExpression";
        $lines[] = "        return true;";
        $lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {}';
        $lines[] = '}, 8000);';
        return $lines;
    }
    
    public function waitForElementNotPresent($target) {
        $localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target));
        $lines = array();
        $lines[] = $this->_obj . '->waitUntil(function($testCase) {';
        $lines[] = "    try {";
        $lines[] = "        $localExpression";
        $lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
        $lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
        $lines[] = "            return true;";
        $lines[] = "        }";
        $lines[] = '    }';
        $lines[] = '}, 8000);';
        return $lines;
    }

    /**
     * 
     * @param string $target
     * @return array
     */
    public function waitForTextPresent($text) {

        $lines = array();
        $lines[] = $this->_obj . '->waitUntil(function($testCase) {';
        $lines[] = "    if (strpos(\$testCase->byTag('body')->text(), '$text') !== false) {";
        $lines[] = "         return true;";
        $lines[] = '    }';
        $lines[] = '}, 8000);';
        return $lines;
    }

    /**
     * 
     * @param string $expression
     * @return array
     */
    protected function _waitWrapper($expression) {
        $localExpression = str_replace($this->_obj, '$testCase', $expression);
        $lines = array();
        $lines[] = $this->_obj . '->waitUntil(function($testCase) {';
        $lines[] = '    try {';
        $lines[] = "        $localExpression";
        $lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {}';
        $lines[] = '}, 8000);';
        return $lines;
    }

    /**
     * 
     * @param string $expression
     * @return string
     */
    protected function _assertFalse($expression) {
        return "{$this->_obj}->assertFalse($expression);";
    }

    /**
     * 
     * @param string $expression
     * @return string
     */
    protected function _assertTrue($expression) {
        return "{$this->_obj}->assertTrue($expression);";
    }

    protected function _assertPattern($target, $string) {
        $target = str_replace("?", "[\s\S]", $target);
        $expression = "(bool)preg_match('/^$target$/', " . $string . ")";
        return $expression;
    }
    
    protected function _isTextPresent($text) {
        return "(bool)(strpos({$this->_obj}->byTag('body')->text(), '$text') !== false)";
    }

    public function assertTextPresent($target) {
        return $this->_assertTrue($this->_isTextPresent($target));
    }

    public function assertTextNotPresent($target) {
        return $this->_assertFalse($this->_isTextPresent($target));
    }

}
