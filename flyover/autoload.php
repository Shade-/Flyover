<?php

if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    throw new Exception('Flyover requires PHP version 7.1 or higher.');
}

spl_autoload_register(
    function ($class) {

        $prefix = 'Flyover\\';
        // base directory for the namespace prefix.
        $base_dir = __DIR__;   // By default, it points to this same folder.
                               // You may change this path if having trouble detecting the path to
                               // the source files.
        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader.
            return;
        }
        // get the relative class name.
        $relative_class = substr($class, $len);
        // replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = $base_dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $relative_class).'.php';
        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
);