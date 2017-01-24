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

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * get result
     *
     * @return array
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * like sql `count`
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * like mysql `limit`
     *
     * @param $start
     * @param $length
     */
    public function limit($start, $length)
    {
        $this->data = array_slice($this->data, $start, $length);
    }

    /**
     * like sql `group by`
     * example: $sorted = $obj->orderBy($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
     *
     * @return mixed
     */
    public function orderBy()
    {
        $args = \func_get_args();
        $data = $this->data;
        foreach ($args as $n => $field) {
            if (\is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        \call_user_func_array('array_multisort', $args);

        return \array_pop($args);
    }

    /**
     * group by based on fields
     *
     * @param $group_field
     * @return $this
     */
    public function groupByField($group_field)
    {
        $group_by_field_params[] = $this->data;
        foreach ($group_field as $key => $value) {
            if (is_callable($value)) {
                $group_by_field_params[] = $key;
                $group_by_field_params[] = $value;
            } else {
                $group_by_field_params[] = $value;
                $group_by_field_params[] = null;
            }
        }

        $grouped = call_user_func_array("\\Jenner\\Zebra\\ArrayGroupBy::groupByFieldDeep", $group_by_field_params);
        $this->data = self::getDeepestArray($grouped);

        return $this;
    }

    /**
     * group by field and return the final result
     *
     * @param $callbacks ['field_name'=>function(){}, 'field_name'=>['callback'=>function(){}, 'as'=>'as_name']]
     * @return $this
     */
    public function groupByValue($callbacks)
    {
        $grouped_arr = $this->data;
        $result = [];
        $count = count($grouped_arr);
        for ($i = 0; $i < $count; $i++) {
            $result[$i] = [];
            foreach ($callbacks as $field_name => $field_config) {
                //支持'field_name'=>callback配置
                if (is_callable($field_config)) {
                    $callback = $field_config;
                    $result[$i][$field_name] = call_user_func($callback, $grouped_arr[$i]);
                } //支持'field_name'=>['callback'=>callback, 'as'=>'as_name']配置
                elseif (is_array($field_config)) {
                    if (isset($field_config['callback']) && is_callable($field_config['callback'])) {
                        $callback = $field_config['callback'];
                        $field_value = call_user_func($callback, $grouped_arr[$i]);
                    } else {
                        $field_value = $grouped_arr[$i][0][$field_name];
                    }

                    if (isset($field_config['as']) && !empty($field_config['as'])) {
                        $result[$i][$field_config['as']] = $field_value;
                    } else {
                        $result[$i][$field_name] = $field_value;
                    }
                } // support string field
                else {
                    $result[$i][$field_config] = $grouped_arr[$i][0][$field_config];
                }
            }
        }
        $this->data = $result;

        return $this;
    }

    /**
     * not chain method
     *
     * @param $data
     * @param $group_field
     * @param $group_value
     * @return array
     */
    public static function groupBy($data, $group_field, $group_value)
    {
        return (new ArrayGroupBy($data))->groupByField($group_field)->groupByValue($group_value)->get();
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
     * @throws \Exception
     */
    public static function groupByFieldDeep($arr, $key, $callback = null)
    {
        if (!is_array($arr)) {
            $message = '\Jenner\Zebra\ArrayGroupBy::groupByFieldDeep(): The first argument should be an array';
            throw new \InvalidArgumentException($message);
        }
        if (!is_string($key) && !is_int($key) && !is_float($key)) {
            $message = '\Jenner\Zebra\ArrayGroupBy::groupByFieldDeep(): The key should be a string or an integer';
            throw new \InvalidArgumentException($message);
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
                $grouped[$key] = call_user_func_array("\\Jenner\\Zebra\\ArrayGroupBy::groupByFieldDeep", $params);
            }
        }
        return $grouped;
    }

    /**
     * @param $array
     * @return array
     */
    public static function getDeepestArray($array)
    {
        $result = [];
        foreach ($array as $arr) {
            if (!is_array($arr)) {
                continue;
            } elseif (self::arrayDepth($arr) == 2) {
                $arr = [array_values($arr)];
                $result = array_merge(array_values($result), $arr);
            } else {
                $sub_result = call_user_func("\\Jenner\\Zebra\\ArrayGroupBy::getDeepestArray", $arr);
                $result = array_merge(array_values($result), $sub_result);
            }
        }

        return $result;
    }

    /**
     * get array depth
     *
     * @param $array
     * @return int
     */
    public static function arrayDepth($array)
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
     * @param array $array
     * @param null $column_key
     * @param $index_key
     * @throws \Exception
     * @return array
     */
    public static function arrayColumn($array, $column_key, $index_key = null)
    {
        if (!is_array($array) && !($array instanceof \ArrayAccess))
            throw new \Exception('Argument 1 passed to \Jenner\Zebra\ArrayGroupBy::::arrayColumn() must be of the type array');

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