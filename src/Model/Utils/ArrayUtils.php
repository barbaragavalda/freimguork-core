<?php

namespace Core\Model\Utils;

class ArrayUtils {

    /**
     * replace keys
     * @param $items
     * @param string $fieldID
     * @param string|array $fieldName
     * @return array
     */
    public static function replaceKeys($items, $fieldID, $fieldName = null){
        foreach($items as &$item){
            $item['id'] = $item[$fieldID];
            if( $fieldName !== null ) {
                if (is_array($fieldName)) {
                    $name = '';
                    for ($i = 0; $i < count($fieldName); $i++) {
                        $key = $fieldName[$i];
                        $value = $item[$key];
                        if ($i != 0) $value = ' (' . $value . ')';
                        $name .= ' ' . $value;
                        if( $key != 'name' ) unset($item[$key]);
                    }
                    $item['name'] = $name;
                } else {
                    $item['name'] = $item[$fieldName];
                    if( $fieldName != 'name' ) unset($item[$fieldName]);
                }
            }
            if( $fieldID != 'name' ) unset($item[$fieldID]);
        }
        return $items;
    }

    /**
     * Sum two arrays: array(1, 1) + array(2, 4) = array(3, 5)
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function sum($a, $b){
        return array_map(function (...$arrays) {
            return array_sum($arrays);
        }, $a, $b);
    }

    /**
     * Sum two associative arrays: array('a'=>1, 'b'=>1) + array('a'=>2, 'b'=>4) =array('a'=>3, 'b'=>5)
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function sumAssoc($a , $b){
        $result = array_merge_recursive($a, $b);
        foreach($result as &$x) {
            if (is_array($x)) {
                $x = array_sum($x);
            }
        }
        return $result;
    }

    /**
     * Checks if all keys exist in a given array
     * @param array $array array to check
     * @param array $keys with the names of the keys that need to exist
     * @return bool
     */
    public static function allKeysExist( array $array , array $keys ) {
        return !array_diff_key( array_flip($keys) , $array );
    }

    public static function arraySpliceAssoc($array, $offset, $length, $extraArray){
        return array_slice($array, 0, $offset, true) +
            $extraArray +
            array_slice($array, $offset + $length, null, true);
    }

    /**
     * move elements in array
     * @param array $array
     * @param integer $currentIndex
     * @param integer $finalIndex
     */
    public static function move(&$array, $currentIndex, $finalIndex) {
        if( $finalIndex < 0 ){
            $finalIndex = 0;
        }
        if( $finalIndex >= count($array) ){
            $finalIndex = count($array) - 1;
        }

        if( $currentIndex != $finalIndex ){
            $out = array_splice($array, $currentIndex, 1);
            array_splice($array, $finalIndex, 0, $out);
        }
    }

}
