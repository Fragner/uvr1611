<?php

$path = dirname( dirname(__FILE__) ); // /path/to/folder/../lib
// This will append whichever path you would like to the current include path
// PHP is smart enough to convert / with \ if on a Windows box
// If not you can replace / with DIRECTORY_SEPARATOR

set_include_path(get_include_path() . PATH_SEPARATOR . $path);
if (PHP_SAPI === 'cli'){
    echo $path."\n";
}
?>