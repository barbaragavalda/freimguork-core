<?php

namespace Core\View\Excel\Writer;

use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\WriterPart;

class XMLGenerator extends WriterPart
{

    /**
     * @var \PhpOffice\PhpSpreadsheet\Shared\XMLWriter
     */
    protected $writer;

    /**
     * @var \SimpleXMLElement
     */
    protected $xml;

    /**
     * @var array
     */
    protected $items = array();

    /**
     * @var \Core\View\Excel\Writer\SharedStrings
     */
    protected $sharedStrings = array();

    public function __construct(
        \PhpOffice\PhpSpreadsheet\Writer\Xlsx $pWriter,
        $items = array(),
        \SimpleXMLElement $xml = null
    ) {
        parent::__construct($pWriter);

        if ($this->getParentWriter()->getUseDiskCaching()) {
            $this->writer = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $this->writer = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        $this->items = $items;
        $this->xml   = $xml;

        $this->sharedStrings = SharedStrings::getInstance();
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string            $replace
     */
    protected function replace(\SimpleXMLElement $element, $replace)
    {
        $xml        = dom_import_simplexml($element);
        $xmlReplace = new \SimpleXMLElement($replace, LIBXML_NOERROR);
        $import     = $xml->ownerDocument->importNode(dom_import_simplexml($xmlReplace), true);
        $xml->parentNode->replaceChild($import, $xml);
    }

}