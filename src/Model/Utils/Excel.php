<?php

namespace Core\Model\Utils;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Workbook;

class Excel extends Spreadsheet {

    /**
     * current sheet
     * @var \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Workbook
     */
    protected $sheet = null;

    /**
     * current sheet number
     * @var integer
     */
    private $currentSheet = 0;

    /**
     * sheet columns
     * @var array
     */
    private $columns = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
    );

    /**
     * add new sheet with name
     * @param string $name
     */
    public function addTab($name){
        if( $this->currentSheet == 0 ){
            $this->sheet = $this->getActiveSheet();
        }else{
            $this->sheet = $this->createSheet( $this->currentSheet );
        }
        $this->sheet->setTitle($name);

        $this->currentSheet++;
    }

    /**
     * return letter corresponding with index
     * @param integer $index
     * @return string
     */
    public function getColumn($index){
        return $this->columns[$index];
    }

    /**
     * merge cells
     * @param string $cellRange
     */
    public function merge($cellRange){
        $this->sheet->mergeCells($cellRange);
    }

    /**
     * change cells style to bold
     * @param string $cellRange
     * @param integer $size
     */
    public function bold($cellRange, $size = null){
        $this->sheet->getStyle($cellRange)->getFont()->setBold(true);

        if( $size != null ) $this->fontSize($cellRange, $size);
    }

    /**
     * change cells text color
     * @param string $cellRange
     * @param string $textColor
     * @param integer $size
     */
    public function textColor($cellRange, $textColor, $size = null){
        $this->sheet->getStyle($cellRange)->getFont()->getColor()->setRGB($textColor);

        if( $size != null ) $this->fontSize($cellRange, $size);
    }

    /**
     * change cells font size
     * @param string $cellRange
     * @param integer $size
     */
    public function fontSize($cellRange, $size){
        $this->sheet->getStyle($cellRange)->getFont()->setSize($size);
    }

    /**
     * change cells alignment to center
     * @param string $cellRange
     */
    public function center($cellRange){
        $this->sheet->getStyle($cellRange)->getAlignment()->setHorizontal('center');
    }

    /**
     * change cells alignment to right/left
     * @param string $cellRange
     */
    public function align($cellRange, $alignment = 'left'){
        $this->sheet->getStyle($cellRange)->getAlignment()->setHorizontal($alignment);
    }

    /**
     * add border to cells
     * @param string $cellRange
     * @param string $color
     */
    public function border($cellRange, $color = '000000'){
        $cells = $this->explodeRange($cellRange);

        for( $row=$cells['rowStart']; $row<=$cells['rowEnd']; $row++ ){
            for( $col=$cells['colStart']; $col<=$cells['colEnd']; $col++ ){
                $this->sheet->getStyle($this->columns[$col].$row)->getBorders()->applyFromArray(array(
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => $color],
                    ]
                ));
            }
        }
    }

    /**
     * change cells background color
     * @param string $cellRange
     * @param string $color
     * @param string $textColor
     */
    public function bgColor($cellRange, $color, $textColor = null){
        $this->sheet->getStyle($cellRange)->getFill()->applyFromArray(array(
            'fillType' => Fill::FILL_SOLID,
            'color' => array('rgb' => $color)
        ));

        if( $textColor != null ) $this->textColor($cellRange, $textColor);
    }

    /**
     * apply grey background color to even rows
     * @param integer $lastRow
     * @param integer $firstRow
     * @param string $lastColumn
     */
    public function zebra($lastRow, $firstRow = 1, $lastColumn = 'Z'){
        for( $i=$firstRow; $i<$lastRow; $i++ ){
            if( $i%2 == 0 ){
                $this->bgColor('A' . $i . ':' . $lastColumn . $i, 'DDDDDD');
            }
        }
    }

    /**
     * adapt columns with to its content
     * @param array $columns
     */
    public function autoSize($columns = null){
        if( $columns == null ) $columns = $this->columns;

        foreach($columns as $column){
            $this->sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * fill cells with array content
     * @param array $array
     * @param string $start
     */
    public function fill($array, $start){
        $this->sheet->fromArray($array, null, $start);
    }

    private function explodeRange($cellRange){
        $range = array();
        $cells = explode(':', $cellRange);
        if( count($cells) > 0 ){
            $range = array(
                'colStart' => array_search(preg_replace('/[^a-zA-Z]/', '', $cells[0]), $this->columns),
                'rowStart' => preg_replace('/[^0-9]/', '', $cells[0])
            );
            if( count($cells) == 1 ){
                $range['colEnd'] = $range['colStart']+1;
                $range['rowEnd'] = $range['rowStart']+1;
            }else{
                $range['colEnd'] = array_search(preg_replace('/[^a-zA-Z]/', '', $cells[1]), $this->columns);
                $range['rowEnd'] = preg_replace('/[^0-9]/', '', $cells[1]);
            }
        }
        return $range;
    }

    /**
     * call any function of \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Workbook class
     * @param string $functionName
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($functionName, $args) {
        if( method_exists($this->sheet, $functionName) ){
            return call_user_func_array(array($this->sheet, $functionName), $args);
        } else {
            throw new \Exception('The method <em>' . $functionName . '</em> doesn\'t exists on Spreadsheet. Check <a href="https://phpspreadsheet.readthedocs.io/en/latest/" target="_blank">the manual</a> for more information');
        }
    }

}