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

    public function write()
    {
        foreach ($this->xml->data->tabular as $tabular) {
            $pivotCacheId = $tabular['pivotCacheId'];
            $this->replace($tabular, '<tabular pivotCacheId="'.$pivotCacheId.'"></tabular>');
        }

        return $this->xml->asXML();
    }

}