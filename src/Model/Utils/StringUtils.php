<?php

namespace Core\Model\Utils;

class StringUtils {

    /**
     * checks if a string starts with a substring
     * @param string $haystack     string
     * @param string $needle       substring
     * @return bool
     */
    public static function startsWidth($haystack, $needle){
        return strpos($haystack, $needle) === 0;
    }

    /**
     * checks if a string ends with a substring
     * @param string $haystack     string
     * @param string $needle       substring
     * @return bool
     */
    public static function endsWidth($haystack, $needle){
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    /**
     * normalize string
     * @param string $string
     * @return string
     */
    public static function normalize($string){
        return trim(strtolower(self::removeAccents($string)));
    }

    /**
     * remove accents and special characters. For example: "Bàrbara Gavaldà" will be "barbara-gavalda"
     * @param string $string
     * @param boolean $toLower
     * @return string
     */
    public static function removeSpecialCharacters($string = '', $toLower = true){
        $string = trim($string);
        if( $toLower ){
            $string = mb_strtolower($string);
        }
        $string = str_replace(' ', '-', $string);
        $string = self::removeAccents($string);
        return preg_replace('/[^A-Za-z0-9\-_.]/', '', $string);
    }

    /**
     * replace any accent with the same letter without accent
     * @param string $str
     * @return string
     */
    public static function removeAccents($str) {
        $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
        $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
        return str_replace($a, $b, $str);
    }

    public static function replaceAccents($str){
        $a = array('À','Ä','Á','È','Ë','É','Ì','Ï','Í','Ò','Ö','Ó','Ù','Ü','Ú','à','ä','á','è','ë','é','ì','ï','í','ò','ö','ó','ù','ü','ú','¡','!','¿','?');
        $b = array(
            htmlentities('À',ENT_QUOTES,"UTF-8"),
            htmlentities('Ä',ENT_QUOTES,"UTF-8"),
            htmlentities('Á',ENT_QUOTES,"UTF-8"),
            htmlentities('È',ENT_QUOTES,"UTF-8"),
            htmlentities('Ë',ENT_QUOTES,"UTF-8"),
            htmlentities('É',ENT_QUOTES,"UTF-8"),
            htmlentities('Ì',ENT_QUOTES,"UTF-8"),
            htmlentities('Ï',ENT_QUOTES,"UTF-8"),
            htmlentities('Í',ENT_QUOTES,"UTF-8"),
            htmlentities('Ò',ENT_QUOTES,"UTF-8"),
            htmlentities('Ö',ENT_QUOTES,"UTF-8"),
            htmlentities('Ó',ENT_QUOTES,"UTF-8"),
            htmlentities('Ù',ENT_QUOTES,"UTF-8"),
            htmlentities('Ü',ENT_QUOTES,"UTF-8"),
            htmlentities('Ú',ENT_QUOTES,"UTF-8"),
            htmlentities('à',ENT_QUOTES,"UTF-8"),
            htmlentities('ä',ENT_QUOTES,"UTF-8"),
            htmlentities('á',ENT_QUOTES,"UTF-8"),
            htmlentities('è',ENT_QUOTES,"UTF-8"),
            htmlentities('ë',ENT_QUOTES,"UTF-8"),
            htmlentities('é',ENT_QUOTES,"UTF-8"),
            htmlentities('ì',ENT_QUOTES,"UTF-8"),
            htmlentities('ï',ENT_QUOTES,"UTF-8"),
            htmlentities('í',ENT_QUOTES,"UTF-8"),
            htmlentities('ò',ENT_QUOTES,"UTF-8"),
            htmlentities('ö',ENT_QUOTES,"UTF-8"),
            htmlentities('ó',ENT_QUOTES,"UTF-8"),
            htmlentities('ù',ENT_QUOTES,"UTF-8"),
            htmlentities('ü',ENT_QUOTES,"UTF-8"),
            htmlentities('ú',ENT_QUOTES,"UTF-8"),
            htmlentities('¡',ENT_QUOTES,"UTF-8"),
            htmlentities('!',ENT_QUOTES,"UTF-8"),
            htmlentities('¿',ENT_QUOTES,"UTF-8"),
            htmlentities('?',ENT_QUOTES,"UTF-8")
        );
        return str_replace($a, $b, $str);
    }

    /**
     * cuts a string that might contain HTML tags
     * @param string $text          HTML string
     * @param integer $length       final length of the string
     * @param string $ending        how should end the string if its finally cutted
     * @param bool $exact           words can be cut in half or not
     * @param bool $considerHtml    has HTML ot not
     * @return string
     */
    public static function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) {
        if( !$text ){
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
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length+$content_length> $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1]+1-$entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if($total_length>= $length) {
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
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    /**
     * prepares a youtube URL to be inserted on a iFrame
     * @param string $url
     * @return string
     */
    public static function checkEmbedYoutube($url){
        $url = str_replace('youtu.be', 'youtube.com', $url);
        if( strpos($url, 'embed') === false ){
            $url = str_replace('youtube.com/', 'youtube.com/embed/', $url);
        }
        return $url;
    }

    /**
     * format a number to be user friendly
     * @param float $value              value to be formated
     * @param integer $decimals         how many decimals should have
     * @param string $thousandsSep      character that separates thousands
     * @param string $decPoint          character that separates decimals
     * @param string $currency          currency
     * @return string
     */
    public static function formatPrice($value, $decimals = 2, $thousandsSep = '.', $decPoint = ',', $currency = '&euro;'){
        $value = str_replace(',', '.', $value);
        return number_format($value, $decimals, $decPoint, $thousandsSep) . $currency;
    }

    /**
     * validate spanish NIF and CIF format
     * @param string $string
     * @return bool
     */
    public static function validateNifCif($string){
        return self::validateCif($string) || self::validateNif($string);
    }

    /**
     * validate spanish CIF format
     * @param string $cif
     * @return bool
     */
    public static function validateCif($cif){
        if (strlen($cif) < 9)
            return false;
        if ($cif[0] == "(" && $cif[strlen($cif) - 1] == ")") {
            return true;
        }
        $cif = strtoupper($cif);
        $cif_codes = 'JABCDEFGHI';
        
        $sum = (string)self::getCifSum($cif);
        $n = (10 - substr($sum, -1)) % 10;
        
        if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $cif)) {
            if (in_array($cif[0], array('A', 'B', 'E', 'H'))) {
                // Numerico
                return ($cif[8] == $n);
            } elseif (in_array($cif[0], array('K', 'P', 'Q', 'S'))) {
                // Letras
                return ($cif[8] == $cif_codes[$n]);
            } else {
                // Alfanumérico
                if (is_numeric($cif[8])) {
                    return ($cif[8] == $n);
                } else {
                    return ($cif[8] == $cif_codes[$n]);
                }
            }
        }
        
        return false;
    }

