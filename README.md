Zebar-PHP-ArrayGroupBy
=================
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
    'price' => function($value_array){
            return array_sum($value_array);
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