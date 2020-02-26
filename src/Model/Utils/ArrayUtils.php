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
                    unset($item[$fieldName]);
                }
            }
            unset($item[$fieldID]);
        }
        return $items;
    }

}
