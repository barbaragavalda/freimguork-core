<?php

namespace Core\View\Excel\Writer;

/**
 * Class Styles
 * Create styles in order to format data properly
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        Sheet.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class Styles extends XMLGenerator
{

    private $formats = array(
        'date' => array('format' => 'dd/mm/yyyy'),
        'time' => array('format' => 'h:mm:ss')
    );

    public function getFormats()
    {
        return $this->formats;
    }

    public function write()
    {
        $maxID = 0;
        foreach ($this->xml->numFmts->numFmt as $numFmt) {
            $id = (int) $numFmt['numFmtId'];
            if ($id > $maxID) {
                $maxID = $id;
            }
        }

        foreach ($this->formats as &$format) {
            $maxID++;

            // create new data format
            $numFmt = $this->xml->numFmts->addChild('numFmt');
            $numFmt->addAttribute('numFmtId', $maxID);
            $numFmt->addAttribute('formatCode', $format['format']);

            // create new style
            $xf = $this->xml->cellXfs->addChild('xf');
            $xf->addAttribute('numFmtId', $maxID);

            $format['id'] = $maxID;
        }

        // create positions
        foreach ($this->formats as &$f) {
            $i = 0;
            foreach ($this->xml->cellXfs->xf as $xf) {
                $id = (int) $xf['numFmtId'];
                if ($id == $f['id']) {
                    $f['position'] = $i;
                    break;
                }
                $i++;
            }
        }

        return $this->xml->asXML();
    }

}