<?php

namespace Core\View\Extension;

use Core\Model\Utils\StringUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Twig extends AbstractExtension{
    public function getFilters(){
        return [
            new TwigFilter('formatPrice', [$this, 'formatPrice']),
        ];
    }

    public function formatPrice($price, $decimals = 2, $thousandsSep = '.', $decPoint = ','){
        return StringUtils::formatPrice($price, $decimals, $thousandsSep, $decPoint);
    }

}