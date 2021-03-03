<?php

namespace Core\View\Excel\Writer;

/**
 * Class PivotCacheDefinition
 * Generate XML pivotCacheDefinition
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        PivotCacheDefinition.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class PivotCacheDefinition extends XMLGenerator
{

    private $values = array();

    public function write($fields)
    {
        $this->xml['recordCount']   = count($this->items);
        $this->xml['refreshOnLoad'] = 1;

        $date                       = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(time());
        $this->xml['refreshedDate'] = str_replace(',', '.', $date);

        $this->xml->cacheFields['count'] = count($fields);

        $i      = 0;
        $fields = array_values($fields);
        foreach ($this->xml->cacheFields->cacheField as $cacheField) {
            if ($i < count($fields)) {
                $type  = $fields[ $i ]['type'];
                $field = $fields[ $i ]['field'];

                if (!in_array($field, array('responsible'))) {
                    $values      = $this->prepare($field);
                    $sharedItems = true;
                    switch ($type) {
                        case 'd':
                            $cacheField->sharedItems['containsSemiMixedTypes'] = 0;
                            $cacheField->sharedItems['containsNonDate']        = 0;
                            $cacheField->sharedItems['containsDate']           = 1;
                            $cacheField->sharedItems['containsString']         = 0;
                            $this->setMinMax($cacheField->sharedItems, $values, 'Date');
                            break;
                        case 'n':
                            $cacheField->sharedItems['containsString']         = 0;
                            $cacheField->sharedItems['containsSemiMixedTypes'] = 0;
                            $cacheField->sharedItems['containsNumber']         = 1;
                            $cacheField->sharedItems['containsInteger']        = 1;

                            $sharedItems = false;
                            $this->setMinMax($cacheField->sharedItems, $values);
                            break;
                        case 's':
                            unset($cacheField->sharedItems['containsMixedTypes']);
                            unset($cacheField->sharedItems['containsSemiMixedTypes']);
                            unset($cacheField->sharedItems['containsString']);
                            unset($cacheField->sharedItems['containsNumber']);
                            unset($cacheField->sharedItems['containsDate']);
                            unset($cacheField->sharedItems['containsInteger']);
                            unset($cacheField->sharedItems['containsNonDate']);
                            unset($cacheField->sharedItems['minValue']);
                            unset($cacheField->sharedItems['maxValue']);
                            break;
                    }

                    if ($sharedItems) {
                        $cacheField->sharedItems['count'] = count($values);
                        $cacheField->sharedItems[0]       = $this->addValues($values, $type, $field);
                    } else {
                        unset($cacheField->sharedItems['count']);
                        $cacheField->sharedItems[0] = '';
                    }

                    if (count($values)) {
                        $cacheField->sharedItems['containsBlank'] = 0;
                    }
                }
            }

            $i++;
        }

        $finalValues = array();
        foreach ($this->values as $key => $values) {
            $finalValues[ $key ] = array_flip($values);
        }
        $this->values = $finalValues;

        $xml = str_replace('&lt;', '<', $this->xml->asXML());
        $xml = str_replace('&gt;', '>', $xml);

        return $xml;
    }

    /**
     * remove duplicates and sort values
     * filter
     *
     * @param string $field
     *
     * @return array
     */
    private function prepare($field)
    {
        $values = array_column($this->items, $field);

        $totalValues = count($values);
        $values      = array_filter($values);
        $finalValues = count($values);
        if ($totalValues > $finalValues) {
            array_unshift($values, 0);
        }

        $values = array_unique($values);
        $values = array_values($values);
        asort($values);

        return $values;
    }

    /**
     * @param array  $values
     * @param string $valueType
     * @param string $field
     *
     * @return string
     */
    private function addValues($values, $valueType = null, $field = null)
    {
        $sharedItems = '';
        if ($valueType != null) {
            $this->values[ $field ] = $values;
            foreach ($values as $value) {
                if (empty($value)) {
                    $sharedItems .= '<' . $valueType . ' v="0"/>';
                } else {
                    $sharedItems .= '<' . $valueType . ' v="' . $value . '"/>';
                }
            }
        }

        return $sharedItems;
    }

    /**
     * get min and max values
     *
     * @param \SimpleXMLElement $element
     * @param array             $values
     * @param string            $field
     *
     * @return string
     */
    private function setMinMax($element, $values = array(), $field = 'Value')
    {
        if (count($values) >= 2) {
            $element[ 'min' . $field ] = array_shift($values);
            $element[ 'max' . $field ] = array_pop($values);
        } else {
            unset($element[ 'min' . $field ]);
            unset($element[ 'max' . $field ]);
        }
    }

    public function getPosition($type, $string)
    {
        if (isset($this->values[ $type ][ $string ])) {
            return $this->values[ $type ][ $string ];
        }
        return false;
    }
}