<?php

namespace Core\Model\Utils;

use DateInterval;
use DateTime;
use Exception;
use IntlDateFormatter;
use Locale;

class DateUtils
{

    const string FORMAT_DATE_DB        = 'Y-m-d';
    const string FORMAT_DATE_USER      = 'd/m/Y';
    const string FORMAT_TIMESTAMP_DB   = 'Y-m-d H:i:s';
    const string FORMAT_TIMESTAMP_USER = 'H:i:s d/m/Y';

    /**
     * transforms a string from Y-m-d to d/m/Y
     *
     * @param string $string date to be formatted
     *
     * @return ?string      date formatted or null
     */
    public static function userDate(string $string): ?string
    {
        $value = self::format(self::FORMAT_DATE_DB, self::FORMAT_DATE_USER, $string);
        if ($value) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * transforms a string from d/m/Y to Y-m-d
     *
     * @param string $string date to be formatted
     *
     * @return ?string      date formatted or null
     */
    public static function databaseDate(string $string): ?string
    {
        $value = self::format(self::FORMAT_DATE_USER, self::FORMAT_DATE_DB, $string);
        if ($value) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * transforms a string from Y-m-d H:i:s to H:i:s d/m/Y
     *
     * @param string $string date to be formatted
     *
     * @return ?string      date formatted or null
     */
    public static function userTimestamp(string $string): ?string
    {
        $value = self::format(self::FORMAT_TIMESTAMP_DB, self::FORMAT_TIMESTAMP_USER, $string);
        if ($value) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * transforms a string from H:i:s d/m/Y to Y-m-d H:i:s
     *
     * @param string $string date to be formatted
     *
     * @return ?string      date formatted or null
     */
    public static function databaseTimestamp(string $string): ?string
    {
        $value = self::format(self::FORMAT_TIMESTAMP_USER, self::FORMAT_TIMESTAMP_DB, $string);
        if ($value) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * transforms a string from desired format to desired format
     *
     * @param string $from initial format of $date string
     * @param string $to   desired final format
     * @param string $date date to be formatted
     *
     * @return bool|string      date formatted or false
     */
    public static function format(string $from, string $to, string $date): bool|string
    {
        if (!empty($date)) {
            try {
                $dateTime = DateTime::createFromFormat($from, $date);
                if ($dateTime === false) {
                    // createFromFormat() returns false (doesn't throw) on a
                    // malformed date - calling ->format() on it would be a
                    // PHP Error, not an Exception, so the catch below never
                    // sees it
                    return false;
                }
                return $dateTime->format($to);
            } catch (Exception) {
                return false;
            }
        }
        return false;
    }

    /**
     * translate database date to localized date
     *
     * @param string $date date to be formatted
     * @param string $from initial format of $date string
     * @param string $to   desired final format
     *
     * @return string
     */
    public static function userLocale(string $date, string $from = DateUtils::FORMAT_DATE_DB, string $to = 'd MMMM, yyyy'): string
    {
        if (!empty($date)) {
            $date = DateTime::createFromFormat($from, $date);
            $f    = new IntlDateFormatter(Locale::getDefault());
            $f->setPattern($to);
            return $f->format($date);
        }
        return '';
    }

    /**
     * transform an amount of seconds to i:s format
     *
     * @param int    $seconds
     * @param string $format
     *
     * @return string
     */
    public static function formatSeconds(int $seconds, string $format = '%02d:%02d'): string
    {
        $minutes = floor($seconds / 60);
        $secs    = floor($seconds % 60);
        return sprintf($format, $minutes, $secs);
    }

    /**
     * time interval between two dates
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return DateInterval
     */
    public static function getTimeInterval(DateTime $startDate, DateTime $endDate): DateInterval
    {
        $interval = $startDate->diff($endDate);
        return self::checkInterval($startDate, $endDate, $interval);
    }

    /**
     * return time interval for database
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return array
     */
    public static function getDbInterval(DateTime $startDate, DateTime $endDate): array
    {
        $interval = self::getTimeInterval($startDate, $endDate);

        $intervalArray = array();
        if ($interval->y > 0) {
            $intervalArray[] = 'INTERVAL' . $interval->y . ' YEAR';
        }
        if ($interval->m > 0) {
            $intervalArray[] = 'INTERVAL ' . $interval->m . ' MONTH';
        }
        if ($interval->d > 0) {
            $intervalArray[] = 'INTERVAL ' . $interval->d . ' DAY';
        }
        if ($interval->h > 0) {
            $intervalArray[] = 'INTERVAL ' . $interval->h . ' HOUR';
        }
        if ($interval->i > 0) {
            $intervalArray[] = 'INTERVAL ' . $interval->i . ' MINUTE';
        }
        if ($interval->s > 0) {
            $intervalArray[] = 'INTERVAL ' . $interval->s . ' SECOND';
        }
        return $intervalArray;
    }

    /**
     * seconds in interval
     *
     * @param DateInterval $interval
     *
     * @return int
     */
    public static function intervalToSeconds(DateInterval $interval): int
    {
        $seconds = $interval->s + ($interval->i * 60) + ($interval->h * 3600);
        $seconds += ($interval->d * 86400) + ($interval->m * 2592000) + ($interval->y * 31536000);

        return $seconds;
    }

    /**
     * seconds between two dates
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return int
     */
    public static function secondsBetween(DateTime $startDate, DateTime $endDate): int
    {
        $interval = self::getTimeInterval($startDate, $endDate);
        return self::intervalToSeconds($interval);
    }

    /**
     * minutes between two dates
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return int
     */
    public static function minutesBetween(DateTime $startDate, DateTime $endDate): int
    {
        $start = strtotime($endDate->format(self::FORMAT_TIMESTAMP_DB));
        $end   = strtotime($startDate->format(self::FORMAT_TIMESTAMP_DB));
        // round(..., 2) preserved for its exact prior behavior even though
        // the 2 decimals never survived the (previously implicit) cast to
        // int - not changing the return type since that would change what
        // every caller actually receives
        return (int) round(abs($end - $start) / 60, 2);
    }

    public static function timeAgo(string $startString): string
    {
        $startDate = DateTime::createFromFormat(self::FORMAT_DATE_USER, $startString);
        $now       = new DateTime();
        $interval  = $startDate->diff($now);
        $interval  = self::checkInterval($startDate, $now, $interval);

        if ($interval->d == 0) {
            return _('hoy');
        } elseif ($interval->d < 7) {
            switch ($startDate->format('N')) {
                case 1:
                    return _('lunes');
                case 2:
                    return _('martes');
                case 3:
                    return _('miércoles');
                case 4:
                    return _('jueves');
                case 5:
                    return _('viernes');
                case 6:
                    return _('sábado');
                case 7:
                    return _('domingo');
            }
        }
        return $startString;
    }

    /**
     * time between two dates
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return string                   formatted as hours:min
     */
    public static function timeBetween(DateTime $startDate, DateTime $endDate): string
    {
        if ($startDate < $endDate) {
            $interval = self::getTimeInterval($startDate, $endDate);

            $hours = $interval->y * 8760;
            $hours += $interval->m * 1440;
            $hours += $interval->d * 24;
            $hours += $interval->h;

            $minutes = substr('0' . $interval->i, -2);
            return $hours . ':' . $minutes;
        }
        return '00:00';
    }

    /**
     * time between two dates
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param bool     $fullDescription
     *
     * @return string                       "'y' years 'm' months 'd' days 'h' hours 'm' minutes 's' seconds" if $fullDescription = true, 'y' if false
     */
    public static function describeTimeBetween(
        DateTime $startDate,
        DateTime $endDate,
        bool $fullDescription = true
    ): string {
        $interval = self::getTimeInterval($startDate, $endDate);

        $time = array();
        self::addTime($interval->y, 'año', 'años', $time);
        self::addTime($interval->m, 'mes', 'meses', $time);
        self::addTime($interval->d, 'día', 'días', $time);
        self::addTime($interval->h, 'hora', 'horas', $time);
        self::addTime($interval->i, 'minuto', 'minutos', $time);
        self::addTime($interval->s, 'segundo', 'segundos', $time);

        if ($fullDescription) {
            return implode(' ', $time);
        } else {
            return $time[0];
        }
    }

    private static function addTime(float $value, string $singular, string $plural, array &$time): void
    {
        if ($value == 1) {
            $time[] = '1 ' . _($singular);
        } elseif ($value > 1) {
            $time[] = $value . ' ' . _($plural);
        }
    }

    /**
     * check summer timezone
     *
     * @param DateTime     $startDate
     * @param DateTime     $endDate
     * @param DateInterval $interval
     *
     * @return DateInterval
     */
    public static function checkInterval(DateTime $startDate, DateTime $endDate, DateInterval $interval): DateInterval
    {
        if (!date('I', $startDate->getTimestamp()) && date('I', $endDate->getTimestamp())) {
            $interval->h--;
        }
        if (date('I', $startDate->getTimestamp()) && !date('I', $endDate->getTimestamp())) {
            $interval->h++;
        }
        return $interval;
    }

    /**
     * calculate age of a date
     *
     * @param string $date
     * @param string $format
     *
     * @return int
     */
    public static function age(string $date, string $format = self::FORMAT_DATE_USER): int
    {
        return DateTime::createFromFormat($format, $date)->diff(new DateTime())->y;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param          $variable             string
     *
     * @return int
     */
    public static function timeDifference(DateTime $startDate, DateTime $endDate, string $variable = 'y'): int
    {
        $interval = $startDate->diff($endDate);
        $interval = self::checkInterval($startDate, $endDate, $interval);

        return $interval->$variable;
    }

}