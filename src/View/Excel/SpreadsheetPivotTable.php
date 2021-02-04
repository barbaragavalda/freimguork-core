<?php

namespace Core\View\Excel;

use Core\View\Excel\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;

IOFactory::registerWriter('Xlsx', Xlsx::class);

class SpreadsheetPivotTable extends Spreadsheet
{

}