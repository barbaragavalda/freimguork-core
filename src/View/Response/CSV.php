<?php

namespace Core\View\Response;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class CSV extends Response
{

    private string $tableName;

    public function __construct($tableName)
    {
        parent::__construct();

        $this->tableName = $tableName;
    }

    /**
     * creates a file and downloads it through the browser
     *
     * @param array   $info info to write to the file
     * @param ?string $path path to file
     */
    public function initResponse(array $info = array(), ?string $path = null): ResponseInterface
    {
        $file = $this->tableName . '-' . date('Y-m-d-His') . '.csv';
        if (array_key_exists('export_without_date', $info)) {
            $file = $this->tableName . '.csv';
        }

        $toHttpResponse = $path == null;
        if ($toHttpResponse) {
            // built in memory, then wrapped as the PSR-7 body below - not
            // written straight to php://output anymore, so this builds like
            // any other Response subclass (cacheable, testable)
            $f = fopen('php://temp', 'w+');
            $this->setHeaderType('application/csv');
            $this->setHeader('Content-Disposition', 'attachment; filename="' . $file . '";');
        } else {
            $f = fopen($path . $file, 'w');
        }

        // titles
        if (count($info['csv']['titles']) > 0) {
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            array_unshift($info['csv']['titles'], '');
            fputcsv($f, $info['csv']['titles'], ';', escape: '\\');
        }

        // list
        foreach ($info['csv']['list'] as $item) {
            foreach ($item as &$value) {
                if (!empty($value)) {
                    $value = strip_tags($value);
                }
            }
            fputcsv($f, $item, ';', escape: '\\');
        }

        if ($toHttpResponse) {
            rewind($f);
            $this->response = $this->response->withBody(Utils::streamFor($f));
        } else {
            fclose($f);
        }

        return $this->response;
    }

    /**
     * generate excel
     *
     * @param string $fileName
     * @param array  $content
     */
    public static function createCSV(string $fileName, array $content): void
    {
        $file = $fileName . '.csv';
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $file . '";');
        $f = fopen('php://output', 'w');
        fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($content as $line) {
            fputcsv($f, $line, ';', escape: '\\');
        }
    }

}