    /**
     * validate spanish NIF format
     * @param $nif
     * @return bool
     */
    public static function validateNif($nif){
        if (strlen($nif) < 9)
            return false;
        if ($nif[0] == "(" && $nif[strlen($nif) - 1] == ")") {
            return true;
        }
        $nif = strtoupper($nif);
        $nif_codes = 'TRWAGMYFPDXBNJZSQVHLCKE';
        
        $sum = (string)self::getCifSum($nif);
        $n = 10 - substr($sum, -1);
        
        if (preg_match('/^[0-9]{8}[A-Z]{1}$/', $nif)) {
            // DNIs
            $num = substr($nif, 0, 8);
            
            return ($nif[8] == $nif_codes[$num % 23]);
        } elseif (preg_match('/^[XYZ][0-9]{7}[A-Z]{1}$/', $nif)) {
            // NIEs normales
            $tmp = substr($nif, 1, 7);
            $tmp = strtr(substr($nif, 0, 1), 'XYZ', '012') . $tmp;
            
            return ($nif[8] == $nif_codes[$tmp % 23]);
        } elseif (preg_match('/^[KLM]{1}/', $nif)) {
            // NIFs especiales
            return ($nif[8] == chr($n + 64));
        } elseif (preg_match('/^[T]{1}[A-Z0-9]{8}$/', $nif)) {
            // NIE extraño
            return true;
        }
        
        return false;
    }

    private static function getCifSum($cif){
        $sum = $cif[2] + $cif[4] + $cif[6];
        for ($i = 1; $i < 8; $i += 2) {
            $tmp = (string)(2 * $cif[$i]);
            $tmp = $tmp[0] + ((strlen($tmp) == 2) ? $tmp[1] : 0);
            $sum += $tmp;
        }
        return $sum;
    }
    
    /**
     * validate if string is a MAC address
     * @param string $string to check
     *
     * @return bool true if is a MAC, false if it isn't
     */
    public static function validateMAC($string){
        if (isset($string) && strcmp($string,"") != 0 ){
            if (preg_match('/^[0-9a-fA-F]{2}(?=([-:;. ]?))(?:\\1[0-9a-fA-F]{2}){5}$/', $string)) {
                return true;
            }
        }
        return false;
    }

    /**
     * generates an alphanumeric token of the desired length
     * @param integer $length   length of the token
     * @return string           generated token
     */
    public static function generateToken($length){
        $permittedChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';
        for($i=0; $i<$length; $i++){
            $token .= $permittedChars[ rand(0, strlen($permittedChars)-1) ];
        }
        return $token;
    }

    /**
     * Gets the string between two specific characters
     * @param string $string    with the text to search in.
     * @param string $start     that represents the start.
     * @param string $end       that represents the end.
     * @return string           between the start and the end.
     */
    public static function getStringBetween($string, $start, $end){
        $string = ' '.$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }

    /**
     * check is string has URL format
     * @param string $string    string to be checked
     * @return bool|string      false or URL with HTTP
     */
    public static function validateURL($string){
        if( !self::startsWidth($string, 'http') ){
            $string = 'http://' . $string;
        }
        $url = parse_url($string);
        if( !array_key_exists('host', $url) ){
            return false;
        }

        $scheme   = isset($url['scheme']) ? $url['scheme'] . '://' : '';
        $host     = isset($url['host']) ? $url['host'] : '';
        $port     = isset($url['port']) ? ':' . $url['port'] : '';
        $user     = isset($url['user']) ? $url['user'] : '';
        $pass     = isset($url['pass']) ? ':' . $url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($url['path']) ? $url['path'] : '';
        $query    = isset($url['query']) ? '?' . $url['query'] : '';
        $fragment = isset($url['fragment']) ? '#' . $url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }
    
    /**
    * Get initials of a sentence
    * @param string $string
    * @return string
    */
   public static function acronym($string){
       $words = explode(' ', $string);

       $acronym = '';
       foreach($words as $w){
           if( strlen($w) > 0 ){
               $acronym .= $w[0];
           }
       }
       return mb_strtoupper($acronym);
   }

    public static function mb_str_pad( $input, $pad_length, $pad_string = ' ', $pad_type = STR_PAD_RIGHT)
    {
        $diff = strlen( $input ) - mb_strlen( $input );
        return str_pad( $input, $pad_length + $diff, $pad_string, $pad_type );
    }


}
