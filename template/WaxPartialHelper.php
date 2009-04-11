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
   * To inherit an existing view use this method: eg.....
   * 
   *  <?=partial("mypartial", $this);?>
   *
   * Alternate syntax allows standalone execution that runs the partialname() method
   *  <?=partial("mypartial")?>
   *
   * @param string $path 
   * @param array $extra_vals 
   * @param string $format 
   */
  
  public function partial($path, $extra_vals=array(), $format="html") {
    ob_start();
		$controller = WaxUrl::route_controller($path);
		$cache = new WaxCache($_SERVER['HTTP_HOST'].md5($path.$_SERVER['REQUEST_URI'].serialize($_GET)).'.partial');
		if(count($_POST)) $cache->expire();
		if(Config::get('partial_cache') && !substr_count($path, "admin") && !substr_count(strtolower($controller), "admin") && $cache->valid()){			
			$partial= $cache->get();
		}else if($extra_vals instanceof WaxTemplate) {
		  foreach($extra_vals as $var=>$val) $this->{$var}=$val;
		  $view= new WXTemplate();
  		$view->add_path(VIEW_DIR.$path);
      foreach($this as $var=>$val) {
        if(!$view->{$var}) $view->{$var}=$val;
      }
  		$view->add_path(VIEW_DIR.$view->controller."/".$path);
  		$partial = $view->parse($format);
	  } else {
      if(!$controller) $controller = WaxUrl::$default_controller;
      $delegate = Inflections::slashcamelize($controller, true);
      $delegate .="Controller";
      $delegate_controller = new $delegate;
      $delegate_controller->controller = $controller;
  		$partial = $delegate_controller->execute_partial($path);
  	}
		if(Config::get('partial_cache') && !substr_count($controller, "admin") ) $cache->set($partial);
    echo $partial;
    ob_end_flush();
  }
  
  

  
}