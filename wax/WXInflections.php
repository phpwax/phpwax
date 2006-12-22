<?php
/**
 * Provides standard string inflections
 *
 * @author Ross Riley
 * @version $Id$
 * @package waxphp
 **/
class WXInflections 
{
  
  public function camelize($underscored_word, $upper_first=false) {
    $camel = '_' . str_replace('_', ' ', strtolower($underscored_word));
    $camel = ltrim(str_replace(' ', '', ucwords($camel)), '_');
    if($upper_first) return ucfirst($camel);
    return $camel;
  }
  
  public function capitalize($word) {
    return ucwords($word);
  }
  
  public function dasherize($underscored_word) {
    $dashed = str_replace('_', '-', strtolower($underscored_word));
    $dashed = str_replace(' ', '-', strtolower($dashed));
    return $dashed;
  }
  
  public function humanize($underscored_word) {
    $dashed = $this->undasherize($underscored_word);
    $dashed = str_replace('_', ' ', strtolower($underscored_word));
    return ucfirst($dashed);
  }
  
  public function underscore($camel_word) {
    $underscore = strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $camel_word));
    return $underscore;
  }
  
  public function slashify($camel_word) {
    $slash = strtolower(preg_replace('/([a-z])([A-Z])/', "$1/$2", $camel_word));
    return $slash;
  }
  
  public function slashcamelize($slash_word, $upper_first=false) {
    $camel = '/' . str_replace('/', ' ', strtolower($slash_word));
    $camel = ltrim(str_replace(' ', '', ucwords($camel)), '/');
    if($upper_first) return ucfirst($camel);
    return $camel;
  }

	public function truncate($substring, $max=50, $rep = '...') {
		if(strlen($substring) < 1) $string = $rep;
	    else $string = $substring;
		$leave = $max - strlen ($rep);
		if(strlen($string) > $max) return substr_replace($string, $rep, $leave);
		  else return $string;
	}
	
	public function undasherize($dashed_word) {
      $undashed = str_replace('-', ' ', strtolower($dashed_word));
      return $undashed;
  }

  public function humanize_undasherize($word){
      $parsed = $this->undasherize($this->humanize($word));
      return $parsed;
  }
	
}

?>