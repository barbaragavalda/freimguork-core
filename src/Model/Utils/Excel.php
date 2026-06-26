<?php

namespace Core\Model\Utils;

use Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Excel extends Spreadsheet
{

    protected Worksheet $sheet;

    private int $currentSheet = 0;

    /**
     * sheet columns
     * @var array
     */
    private array $columns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',);

    /**
     * add new sheet with name
     *
     * @param string $name
     */
    public function addTab(string $name): void
    {
        if ($this->currentSheet == 0) {
            $this->sheet = $this->getActiveSheet();
        } else {
            $this->sheet = $this->createSheet($this->currentSheet);
        }
        $this->sheet->setTitle($name);

        $this->currentSheet++;
    }

    /**
     * return letter corresponding with index
     *
     * @param int $index
     *
     * @return string
     */
    public function getColumn(int $index): string
    {
        return $this->columns[ $index ];
    }

    /**
     * merge cells
     *
     * @param string $cellRange
     */
    public function merge(string $cellRange): void
    {
        $this->sheet->mergeCells($cellRange);
    }

    /**
     * change cells style to bold
     *
     * @param string $cellRange
     * @param ?int   $size
     */
    public function bold(string $cellRange, ?int $size = null): void
    {
        $this->sheet->getStyle($cellRange)->getFont()->setBold(true);

        if ($size != null) {
            $this->fontSize($cellRange, $size);
        }
    }

    /**
     * change cells text color
     *
     * @param string $cellRange
     * @param string $textColor
     * @param ?int   $size
     */
    public function textColor(string $cellRange, string $textColor, ?int $size = null): void
    {
        $this->sheet->getStyle($cellRange)->getFont()->getColor()->setRGB($textColor);

        if ($size != null) {
            $this->fontSize($cellRange, $size);
        }
    }

    /**
     * change cells font size
     *
     * @param string $cellRange
     * @param int    $size
     */
    public function fontSize(string $cellRange, int $size): void
    {
        $this->sheet->getStyle($cellRange)->getFont()->setSize($size);
    }

    /**
     * change cells alignment to center
     *
     * @param string $cellRange
     */
    public function center(string $cellRange): void
    {
        $this->sheet->getStyle($cellRange)->getAlignment()->setHorizontal('center');
    }

    /**
     * change cells alignment to right/left
     *
     * @param string $cellRange to apply alignment
     * @param string $alignment desired for cell range
     */
    public function align(string $cellRange, string $alignment = 'left'): void
    {
        $this->sheet->getStyle($cellRange)->getAlignment()->setHorizontal($alignment);
    }

    /**
     * change cells alignment to top/center/bottom
     *
     * @param string $cellRange to apply alignment
     * @param string $alignment desired for cell range
     */
    public function verticalAlign(string $cellRange, string $alignment = 'top'): void
    {
        $this->sheet->getStyle($cellRange)->getAlignment()->setVertical($alignment);
    }

    /**
     * add border to cells
     *
     * @param string $cellRange
     * @param string $color
     */
    public function border(string $cellRange, string $color = '000000'): void
    {
        $cells = $this->explodeRange($cellRange);

        for ($row = $cells['rowStart']; $row <= $cells['rowEnd']; $row++) {
            for ($col = $cells['colStart']; $col <= $cells['colEnd']; $col++) {
                $this->sheet->getStyle($this->columns[ $col ] . $row)->getBorders()->applyFromArray(array(
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => $color],
                    ]
                ));
            }
        }
    }

    /**
     * change cells background color
     *
     * @param string  $cellRange
     * @param string  $color
     * @param ?string $textColor
     */
    public function bgColor(string $cellRange, string $color, ?string $textColor = null): void
    {
        $this->sheet->getStyle($cellRange)->getFill()->applyFromArray(array(
            'fillType' => Fill::FILL_SOLID,
            'color'    => array('rgb' => $color)
        ));

        if ($textColor != null) {
            $this->textColor($cellRange, $textColor);
        }
    }

    /**
     * apply gray background color to even rows
     *
     * @param int    $lastRow
     * @param int    $firstRow
     * @param string $lastColumn
     */
    public function zebra(int $lastRow, int $firstRow = 1, string $lastColumn = 'Z'): void
    {
        for ($i = $firstRow; $i < $lastRow; $i++) {
            if ($i % 2 == 0) {
                $this->bgColor('A' . $i . ':' . $lastColumn . $i, 'DDDDDD');
            }
        }
    }

    /**
     * adapt columns with to its content
     *
     * @param ?array $columns
     */
    public function autoSize(?array $columns = null): void
    {
        if ($columns == null) {
            $columns = $this->columns;
        }

        foreach ($columns as $column) {
            $this->sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * fill cells with array content
     *
     * @param array  $array
     * @param string $start
     */
    public function fill(array $array, string $start): void
    {
        $this->sheet->fromArray($array, null, $start);
    }

    private function explodeRange(string $cellRange): array
    {
        $range = array();
        $cells = explode(':', $cellRange);
        if (count($cells) > 0) {
            $range = array(
                'colStart' => array_search(preg_replace('/[^a-zA-Z]/', '', $cells[0]), $this->columns),
                'rowStart' => preg_replace('/[^0-9]/', '', $cells[0])
            );
            if (count($cells) == 1) {
                $range['colEnd'] = $range['colStart'] + 1;
                $range['rowEnd'] = $range['rowStart'] + 1;
            } else {
                $range['colEnd'] = array_search(preg_replace('/[^a-zA-Z]/', '', $cells[1]), $this->columns);
                $range['rowEnd'] = preg_replace('/[^0-9]/', '', $cells[1]);
            }
        }
        return $range;
    }

    /**
     * call any function of \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Workbook class
     *
     * @param string $functionName
     * @param array  $args
     *
     * @return mixed
     * @throws \Exception
     */
    public function __call(string $functionName, array $args): mixed
    {
        if (method_exists($this->sheet, $functionName)) {
            return call_user_func_array(array($this->sheet, $functionName), $args);
        } else {
            throw new Exception(
                "The method <em>$functionName</em> doesn\'t exists on Spreadsheet. Check <a href='https://phpspreadsheet.readthedocs.io/en/latest/' target='_blank'>the manual</a> for more information"
            );
        }
    }

}