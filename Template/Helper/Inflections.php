<?php
namespace Wax\Template\Helper;

/**
 * Provides standard string inflections to manipulate text.
 *
 * @author Ross Riley
 * @package PHP-Wax
 **/
trait Inflection {
  
  
  public static function camelize($underscored_word, $upper_first=false) {
    $camel = '_' . str_replace('_', ' ', strtolower($underscored_word));
    $camel = ltrim(str_replace(' ', '', ucwords($camel)), '_');
    if($upper_first) return ucfirst($camel);
    return $camel;
  }
  
  public static function capitalize($word) {
    return ucwords($word);
  }
  
  public static function dasherize($underscored_word) {
    $dashed = str_replace('_', '-', strtolower($underscored_word));
    $dashed = str_replace(' ', '-', strtolower($dashed));
    return $dashed;
  }
  
  public static function humanize($underscored_word) {
    $dashed = self::undasherize($underscored_word);
    $dashed = str_replace('_', ' ', strtolower($dashed));
    return ucfirst($dashed);
  }
  
  public static function underscore($camel_word) {
    $underscore = strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $camel_word));
    $underscore = str_replace("-", "_", $underscore);
    $underscore = str_replace(' ', '_', $underscore);
    return $underscore;
  }
  
  public static function slashify($camel_word) {
    $slash = strtolower(preg_replace('/([a-z])([A-Z])/', "$1/$2", $camel_word));
    return $slash;
  }
  
  public static function slashcamelize($slash_word, $upper_first=false) {
    $camel = '/' . str_replace('/', ' ', strtolower($slash_word));
    $camel = ltrim(str_replace(' ', '', ucwords($camel)), '/');
    if($upper_first) return ucfirst($camel);
    return $camel;
  }

	public static function truncate($substring, $max=50, $rep = '...') {
		if(strlen($substring) < 1) $string = $rep;
	    else $string = $substring;
		$leave = $max - strlen ($rep);
		if(strlen($string) > $max) return substr_replace($string, $rep, $leave);
		  else return $string;
	}
	
	public static function undasherize($dashed_word) {
      $undashed = str_replace('-', '_', strtolower($dashed_word));
      return $undashed;
  }

  public static function humanize_undasherize($word){
      $parsed = self::undasherize(self::humanize($word));
      return $parsed;
  }
  public static function to_url($words) {
    $words = preg_replace('/([^a-z0-9A-Z\s\p{L}])/u', "", $words);
    while(strpos($words, "  ")) $words = str_replace("  ", " ", $words);
    return self::dasherize($words);
  }
	
}

class Inflections {

  use Inflection;
  
}

