<?php

namespace IvanFilho\Database;

/**
 * Class Exceptions
 * 
 * A simple debug class.
 * 
 * @package      Database
 * @subpackage   src
 * @author       Ivan Filho <ivanfilho21@gmail.com>
 * 
 * Created: Jan 16, 2020.
 * Last Modified: Jan 19, 2020.
 *
 */
class Exceptions
{
    /**
     * Show the error message, method and file in which the error was triggered.
     * 
     * @param object $class
     * @param string $method
     * @param string $message
     * @param integer $type
     * 
     * @return void
     */
    public static function triggerError(object $class, string $method, string $message, int $type = E_USER_WARNING)
    {
        $m = explode('\\', $method);
        $method = count($m) > 0 ? $m[count($m) -1] : $method;
        $class = get_class($class);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        // echo '<pre>' .var_export($trace[0], true) .'</pre>';
        
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $parentMethod = (isset($trace[0]['args']['0'])) ? ' by ' .$trace[0]['args']['0'] .'()' : '';
        
        echo "<b>Fatal error</b>: $message in <b>$method()</b> of file <b>$file</b> on line <b>$line</b>.";

        if ($type == E_USER_ERROR) exit();
    }
}