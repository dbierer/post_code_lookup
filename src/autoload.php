<?php
spl_autoload_register(function ($class) {
    $path = __DIR__ . '/../src/';
    $path .= str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path .= '.php';
    require_once($path);
});
