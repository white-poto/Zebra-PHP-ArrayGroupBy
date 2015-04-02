<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 15-4-2
 * Time: 下午2:21
 */

define('ZEBRA_GROUP_BY_ROOT', dirname(__FILE__));

spl_autoload_register(function($class_name){
    $class_name = str_replace ( '\\', '/', $class_name );
    require ZEBRA_GROUP_BY_ROOT . DIRECTORY_SEPARATOR . $class_name;
});
