<?php

namespace Core\View\Excel\Writer;

/**
 * Class Table
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        Table.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class Table extends XMLGenerator
{

    public function write($offset, $maxColumn)
    {
        $this->xml['ref']             = 'A1:' . $maxColumn . (count($this->items) + $offset);
        $this->xml->autoFilter['ref'] = 'A1:' . $maxColumn . (count($this->items) + $offset);

        return $this->xml->asXML();
    }

}