<?php

namespace Core\Model\Utils;

class DateUtils {

    const FORMAT_DATE_DB = 'Y-m-d';
    const FORMAT_DATE_USER = 'd/m/Y';
    const FORMAT_TIMESTAMP_DB = 'Y-m-d H:i:s';
    const FORMAT_TIMESTAMP_USER = 'H:i:s d/m/Y';

    /**
     * transforms a string from Y-m-d to d/m/Y
     * @param string $string    date to be formatted
     * @return null|string      date formatted or null
     */
    public static function userDate($string){
        $value = self::format(self::FORMAT_DATE_DB, self::FORMAT_DATE_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    /**
     * transforms a string from d/m/Y to Y-m-d
     * @param string $string    date to be formatted
     * @return null|string      date formatted or null
     */
    public static function databaseDate($string){
        $value = self::format(self::FORMAT_DATE_USER, self::FORMAT_DATE_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    /**
     * transforms a string from Y-m-d H:i:s to H:i:s d/m/Y
     * @param string $string    date to be formatted
     * @return null|string      date formatted or null
     */
    public static function userTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_DB, self::FORMAT_TIMESTAMP_USER, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    /**
     * transforms a string from H:i:s d/m/Y to Y-m-d H:i:s
     * @param string $string    date to be formatted
     * @return null|string      date formatted or null
     */
    public static function databaseTimestamp($string){
        $value = self::format(self::FORMAT_TIMESTAMP_USER, self::FORMAT_TIMESTAMP_DB, $string);
        if( $value )
            return $value;
        else
            return null;
    }

    /**
     * transforms a string from desired format to desired format
     * @param string $from      initial format of $date string
     * @param string $to        desired final format
     * @param string $date      date to be formatted
     * @return bool|string      date formatted or false
     */
    public static function format($from, $to, $date){
        if( !empty($date) && is_string($date) ){
            try{
                $dateTime = \DateTime::createFromFormat($from, $date);
                return $dateTime->format($to);
            }catch(\Exception $e){
                return false;
            }
        }
        return false;
    }

    /**
     * time interval between two dates
     * @param \DateTime     $startDate
     * @param \DateTime     $endDate
     * @return \DateInterval
     */
    public static function getTimeInterval($startDate, $endDate){
        $interval = $startDate->diff($endDate);
        return self::checkInterval($startDate, $endDate, $interval);
    }

    /**
     * minutes between two dates
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return integer
     */
    public static function minutesBetween($startDate, $endDate){
        $interval = self::getTimeInterval($startDate, $endDate);

        $minutes = $interval->d * 24 * 60;
        $minutes += $interval->h * 60;
        $minutes += $interval->i;

        return $minutes;
    }

    /**
     * time between two dates
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return string                   formatted as hours:min
     */
    public static function timeBetween($startDate, $endDate){
        if( $startDate < $endDate ){
            $interval = self::getTimeInterval($startDate, $endDate);

            $hours = $interval->y * 8760;
            $hours += $interval->m * 1440;
            $hours += $interval->d * 24;
            $hours += $interval->h;

            $minutes = substr('0'.$interval->i, -2);
            return $hours . ':' . $minutes;
        }
        return '00:00';
    }

    /**
     * time between two dates
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param boolean $fullDescription
     * @return string                       "'y' years 'm' months 'd' days 'h' hours 'm' minutes 's' seconds" if $fullDescription = true, 'y' if false
     */
    public static function describeTimeBetween($startDate, $endDate, $fullDescription = true){
        $interval = self::getTimeInterval($startDate, $endDate);

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

    /**
     * check summer time zone
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param \DateInterval $interval
     * @return \DateInterval
     */
    private static function checkInterval($startDate, $endDate, $interval){
        if( !date('I', $startDate->getTimestamp()) && date('I', $endDate->getTimestamp()) ){
            $interval->h--;
        }
        if( date('I', $startDate->getTimestamp()) && !date('I', $endDate->getTimestamp()) ){
            $interval->h++;
        }
        return $interval;
    }

    /**
     * calculate age of a date
     * @param string $date
     * @param string $format
     * @return int
     */
    public static function age($date, $format = self::FORMAT_DATE_USER){
        return \DateTime::createFromFormat($format, $date)
            ->diff(new \DateTime())
            ->y;
    }

}