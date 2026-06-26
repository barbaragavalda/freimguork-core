<?php

namespace Core\Model\Utils;

class NumberUtils
{

    public static function distance(float $lat1, float $long1, float $lat2, float $long2): ?float
    {
        if ($lat1 && $long1 && $lat2 && $long2) {
            return round(
                6371 * acos(
                    cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($long2) - deg2rad($long1)) + sin(
                        deg2rad($lat1)
                    ) * sin(deg2rad($lat2))
                ),
                2
            );
        }
        return null;
    }

    public static function formatMBytes(float $bytes, int $precision = 2): float
    {
        return self::formatBytes($bytes, $precision, 'MB');
    }

    public static function formatBytes(float $bytes, int $precision = 2, ?string $unit = null): float
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));

        $finalUnit = min($pow, count($units) - 1);
        if ($unit != null) {
            $finalUnit = array_search($unit, $units);
        }
        $pow = $finalUnit;

        $bytes /= pow(1024, $pow);
        return round($bytes, $precision);
    }

}