<?php

namespace Core\View\Excel\Writer;

/**
 * Class Sheet
 * Fill sheet with data
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        Sheet.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class Sheet extends XMLGenerator
{

    public function write($offset, $fields, $maxColumn, $formats)
    {
        $firstCell                   = 'A1';
        $this->xml->dimension['ref'] = $firstCell . ':' . $maxColumn . (count($this->items) + $offset);

        $this->xml->sheetViews->sheetView['topLeftCell']           = $firstCell;
        $this->xml->sheetViews->sheetView->selection['activeCell'] = $firstCell;
        $this->xml->sheetViews->sheetView->selection['sqref']      = $firstCell;

        $sheetData = '<sheetData>';
        foreach ($this->xml->sheetData->row as $row) {
            $rowNumber = intval($row['r']);
            if ($rowNumber <= $offset) {
                $sheetData .= $row->asXML();
            } else {
                break;
            }
        }

        $i = $offset + 1;
        foreach ($this->items as $item) {
            $sheetData .= '<row r="' . $i . '" spans="1:' . count($fields) . '" thickTop="1">';

            $j = 0;
            foreach ($fields as $column => $field) {
                $fieldName = $field['field'];
                $value     = $item[ $field['field'] ];

                $extra  = '';
                $format = $this->getFormat($formats, $field['type'], $fieldName);
                if ($format) {
                    $extra .= ' s="' . $format . '"';
                }

                $found = true;
                if (!empty($value)) {
                    switch ($field['type']) {
                        case 'n':
                            $found = false;
                            break;
                        case 'd':
                            $found = false;
                            $value = $item[ $fieldName . '_timestamp' ];
                            break;
                    }
                    $sheetData .= $this->setValue($column . $i, $value, $found, $extra);
                } else {
                    $notEmpty = array(
                        'publicSource',
                        'publicDestination',
                        'privateSource',
                        'privateDestination',
                        'responsible'
                    );
                    if (!in_array($fieldName, $notEmpty)) {
                        $sheetData .= $this->setValue($column . $i, 0, false);
                    }
                }

                $j++;
            }

            $sheetData .= '</row>';
            $i++;
        }
        $sheetData .= '</sheetData>';
        $this->replace($this->xml->sheetData[0], $sheetData);
        return $this->xml->asXML();
    }

    private function setValue($cell, $value, $found = false, $extra = '')
    {
        if ($found !== false) {
            $value = $this->sharedStrings->getPosition($value);
            $extra .= ' t="s"';
        }

        return '<c r="' . $cell . '"' . $extra . '><v>' . $value . '</v></c>';
    }

    private function getFormat($formats, $type, $fieldName)
    {
        switch ($type) {
            case 'd':
                return $formats[ $fieldName ]['position'];
                break;
        }
        return false;
    }

}