<?php

/**
 * @link http://php.net/manual/ru/features.commandline.php
 * @param type $args
 * @return type
 */
function arguments($args) {
    $ret = array(
        'exec' => '',
        'options' => array(),
        'flags' => array(),
        'arguments' => array(),
    );

    $ret['exec'] = array_shift($args);

    while (($arg = array_shift($args)) != NULL) {
        // Is it a option? (prefixed with --)
        if (substr($arg, 0, 2) === '--') {
            $option = substr($arg, 2);

            // is it the syntax '--option=argument'?
            if (strpos($option, '=') !== FALSE)
                array_push($ret['options'], explode('=', $option, 2));
            else
                array_push($ret['options'], $option);

            continue;
        }

        // Is it a flag or a serial of flags? (prefixed with -)
        if (substr($arg, 0, 1) === '-') {
            for ($i = 1; isset($arg[$i]); $i++)
                $ret['flags'][] = $arg[$i];

            continue;
        }

        // finally, it is not option, nor flag
        $ret['arguments'][] = $arg;
        continue;
    }
    return $ret;
}
