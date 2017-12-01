<?php

namespace Core\View\Response;

/**
 * Class CSV
 * @package Core\Views\Response
 */
class CSV extends Response{

    /**
     * field to be downloaded
     * @var string
     */
    private $fieldName = '';

    public function __construct($tableName){
        $this->fieldName = $tableName . '-' . date('Y-m-d-His') . '.csv';
    }

    /**
     * creates a file and downloads it through the browser
     * @param array $info . Is always null
     */
    public function initResponse( $info = null ) {
        $f = fopen('php://output', 'w');
        foreach($info['csv'] as $item){
            fputcsv($f, $item, ';');
        }

        $this->setHeaderType('application/csv');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $this->fieldName . '";');
    }

}