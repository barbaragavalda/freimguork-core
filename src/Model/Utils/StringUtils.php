<?php

namespace Core\Model\Utils;

class StringUtils
{

    public static function normalize(string $string): string
    {
        return trim(strtolower(self::removeAccents($string)));
    }

    /**
     * remove accents and special characters. For example: "Bàrbara Gavaldà" will be "barbara-gavalda"
     *
     * @param string $string
     * @param bool   $toLower
     * @param bool   $withPoint
     *
     * @return string
     */
    public static function removeSpecialCharacters(
        string $string = '',
        bool $toLower = true,
        bool $withPoint = true
    ): string {
        $string = trim($string);

        if ($toLower) {
            $string = mb_strtolower($string, 'UTF-8');
        }

        $string = self::removeAccents($string);
        $string = preg_replace('/\s+/u', '-', $string);
        $string = preg_replace($withPoint ? '/[^A-Za-z0-9._-]/' : '/[^A-Za-z0-9_-]/', '', $string);
        $string = preg_replace('/-+/', '-', $string);

        return trim($string, '-');
    }

    /**
     * replace any accent with the same letter without accent
     *
     * @param string $str
     *
     * @return string
     */
    public static function removeAccents(string $str): string
    {
        return transliterator_transliterate('Any-Latin; Latin-ASCII', $str);
    }

