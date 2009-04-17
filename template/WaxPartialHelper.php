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
    if($extra_vals instanceof WaxTemplate) {
      $old_template_paths = $extra_vals->template_paths;
		  foreach($extra_vals as $var=>$val) $this->{$var}=$val;
		  $view= new WXTemplate();
		  if(count($extra_vals->plugins)>0) {
		    foreach($old_template_paths as $template) $view->add_path(str_replace($extra_vals->use_view, $path, $template));
  		}
  		$view->add_path(VIEW_DIR.$path);
      foreach($this as $var=>$val) {
        if(!$view->{$var}) $view->{$var}=$val;
      }
  		$view->add_path(VIEW_DIR.$view->controller."/".$path);
  		$partial = $view->parse($format);
	  } else {
      $controller = WaxUrl::route_controller($path);
      if(!$controller) $controller = WaxUrl::$default_controller;
      $delegate = Inflections::slashcamelize($controller, true);
      $delegate .="Controller";
      $delegate_controller = new $delegate;
      $delegate_controller->controller = $controller;
  		$partial = $delegate_controller->execute_partial($path);
  	}
    echo $partial;
    ob_end_flush();
  }
  
  

  
}