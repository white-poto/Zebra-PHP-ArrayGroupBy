Zebar-PHP-ArrayGroupBy
=================
[![Build Status](https://img.shields.io/travis/huyanping/Zebra-PHP-ArrayGroupBy/master.svg?style=flat)](https://travis-ci.org/huyanping/Zebra-PHP-ArrayGroupBy)
[![Latest Stable Version](http://img.shields.io/packagist/v/jenner/array_group_by.svg?style=flat)](https://packagist.org/packages/jenner/array_group_by)
[![Total Downloads](https://img.shields.io/packagist/dt/jenner/array_group_by.svg?style=flat)](https://packagist.org/packages/jenner/array_group_by)
[![License](https://img.shields.io/packagist/l/jenner/array_group_by.svg?style=flat)](https://packagist.org/packages/jenner/array_group_by)
为什么使用Zebra-PHP-ArrayGroupBy
----------------------
在如下场景中，我们总是希望能够在php中使用类似mysql的groupby操作：
+ SQL过于复杂，造成数据库运算效率低下
+ 从数据库中读取出原始数据，在php中进行运算，增强代码重用率
+ 其他非数据库场景的数组归并场景

Zebar-PHP-ArrayGroupBy能够做什么
----------------------
+ 对二维数组进行归并
+ 归并的同时，支持对字段进行自定义处理
+ 比SQL更灵活的自定义函数，你可以随意编写归并和字段合并函数

示例：
```php
$records = [
    ['order_date' => '2014-01-01', 'price' => 5],
    ['order_date' => '2014-01-02', 'price' => 10],
    ['order_date' => '2014-01-03', 'price' => 20],
    ['order_date' => '2015-01-04', 'price' => 25],
];

$group_by_fields = [
    'order_date' => function($value){
            return date('Y', strtotime($value));
        }
];

$group_by_value = [
    'order_date' => [
        'callback' => function($value_array){
                return substr($value_array[0], 0, 4);
            },
        'as' => 'year'
    ],
    'price' => function($data){
            return array_sum(array_column($data, 'price'));
        },
];

$grouped = \Jenner\Zebra\ArrayGroupBy::groupBy($records, $group_by_fields, $group_by_value);
print_r($grouped);
```

结果：
```php
Array
(
    [0] => Array
        (
            [year] => 2014
            [price] => 35
        )

    [1] => Array
        (
            [year] => 2015
            [price] => 25
        )

)
```

举例
+ 归并过程中，实现对结果的中值计算
+ 归并过程中，对时间字段进行自定义处理，例如归并每5分钟的数据
+ 等等


链式调用
-----------------------
```php
$records = [
    ['bill_time'=>'2014-01-01 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-01 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-01 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-02 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-02 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-03 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-03 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-03 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-04 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-04 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-04 00:00:00', 'price'=>1, 'cnt'=>3,],
    ['bill_time'=>'2014-01-04 00:00:00', 'price'=>1, 'cnt'=>3,],
];

$group_by_fields = [
    'bill_time' => function($field){
            return substr($field, 0, 10);
        }
];

$group_by_values = [
    'bill_time' => function($field_values){
            return substr($field_values[0], 0, 10);
        },
    'price' => function($field_values){
            return array_sum($field_values);
        },
    'cnt' => function($field_values){
            return array_sum($field_values);
        }
];

$week_fields = [
    'bill_time' => function($field){
            return date('w', strtotime($field));
        }
];

$week_values = [
    'bill_time' => function($data){
            return date('w', strtotime($data[0]['bill_time']));
        },
    'price' => function($data){
            return array_sum(array_column($data, 'price'));
        },
    'cnt' => function($data){
            return array_sum(array_column($data, 'cnt'));
        }
];

$grouped = (new \Jenner\Zebra\ArrayGroupBy($records))->groupByField($group_by_fields)->groupByValue($group_by_values)->groupByField($week_fields)->groupByValue($week_values)->get();
print_r($grouped);
```
