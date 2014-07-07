<?php
namespace Unipa;

/**
 * Unipa Utils - Universal Passport
 * Used and controled by Kinki University.
 * Utilities for Unipa and sub classes.
 * 
 * @category   service
 * @package    Unipa
 * @author     Jinbe <jinbe@imokawaya.com>
 * @copyright  2014 The Authors
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @version    Release: 1.0.0
 * @link       http://bost.jp/
 * @since      Class available since Release 1.0.0
 */

class UnipaUtils {
    
    /**
     * Check data type: function
     * 
     * @param  mixed
     * @return bool
     */
    public static function is_function($callback) {
        return is_object($callback) && $callback instanceof \Closure;
    }
    
    
    /**
     * Convert unicode character point(hex) to decoded string.
     * This function can convert only one character, so cannnot convert string.
     * 
     * @param  string $point  Set hex as string, for example, '2000b'.
     * @return string         Converted char
     */
    public static function convertUnicodePointToString($point) {
        return mb_convert_encoding(pack("H*", str_repeat('0', 8 - strlen($point)).$point), 'UTF-8', 'UTF-32BE');
    }
    
    
    /**
     * Convert unicode character points(hex) to decoded string.
     * This function convert html escaped string, for example, '&#12469;&#12452;&#12474;'.
     * If you need to convert a point, use $this->onvertUnicodePointToString().
     * 
     * @param  string $str   String
     * @return string        Converted string.
     */
    public static function convertUnicodePointsToString($str) {
        if(!$str) return $str;
        if(preg_match_all("/&#([xX]?[0-9a-fA-F]{1,7});/", $str, $m)){
            foreach(array_unique($m[1]) as $i => $c){
                $c_hex = strpos($c, 'x') === false ? base_convert($c, 10, 16) : substr($c, 1);
                $str = str_replace("&#{$c};", $this->convertUnicodePointToString($c_hex), $str);
            }
        }
        return $str;
    }
    
    
    /**
     * Original triming function
     * 
     * @param  string $str
     * @return string
     */
    public static function space_trim($str) {
        $str = preg_replace('/^[ 　\n]+/u', '', $str);
        $str = preg_replace('/[ 　\n]+$/u', '', $str);
        return $str;
    }
}

?>