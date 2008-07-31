<?php
/**
 * WaxPartial Class. Enables inline rendering of partials.
 *
 * @package default
 */

class WaxPartialHelper extends WXHelpers {
    
  /**
   * Partial Helper Function
   * Renders a partial from path $path into the current view
   * Values are passed in second value eg.....
   * 
   *  <?=partial("_mypartial", $this);?>
   *
   * @param string $path 
   * @param array $extra_vals 
   * @param string $format 
   */
  
  public function partial($path, $extra_vals=array(), $format="html") {
		foreach($extra_vals as $var=>$val) $this->{$var}=$val;
    ob_start();
    $view= new WXTemplate();
		$view->add_path(VIEW_DIR.$path);
    foreach($this as $var=>$val) {
      if(!$view->{$var}) $view->{$var}=$val;
    }
		echo $view->parse($format);
    ob_end_flush();
  }
  
  
  

  
}