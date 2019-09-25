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
    public static function replaceKeys($items, $fieldID, $fieldName){
        foreach($items as &$item){
            $item['id'] = $item[$fieldID];
            if( is_array($fieldName) ){
                $item['name'] = '';
                for($i=0; $i<count($fieldName); $i++){
                    $key = $fieldName[$i];
                    $value = $item[$key];
                    if( $i != 0 ) $value = '(' . $value . ')';
                    $item['name'] .= ' '.$value;
                    unset($item[$key]);
                }
            }else{
                $item['name'] = $item[$fieldName];
                unset($item[$fieldName]);
            }
            unset($item[$fieldID]);
        }
        return $items;
    }

}
