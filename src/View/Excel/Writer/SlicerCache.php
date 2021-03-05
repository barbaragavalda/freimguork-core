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
        $extLst = $this->xml->extLst;
        if( $extLst == false ){
            $extLst = $this->xml->addChild('extLst');
        }

        $this->replace($extLst, '<extLst><x:ext uri="{470722E0-AACD-4C17-9CDC-17EF765DBC7E}" xmlns:x15="http://schemas.microsoft.com/office/spreadsheetml/2010/11/main"><x15:slicerCacheHideItemsWithNoData/></x:ext></extLst>');
        return $this->xml->asXML();
    }

}