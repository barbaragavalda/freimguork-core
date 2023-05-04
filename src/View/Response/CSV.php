<?php

namespace Core\View\Response;

/**
 * Class CSV
 * @package Core\Views\Response
 */
class CSV extends Response
{

    /**
     * tableName
     * @var string
     */
    private $tableName = '';

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * creates a file and downloads it through the browser
     *
     * @param array  $info info to write to the file
     * @param string $path path to file
     *
     * @return string
     */
    public function initResponse($info = null, $path = null)
    {
        $file = $this->tableName . '-' . date('Y-m-d-His') . '.csv';
        if (array_key_exists('export_without_date', $info)) {
            $file = $this->tableName . '.csv';
        }

        $destination = $path;
        if ($path == null) {
            $destination = 'php://output';
            $this->setHeaderType('application/csv');
            $this->setHeader('Content-Disposition', 'attachment; filename="' . $file . '";');
        } else {
            $destination .= $file;
        }
        $f = fopen($destination, 'w');

        // titles
        if( count($info['csv']['titles']) > 0 ){
            fputs($f, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));
            array_unshift($info['csv']['titles'], '');
            fputcsv($f, $info['csv']['titles'], ';');
        }

        // list
        foreach ($info['csv']['list'] as $item) {
            foreach ($item as &$value) {
                if(!empty($value)){
                    $value = strip_tags($value);
                }
            }
            fputcsv($f, $item, ';');
        }

        return $destination;
    }

    /**
     * generate excel
     *
     * @param string $fileName
     * @param array  $content
     */
    public static function createCSV($fileName, $content)
    {
        $file = $fileName . '.csv';
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $file . '";');
        $f = fopen('php://output', 'w');
        fputs($f, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($content as $line) {
            fputcsv($f, $line, ';');
        }
    }

}
