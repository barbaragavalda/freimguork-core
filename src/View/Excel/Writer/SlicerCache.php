<?php

namespace Core\View\Excel\Writer;

/**
 * Class SlicerCache
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        SlicerCache.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        03/03/2021
 */
class SlicerCache extends XMLGenerator
{

    public function write($cacheDefinition)
    {
        $sourceName = (string) $this->xml['sourceName'];

        $data = null;
        foreach ($cacheDefinition->cacheFields->cacheField as $cacheField) {
            $name = (string) $cacheField['name'];
            if ($name == $sourceName) {
                $data = $cacheField->sharedItems;
                break;
            }
        }

        $items = '<items count="0"></items>';
        if ($data != null) {
            $count = (int) $data['count'];
            $items = '<items count="' . $count . '">';
            for ($i = 0; $i < $count; $i++) {
                $items .= '<i x="' . $i . '" s="1"/>';
            }
            $items .= '</items>';
        }
        $this->replace($this->xml->data->tabular->items, $items);

        return $this->xml->asXML();
    }

}