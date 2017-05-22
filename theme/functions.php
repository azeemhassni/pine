<?php

require_once __DIR__.'/vendor/autoload.php';

/*
 * Die and Dump method
 */
if (!function_exists('dd')) {
    function dd()
    {
        echo '<pre>';
        array_map(function ($data) {
            print_r($data);
        }, func_get_args());
        echo '</pre>';
        die;
    }
}
