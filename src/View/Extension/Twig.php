<?php

namespace Core\View\Extension;

use Core\Model\Utils\StringUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Twig extends AbstractExtension{

    public function getFilters(){
        return [
            new TwigFilter('formatPrice', [$this, 'formatPrice']),
            new TwigFilter('formatArray', [$this, 'formatArray']),
            new TwigFilter('forJS', [$this, 'forJS']),
        ];
    }

    public function formatPrice($price, $decimals = 2, $thousandsSep = '.', $decPoint = ','){
        return StringUtils::formatPrice($price, $decimals, $thousandsSep, $decPoint);
    }

    public function formatArray($string, $array){
        if( count($array) > 0 ){
            return vsprintf($string, $array);
        }
        return $string;
    }

    public function forJS($string){
        return str_replace('\n', '', trim($string));
    }

}