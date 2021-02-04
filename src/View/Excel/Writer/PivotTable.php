<?php

namespace Core\View\Excel\Writer;

/**
 * Class PivotTable
 * Remove info on this XML file
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        PivotTable.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class PivotTable extends XMLGenerator
{

    public function write()
    {
        foreach ($this->xml->pivotFields->pivotField as $pivotField) {
            if (count($pivotField->items) == 1) {
                $this->replace($pivotField->items, '<items count="1"><item t="default"/></items>');
            }
        }

        return $this->xml->asXML();
    }

}