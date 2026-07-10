<?php

namespace Core\Tests\View\Response;

use Core\View\Response\CSV;
use PHPUnit\Framework\TestCase;

class CSVTest extends TestCase
{

    public function testSetsCsvHeadersWithADatedFilename(): void
    {
        $response = (new CSV('recipes'))->initResponse($this->rows());

        $this->assertSame('application/csv', $response->getHeaderLine('Content-Type'));
        $this->assertMatchesRegularExpression(
            '/^attachment; filename="recipes-\d{4}-\d{2}-\d{2}-\d{6}\.csv";$/',
            $response->getHeaderLine('Content-Disposition')
        );
    }

    public function testExportWithoutDateDropsTheTimestampFromTheFilename(): void
    {
        $info               = $this->rows();
        $info['export_without_date'] = true;

        $response = (new CSV('recipes'))->initResponse($info);

        $this->assertSame(
            'attachment; filename="recipes.csv";',
            $response->getHeaderLine('Content-Disposition')
        );
    }

    public function testBodyContainsTitlesAndStrippedRows(): void
    {
        $response = (new CSV('recipes'))->initResponse($this->rows());
        $body     = (string) $response->getBody();

        // leading bytes are the UTF-8 BOM written ahead of the titles row
        $this->assertStringContainsString(";Name;Price\n", $body);
        $this->assertStringContainsString("Rosti;9.50\n", $body);
        // <b>...</b> is stripped from cell values
        $this->assertStringContainsString("Amanida;7.00\n", $body);
        $this->assertStringNotContainsString('<b>', $body);
    }

    public function testWritingToAPathLeavesTheResponseBodyEmptyAndWritesTheFile(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('csv-test-') . '/';
        mkdir($dir);

        try {
            $response = (new CSV('recipes'))->initResponse(
                array('export_without_date' => true, 'csv' => array('titles' => array(), 'list' => array(array('x', 'y')))),
                $dir
            );

            $this->assertSame('', (string) $response->getBody());
            $this->assertSame("x;y\n", file_get_contents($dir . 'recipes.csv'));
        } finally {
            @unlink($dir . 'recipes.csv');
            @rmdir($dir);
        }
    }

    private function rows(): array
    {
        return array(
            'csv' => array(
                'titles' => array('Name', 'Price'),
                'list'   => array(
                    array('Rosti', '9.50'),
                    array('<b>Amanida</b>', '7.00'),
                ),
            ),
        );
    }

}
