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
		$controller = WaxUrl::route_controller($path, $extra_vals);
		if($extra_vals instanceof WaxTemplate) {
		  if(!$controller) $controller = $extra_vals->controller;  		
		  $old_template_paths = $extra_vals->template_paths;
		  foreach($extra_vals as $var=>$val) $this->{$var}=$val;
		  $view= new WaxTemplate();
		  $view->add_path(VIEW_DIR.$controller."/".$path);
 		  $view->add_path(VIEW_DIR.$path);
 		foreach($old_template_paths as $pathdir) {
  		  $view->add_path(substr($pathdir,0,strrpos($pathdir, "/"))."/".$path);
  		}
  		
      foreach($this as $var=>$val) {
        if(!$view->{$var}) $view->{$var}=$val;
      }
  		$partial = $view->parse($format, "partial");
	  } else {
	    if(!$controller && WaxUrl::$params["controller"]) $controller = WaxUrl::$params["controller"];
      else if(!$controller) $controller = WaxUrl::$default_controller;
      $delegate = Inflections::slashcamelize($controller, true);
      $delegate .="Controller";
      $p_controller = new $delegate;
      $p_controller->controller = $controller;
      $p_controller->use_layout = false;
      $p_controller->use_format = $format;
      if(strpos($path, "/")) {
        $partial = substr($path, strrpos($path, "/")+1);
      	$path = substr($path, 0, strrpos($path, "/")+1);
      	$path = $path.$partial;
      } else $partial = $path;
      if(is_array($extra_vals)) foreach($extra_vals as $var=>$val) $p_controller->{$var}=$val;      	
      if($p_controller->is_public_method($p_controller, $partial)) $p_controller->{$partial}();
      $p_controller->use_view = $path;
  		$partial = $p_controller->render_view();
  	}
    return $partial;
  }
  
  
  

  
}