    public static function replaceAccents(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * cuts a string that might contain HTML tags
     *
     * @param string $text         HTML string
     * @param int    $length       final length of the string
     * @param string $ending       how should end the string if its finally cutted
     * @param bool   $exact        words can be cut in half or not
     * @param bool   $considerHtml has HTML ot not
     *
     * @return string
     */
    public static function truncateHtml(
        string $text,
        int $length = 100,
        string $ending = '...',
        bool $exact = false,
        bool $considerHtml = true
    ): string {
        if (!$text) {
            return '';
        }

        $open_tags = array();
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $truncate     = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match(
                        '/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is',
                        $line_matchings[1]
                    )) {
                        // do nothing
                        // if tag is a closing tag
                    } else {
                        if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                            // delete tag from $open_tags list
                            $pos = array_search($tag_matchings[1], $open_tags);
                            if ($pos !== false) {
                                unset($open_tags[ $pos ]);
                            }
                            // if tag is an opening tag
                        } else {
                            if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                                // add tag to the beginning of $open_tags list
                                array_unshift($open_tags, strtolower($tag_matchings[1]));
                            }
                        }
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(
                    preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2])
                );
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left            = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all(
                        '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i',
                        $line_matchings[2],
                        $entities,
                        PREG_OFFSET_CAPTURE
                    )) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum length is reached, so get off the loop
                    break;
                } else {
                    $truncate     .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurrence of a space...
            $spacepos = strrpos($truncate, ' ');
            // ...and cut the text in this position
            $truncate = substr($truncate, 0, $spacepos);
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    /**
     * prepares a YouTube URL to be inserted on a iFrame
     *
     * @param string $url
     *
     * @return string
     */
    public static function checkEmbedYoutube(string $url): string
    {
        $url = str_replace('youtu.be', 'youtube.com', $url);
        if (!str_contains($url, 'embed')) {
            $url = str_replace('youtube.com/', 'youtube.com/embed/', $url);
        }
        return $url;
    }

    /**
     * format a number to be user-friendly
     *
     * @param mixed  $value        value to be formated
     * @param int    $decimals     how many decimals should have
     * @param string $thousandsSep character that separates thousands
     * @param string $decPoint     character that separates decimals
     * @param string $currency     currency
     *
     * @return string
     */
    public static function formatPrice(
        mixed $value,
        int $decimals = 2,
        string $thousandsSep = '.',
        string $decPoint = ',',
        string $currency = '&euro;'
    ): string {
        if ($value) {
            if (is_numeric($value)) {
                $value = str_replace(',', '.', $value);
                return number_format($value, $decimals, $decPoint, $thousandsSep) . $currency;
            } else {
                return $value;
            }
        }
        return '';
    }

    /**
     * validate Spanish NIF and CIF format
     *
     * @param string $string
     *
     * @return bool
     */
    public static function validateNifCif(string $string): bool
    {
        return self::validateCif($string) || self::validateNif($string);
    }

    /**
     * validate Spanish CIF format
     *
     * @param string $cif
     *
     * @return bool
     */
    public static function validateCif(string $cif): bool
    {
        if (strlen($cif) < 9) {
            return false;
        }
        if ($cif[0] == "(" && $cif[ strlen($cif) - 1 ] == ")") {
            return true;
        }
        $cif       = strtoupper($cif);
        $cif_codes = 'JABCDEFGHI';

        $sum = (string) self::getCifSum($cif);
        $n   = (10 - substr($sum, -1)) % 10;

        if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $cif)) {
            if (in_array($cif[0], array('A', 'B', 'E', 'H'))) {
                // numeric
                return ($cif[8] == $n);
            } elseif (in_array($cif[0], array('K', 'P', 'Q', 'S'))) {
                // letters
                return ($cif[8] == $cif_codes[ $n ]);
            } else {
                // alphanumeric
                if (is_numeric($cif[8])) {
                    return ($cif[8] == $n);
                } else {
                    return ($cif[8] == $cif_codes[ $n ]);
                }
            }
        }

        return false;
    }

    /**
     * validate Spanish NIF format
     *
     * @param string $nif
     *
     * @return bool
     */
    public static function validateNif(string $nif): bool
    {
        if (strlen($nif) < 9) {
            return false;
        }
        if ($nif[0] == "(" && $nif[ strlen($nif) - 1 ] == ")") {
            return true;
        }
        $nif       = strtoupper($nif);
        $nif_codes = 'TRWAGMYFPDXBNJZSQVHLCKE';

        $sum = (string) self::getCifSum($nif);
        $n   = 10 - substr($sum, -1);

        if (preg_match('/^[0-9]{8}[A-Z]{1}$/', $nif)) {
            // DNIs
            $num = substr($nif, 0, 8);

            return ($nif[8] == $nif_codes[ $num % 23 ]);
        } elseif (preg_match('/^[XYZ][0-9]{7}[A-Z]{1}$/', $nif)) {
            // NIEs normales
            $tmp = substr($nif, 1, 7);
            $tmp = strtr(substr($nif, 0, 1), 'XYZ', '012') . $tmp;

            return ($nif[8] == $nif_codes[ $tmp % 23 ]);
        } elseif (preg_match('/^[KLM]{1}/', $nif)) {
            // NIFs especiales
            return ($nif[8] == chr($n + 64));
        } elseif (preg_match('/^[T]{1}[A-Z0-9]{8}$/', $nif)) {
            // NIE extraño
            return true;
        }

        return false;
    }

    private static function getCifSum(string $cif): int
    {
        $sum = $cif[2] + $cif[4] + $cif[6];
        for ($i = 1; $i < 8; $i += 2) {
            $tmp = (string) (2 * $cif[ $i ]);
            $tmp = $tmp[0] + ((strlen($tmp) == 2) ? $tmp[1] : 0);
            $sum += $tmp;
        }
        return $sum;
    }

    /**
     * validate if string is a MAC address
     *
     * @param string $string to check
     *
     * @return bool true if is a MAC, false if it isn't
     */
    public static function validateMAC(string $string): bool
    {
        if (strcmp($string, "") != 0) {
            if (preg_match('/^[0-9a-fA-F]{2}(?=([-:;. ]?))(?:\\1[0-9a-fA-F]{2}){5}$/', $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * generates an alphanumeric token of the desired length
     *
     * @param int $length length of the token
     *
     * @return string           generated token
     */
    public static function generateToken(int $length): string
    {
        $permittedChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token          = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $permittedChars[ rand(0, strlen($permittedChars) - 1) ];
        }
        return $token;
    }

    /**
     * Gets the string between two specific characters
     *
     * @param string $string with the text to search in.
     * @param string $start  that represents the start.
     * @param string $end    that represents the end.
     *
     * @return string           between the start and the end.
     */
    public static function getStringBetween(string $string, string $start, string $end): string
    {
        $string = ' ' . $string;
        $ini    = strpos($string, $start);
        if ($ini == 0) {
            return '';
        }
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    /**
     * check is string has URL format
     *
     * @param string $string string to be checked
     *
     * @return false|string      false or URL with HTTP
     */
    public static function validateURL(string $string): false|string
    {
        if (!str_starts_with($string, 'http')) {
            $string = 'http://' . $string;
        }
        $url = parse_url($string);
        if (!array_key_exists('host', $url)) {
            return false;
        }

        $scheme   = isset($url['scheme']) ? $url['scheme'] . '://' : '';
        $host     = $url['host'] ?? '';
        $port     = isset($url['port']) ? ':' . $url['port'] : '';
        $user     = $url['user'] ?? '';
        $pass     = isset($url['pass']) ? ':' . $url['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $url['path'] ?? '';
        $query    = isset($url['query']) ? '?' . $url['query'] : '';
        $fragment = isset($url['fragment']) ? '#' . $url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Get initials of a sentence
     *
     * @param string $string
     *
     * @return string
     */
    public static function acronym(string $string): string
    {
        $words = explode(' ', $string);

        $acronym = '';
        foreach ($words as $w) {
            if (strlen($w) > 0) {
                $acronym .= $w[0];
            }
        }
        return mb_strtoupper($acronym);
    }

    public static function mb_str_pad(
        string $input,
        int $pad_length,
        string $pad_string = ' ',
        int $pad_type = STR_PAD_RIGHT
    ): string {
        $diff = strlen($input) - mb_strlen($input);
        return str_pad($input, $pad_length + $diff, $pad_string, $pad_type);
    }

}
