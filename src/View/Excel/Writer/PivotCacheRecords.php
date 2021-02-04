<?php

namespace Core\View\Excel\Writer;

/**
 * Class PivotCacheRecords
 * Register all items
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        PivotCacheRecords.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class PivotCacheRecords extends XMLGenerator
{

    /**
     * @var \Core\View\Excel\Writer\PivotCacheDefinition
     */
    private $cacheDefinition = null;

    /**
     * @param $cacheDefinition \Core\View\Excel\Writer\PivotCacheDefinition
     */
    public function setDefinition($cacheDefinition)
    {
        $this->cacheDefinition = $cacheDefinition;
    }

    public function write($fields)
    {
        $this->writer->startDocument('1.0', 'UTF-8', 'yes');

        $this->writer->startElement('pivotCacheRecords');
        $this->writer->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $this->writer->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $this->writer->writeAttribute('xmlns:mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006');
        $this->writer->writeAttribute('mc:Ignorable', 'xr');
        $this->writer->writeAttribute('xmlns:xr', 'http://schemas.microsoft.com/office/spreadsheetml/2014/revision');
        $this->writer->writeAttribute('count', count($this->items));

        foreach ($this->items as $item) {
            $this->writer->startElement('r');

            foreach ($fields as $field) {
                $this->element($item[ $field['field'] ], $field['type'], $field['field']);
            }

            $this->writer->endElement();
        }

        $this->writer->endElement();
        return $this->writer->getData();
    }

    private function element($value, $valueType = 's', $find = false)
    {
        if (empty($value)) {
            $this->writer->startElement('m');
            $this->writer->endElement();
        } else {
            if ($this->cacheDefinition != null && $find) {
                $position = $this->cacheDefinition->getPosition($find, $value);
                if ($position !== false) {
                    $valueType = 'x';
                    $value     = $position;
                }
            }

            if ($valueType == null) {
                $valueType = 's';
                if (is_numeric($value)) {
                    $valueType = 'n';
                }
            }
            $this->writer->startElement($valueType);
            $this->writer->writeAttribute('v', $value);
            $this->writer->endElement();
        }
    }

}