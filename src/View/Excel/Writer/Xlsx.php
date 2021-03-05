<?php

namespace Core\View\Excel\Writer;

use Core\Model\Utils\StringUtils;
use Scripts\Model\Statistics\Util\Filters;
use Swan\Model\Util\System;

class Xlsx extends \PhpOffice\PhpSpreadsheet\Writer\Xlsx
{

    const FILE_CORE                   = 'core.xml';
    const FILE_PIVOT_CACHE_DEFINITION = 'pivotCacheDefinition';
    const FILE_PIVOT_CACHE_RECORD     = 'pivotCacheRecord';
    const FILE_PIVOT_TABLE            = 'pivotTable';
    const FILE_SHARED_STRINGS         = 'sharedStrings.xml';
    const FILE_SLICER_CACHE           = 'slicerCache';
    const FILE_STYLE                  = 'styles.xml';

    /**
     * @var array
     */
    private $items = array();

    /**
     * @var integer
     */
    private $sheet = 1;

    /**
     * @var integer
     */
    private $offsetTop = 0;

    /**
     * @var array
     */
    private $fields = array();

    /**
     * @var array
     */
    private $formats = array();

    /**
     * @var \ZipArchive
     */
    private $zip;

    /**
     * @var string
     */
    private $destination = '';

    /**
     * files to write
     * @var array
     */
    private $files = array();

    /**
     * copy template and open it in order to add new information
     *
     * @param string $templateName
     * @param string $destination
     *
     * @return bool success
     */
    public function setTemplate($templateName, $destination)
    {
        // create directory
        $infoTemplate      = pathinfo($templateName);
        $this->destination = $destination . '-' . $infoTemplate['basename'];
        $infoDestination   = pathinfo($this->destination);
        $system            = new System();
        if ($system->makeDirectory($infoDestination['dirname'])) {
            // copy template
            if (copy($templateName, $this->destination)) {
                // open new template file
                $this->zip = new \ZipArchive();
                if ($this->zip->open($this->destination, \ZipArchive::CREATE)) {
                    return true;
                }
            }
        }

        $this->zip = null;
        return false;
    }

    public function setItems($items)
    {
        $this->items = $items;
    }

