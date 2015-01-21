<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 15-1-21
 * Time: 下午3:30
 */
namespace Jenner\Zebra;

class ArrayGroupBy
{

    /**
     * SQL的GROUP BY模拟方法
     * @param $array 需要执行GROUP BY的二维数组
     * @param $group_field group by 方法，
     * 示例：['field_name' => function($field){ return strlen($field); }]
     * 只要回调方法返回的值相同，就会执行group操作
     * @param $value_field 对value的方法，例如sum，average等，回调方法可以自定义
     * 示例：['field_name' => function(array $field_values) { return count($field_value); }, 'field_name_2' => function(array $field_values) { return array_sum($field_values) } ]
     * @return array
     */
    static function groupBy($array, $group_field, $value_field)
    {
        $group_by_field_params[] = $array;
        foreach ($group_field as $key => $value) {
            if (is_callable($value)) {
                $group_by_field_params[] = $key;
                $group_by_field_params[] = $value;
            } else {
                $group_by_field_params[] = $value;
                $group_by_field_params[] = null;
            }
        }

        $grouped = call_user_func_array('\\Jenner\\Zebra\\ArrayGroupBy::groupByField', $group_by_field_params);
        $deepest_array = self::getDeepestArray($grouped);

        return self::groupByValue($deepest_array, $value_field);
    }

    /**
     * Groups an array by a given key. Any additional keys will be used for grouping
     * the next set of sub-arrays.
     *
     * @author Jake Zatecky
     *
     * @param array $arr The array to have grouping performed on.
     * @param mixed $key The key to group or split by.
     *
     * @param null $callback
     * @return array
     */
    static function groupByField($arr, $key, $callback = null)
    {
        if (!is_array($arr)) {
            trigger_error("\\Jenner\\Zebra\\ArrayGroupBy::groupByField(): The first argument should be an array", E_USER_ERROR);
        }
        if (!is_string($key) && !is_int($key) && !is_float($key)) {
            trigger_error("\\Jenner\\Zebra\\ArrayGroupBy::array_group_by(): The key should be a string or an integer", E_USER_ERROR);
        }
        // Load the new array, splitting by the target key
        $grouped = array();
        foreach ($arr as $value) {
            if (!is_null($callback) && is_callable($callback)) {
                $grouped_key = call_user_func($callback, $value[$key]);
            } else {
                $grouped_key = $value[$key];
            }
            $grouped[$grouped_key][] = $value;
        }
        // Recursively build a nested grouping if more parameters are supplied
        // Each grouped array value is grouped according to the next sequential key
        if (func_num_args() > 3) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $params = array_merge(array($value), array_slice($args, 3, func_num_args()));
                $grouped[$key] = call_user_func_array('\Jenner\Zebra\ArrayGroupBy::groupByField', $params);
            }
        }
        return $grouped;
    }

    /**
     * 根据groupby的结果，对
     * @param $grouped_arr
     * @param $callbacks ['field_name'=>function(){}, 'field_name'=>['callback'=>function(){}, 'as'=>'as_name']]
     * @return array
     */
    static function groupByValue($grouped_arr, $callbacks)
    {
        $result = [];
        $count = count($grouped_arr);
        for ($i = 0; $i < $count; $i++) {
            $result[$i] = [];
            foreach($callbacks as $field_name=>$field_config){
                //支持'field_name'=>callback配置
                if(is_callable($field_config)){
                    $callback = $field_config;
                    $result[$i][$field_name] = call_user_func($callback, self::arrayColumn($grouped_arr[$i], $field_name));
                }//支持'field_name'=>['callback'=>callback, 'as'=>'as_name']配置
                elseif(is_array($field_config)){
                    if(isset($field_config['callback']) && is_callable($field_config['callback'])){
                        $callback = $field_config['callback'];
                        $field_value = call_user_func($callback, self::arrayColumn($grouped_arr[$i], $field_name));
                    }else{
                        $field_value = $grouped_arr[$i][0][$field_name];
                    }

                    if(isset($field_config['as']) && !empty($field_config['as'])){
                        $result[$i][$field_config['as']] = $field_value;
                    }else{
                        $result[$i][$field_name] = $field_value;
                    }
                }//支持字符串配置
                else{
                    $result[$i][$field_name] = $grouped_arr[$i][0][$field_config];
                }
            }
        }

        return $result;
    }

    /**
     * 获取多为数组最深的一层数组，并以二维数组的形式返回，
     * 会对key进行重组，统一转换成数字下标
     * @param $array
     * @return array
     */
    static function getDeepestArray($array)
    {
        $result = [];
        foreach ($array as $arr) {
            if (!is_array($arr)) {
                continue;
            } elseif (self::arrayDepth($arr) == 2) {
                $arr = [array_values($arr)];
                $result = array_merge(array_values($result), $arr);
            } else {
                $result = array_merge(array_values($result), call_user_func('\Jenner\Zebra\ArrayGroupBy::getDeepestArray', $arr));
            }
        }

        return $result;
    }

    /**
     * 求数组深度，一维数组为1，二维数组为2....
     * @param $array
     * @return int
     */
    static function arrayDepth($array)
    {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::arrayDepth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }
        return $max_depth;
    }

    /**
     * 将一个二维数组，以其中一列为KEY，一列为VALUE，返回一个一维数组
     * @param array $array
     * @param null $column_key
     * @param $index_key
     * @throws \Exception
     * @return array
     */
    public static function arrayColumn($array, $column_key, $index_key = null)
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess))
            throw new \Exception('Argument 1 passed to Jenner\Zebra\ArrayGroupBy::::arrayColumn() must be of the type array');

        if (function_exists('array_column ')) {
            return array_column($array, $column_key, $index_key);
        }

        $result = [];
        foreach ($array as $arr) {

            if (!is_array($arr) && !($arr instanceof \ArrayAccess)) continue;

            if (is_null($column_key)) {
                $value = $arr;
            } else {
                $value = $arr[$column_key];
            }

            if (!is_null($index_key)) {
                $key = $arr[$index_key];
                $result[$key] = $value;
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }
} 