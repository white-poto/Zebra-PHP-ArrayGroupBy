<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 15-1-21
 * Time: 下午10:07
 */

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$records = array(
    array(
        "state" => "IN",
        "city" => "Indianapolis",
        "object" => "School bus"
    ),
    array(
        "state" => "IN",
        "city" => "Indianapolis",
        "object" => "Manhole"
    ),
    array(
        "state" => "IN",
        "city" => "Plainfield",
        "object" => "Basketball"
    ),
    array(
        "state" => "CA",
        "city" => "San Diego",
        "object" => "Light bulb"
    ),
    array(
        "state" => "CA",
        "city" => "Mountain View",
        "object" => "Space pen"
    )
);

$group_by_fields = [
    'state' => function($value){
            return substr($value, 0, 1);
        }
];

$group_by_value = [
    'state',
    'object',
    'city' => function($value){
            return count($value);
        }
];

$group_by_fields_2 = [
    'state' => function($value){
            return 1;
        }
];

$group_by_value_2 = [
    'state',
    'object',
    'city' => function($value_array){
            return count($value_array);
        },
];

$grouped = (new \Jenner\Zebra\ArrayGroupBy($records))->groupByField($group_by_fields)->groupByValue($group_by_value)->groupByField($group_by_fields_2)->groupByValue($group_by_value_2)->get();

print_r($grouped);