    public function setConfiguration($sheet, $offset, $fields)
    {
        $this->sheet     = $sheet;
        $this->offsetTop = $offset;
        $this->fields    = $fields;
    }

    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Save PhpSpreadsheet to file.
     *
     * @param resource|string $pFilename
     */
    public function save($pFilename): void
    {
        if ($this->zip != null) {
            $sheetExtension = $this->sheet . '.xml';
            $columns        = array_keys($this->fields);
            $maxColumn      = null;
            if (count($columns)) {
                $maxColumn = $columns[ count($columns) - 1 ];
            }

            $this->initSharedStrings();
            $this->initStyles();

            $cacheDefinition = null;
            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $file     = $this->zip->statIndex($i);
                $fileName = $file['name'];
                $fileInfo = pathinfo($fileName);

                $isXML = $fileInfo['extension'] == 'xml';
                if ($isXML) {
                    if (StringUtils::startsWidth($fileInfo['basename'], self::FILE_PIVOT_CACHE_DEFINITION)) {
                        $content                  = $this->getContent($fileName);
                        $cacheDefinition          = new PivotCacheDefinition($this, $this->items, $content);
                        $this->files[ $fileName ] = $cacheDefinition->write($this->fields);
                    }
                }
            }

            for ($i = 0; $i < $this->zip->numFiles; $i++) {
                $file     = $this->zip->statIndex($i);
                $fileName = $file['name'];
                $fileInfo = pathinfo($fileName);

                $isXML = $fileInfo['extension'] == 'xml';
                if ($isXML) {
//                    if (StringUtils::startsWidth($fileInfo['basename'], self::FILE_PIVOT_CACHE_RECORD)) {
//                        $content      = $this->getContent($fileName);
//                        $cacheRecords = new PivotCacheRecords($this, $this->items, $content);
//                        $cacheRecords->setDefinition($cacheDefinition);
//                        $this->files[ $fileName ] = $cacheRecords->write($this->fields);
//                    }
//                    if (StringUtils::startsWidth($fileInfo['basename'], self::FILE_PIVOT_TABLE)) {
//                        $content                  = $this->getContent($fileName);
//                        $pivotTable               = new PivotTable($this, $this->items, $content);
//                        $this->files[ $fileName ] = $pivotTable->write();
//                    }
                    if (StringUtils::startsWidth($fileInfo['basename'], self::FILE_SLICER_CACHE)) {
                        $content                  = $this->getContent($fileName);
                        $slicer = new SlicerCache($this, $this->items, $content);
                        $this->files[ $fileName ] = $slicer->write();
                    }
                    if ($fileInfo['basename'] == 'table' . $sheetExtension) {
                        $content                  = $this->getContent($fileName);
                        $table                    = new Table($this, $this->items, $content);
                        $this->files[ $fileName ] = $table->write($this->offsetTop, $maxColumn);
                    }
                    if ($fileInfo['basename'] == 'sheet' . $sheetExtension) {
                        $content                  = $this->getContent($fileName);
                        $sheet                    = new Sheet($this, $this->items, $content);
                        $this->files[ $fileName ] = $sheet->write(
                            $this->offsetTop,
                            $this->fields,
                            $maxColumn,
                            $this->formats
                        );
                    }
                }
            }

            // update strings
            $sharedStrings                                    = SharedStrings::getInstance();
            $this->files[ 'xl/' . self::FILE_SHARED_STRINGS ] = $sharedStrings->write();

            // modify files
            foreach ($this->files as $fileName => $content) {
                $this->zip->addFromString($fileName, $content);
            }

            $this->updateModificationDate();
            $this->zip->close();
        }
    }

    /**
     * create file with shared strings content
     */
    private function initSharedStrings()
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $fileName = $file['name'];
            $fileInfo = pathinfo($fileName);
            if ($fileInfo['basename'] == self::FILE_SHARED_STRINGS) {
                $content = $this->getContent($fileName);
                SharedStrings::getInstance($content);
            }
        }
    }

    /**
     * create file with shared strings content
     */
    private function initStyles()
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $fileName = $file['name'];
            $fileInfo = pathinfo($fileName);
            if ($fileInfo['basename'] == self::FILE_STYLE) {
                $content                  = $this->getContent($fileName);
                $styles                   = new Styles($this, $this->items, $content);
                $this->files[ $fileName ] = $styles->write();
                $this->formats            = $styles->getFormats();
            }
        }
    }

    /**
     * update modification date of excel file
     */
    private function updateModificationDate()
    {
        for ($i = 0; $i < $this->zip->numFiles; $i++) {
            $file     = $this->zip->statIndex($i);
            $fileName = $file['name'];
            $fileInfo = pathinfo($fileName);
            if ($fileInfo['basename'] == self::FILE_CORE) {
                $xmlCore = $this->getContent($fileName);
                if ($xmlCore != null) {
                    $content = $xmlCore->asXML();
                    $start   = strpos($content, '<dcterms:modified');
                    $end     = strpos($content, '</dcterms:modified>');
                    $content = substr_replace($content, '', $start, $end - $start + strlen('</dcterms:modified>'));
                    $content = str_replace('</cp:coreProperties>', '', $content);

                    $now     = Filters::toDate(new \DateTime());
                    $content .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>';
                    $content .= '</cp:coreProperties>';
                    $this->zip->addFromString($fileName, $content);
                }
                break;
            }
        }
    }

    /**
     * load file into \SimpleXMLElement object
     *
     * @param $fileName
     *
     * @return null|\SimpleXMLElement
     */
    private function getContent($fileName)
    {
        $file = $this->zip->getStream($fileName);
        if ($file) {
            $fileContent = stream_get_contents($file);
            return new \SimpleXMLElement($fileContent);
        }
        return null;
    }

}