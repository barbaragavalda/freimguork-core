<?php

namespace Core\View\Excel\Writer;

/**
 * Class SharedStrings
 * Dictionary of excel file
 * @package     Core\View
 * @subpackage  Excel\Writer
 * @file        SharedStrings.php
 * @author      BARBARA GAVALDA <bgb@optisistem.com>
 * @date        21/01/2021
 */
class SharedStrings
{

    /**
     * @var \Core\View\Excel\Writer\SharedStrings $instance . Instance of the singleton
     */
    private static $instance;

    /**
     * @var \SimpleXMLElement
     */
    private $xml;

    /**
     * @var array
     */
    private $strings = array();

    /**
     * load session
     *
     * @param \Core\View\Excel\Writer\SharedStrings $xml
     */
    private function __construct($xml)
    {
        $this->xml = $xml;

        foreach ($this->xml->si as $si) {
            $this->strings[] = (string) $si->t;
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     *
     * @param \SimpleXMLElement $xml
     *
     * @return \Core\View\Excel\Writer\SharedStrings
     */
    public static function getInstance($xml = null)
    {
        if (self::$instance === null && $xml != null) {
            self::$instance = new SharedStrings($xml);
        }
        return self::$instance;
    }

    public function write()
    {
        return $this->xml->asXML();
    }

    public function getPosition($string)
    {
        $position = array_search($string, $this->strings, true);
        if ($position !== false) {
            return $position;
        }

        $this->strings[] = $string;
        $si              = $this->xml->addChild('si');
        $si->addChild('t', $string);
        return count($this->strings) - 1;
    }

}