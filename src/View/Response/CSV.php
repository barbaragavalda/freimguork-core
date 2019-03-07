<?php
    
namespace Core\View\Response;

/**
 * Class CSV
 * @package Core\Views\Response
 */
class CSV extends Response{
    
    /**
     * tableName
     * @var string
     */
    private $tableName = '';
    
    public function __construct($tableName){
        $this->tableName = $tableName;
    }
    
    /**
     * creates a file and downloads it through the browser
     * @param array $info . Is always null
     */
    public function initResponse( $info = null ) {
        $file = $this->tableName . '-' . date('Y-m-d-His') . '.csv';
        if( array_key_exists('export_without_date', $info) ){
            $file = $this->tableName . '.csv';
        }
        
        $this->setHeaderType('application/csv');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $file . '";');
        
        $f = fopen('php://output', 'w');
        fputs($f, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // titles
        array_unshift($info['csv']['titles'], '');
        fputcsv($f, $info['csv']['titles'], ';');
        
        // list
        foreach($info['csv']['list'] as $item){
            fputcsv($f, $item, ';');
        }
    }

}
