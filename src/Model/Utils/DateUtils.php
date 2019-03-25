<?php

namespace Core\Model\Utils;

class DateUtils {

    const FORMAT_DATE_DB = 'Y-m-d';
    const FORMAT_DATE_USER = 'd/m/Y';
    const FORMAT_TIMESTAMP_DB = 'Y-m-d H:i:s';
    const FORMAT_TIMESTAMP_USER = 'H:i:s d/m/Y';

    public static function userDate($string){
        $value = self::format(self::FORMAT_DATE_DB, self::FORMAT_DATE_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function databaseDate($string){
        $value = self::format(self::FORMAT_DATE_USER, self::FORMAT_DATE_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function userTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_DB, self::FORMAT_TIMESTAMP_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function databaseTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_USER, self::FORMAT_TIMESTAMP_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    public static function format($from, $to, $string){
        if( !empty($string) && is_string($string) ){
            try{
                $date = \DateTime::createFromFormat($from, $string);
                return $date->format($to);
            }catch(\Exception $e){
                return false;
            }
        }
        return false;
    }

    /**
     * minutes between two dates
     * @param $startDate    \DateTime
     * @param $endDate      \DateTime
     * @return int
     */
    public static function minutesBetween($startDate, $endDate){
        $interval = $startDate->diff($endDate);
        if( !date('I', $startDate->getTimestamp()) && date('I', $endDate->getTimestamp()) ){
            $interval->h--;
        }
        if( date('I', $startDate->getTimestamp()) && !date('I', $endDate->getTimestamp()) ){
            $interval->h++;
        }

        $minutes = $interval->d * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;

        return $minutes;
    }

    /**
     * time between two dates
     * @param $startDate    \DateTime
     * @param $endDate      \DateTime
     * @return string       hours:min
     */
    public static function timeBetween($startDate, $endDate){
        if( $startDate < $endDate ){
            $interval = $startDate->diff($endDate);

            $hours = $interval->y * 8760;
            $hours += $interval->m * 1440;
            $hours += $interval->d * 24;
            $hours += $interval->h;

            $hours = substr('0'.$hours, -2);
            $minutes = substr('0'.$interval->i, -2);
            return $hours . ':' . $minutes;
        }
        return '00:00';
    }

    /**
     * time between two dates
     * @param $startDate            \DateTime
     * @param $endDate              \DateTime
     * @param $fullDescription      boolean
     * @return string       "'y' years 'm' months 'd' days 'h' hours 'm' minutes 's' seconds"
     */
    public static function describeTimeBetween($startDate, $endDate, $fullDescription = true){
        $interval = $startDate->diff($endDate);

        $time = array();
        self::addTime($interval->y, 'año', 'años', $time);
        self::addTime($interval->m, 'mes', 'meses', $time);
        self::addTime($interval->d, 'día', 'días', $time);
        self::addTime($interval->h, 'hora', 'horas', $time);
        self::addTime($interval->i, 'minuto', 'minutos', $time);
        self::addTime($interval->s, 'segundo', 'segundos', $time);

        if( $fullDescription ){
            return implode(' ', $time);
        }else{
            return $time[0];
        }
    }

    private function addTime($value, $singular, $plural, &$time){
        if( $value == 1 ){
            $time[] = '1 ' . gettext($singular);
        } elseif ( $value > 1 ){
            $time[] = $value . ' ' . gettext($plural);
        }
    }

}