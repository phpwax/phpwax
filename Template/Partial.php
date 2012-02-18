<?php
namespace Wax\Template;

/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class Partial {
  	
  public $path;
  public $format;
  public $data;
  public $render = true;
  public $output = "";
  public $routed_controller = false;
	
	public function __construct($path, $data = array(), $format="html") {
	  $this->path = $path;
    $this->data = $data;
    $this->format = $format;
    $this->routed_controller = WaxUrl::route_controller($this->path);
	}
	
	public function render() {
	  if($this->render) {
		  if($this->data instanceof WaxTemplate) $this->template_partial();
		  else $this->standalone_partial();
	  }
	  return $this->output;
	}
	
	public function template_partial() {
	  $controller = $this->routed_controller ? $this->routed_controller : $this->data->controller;

	  $view= new WaxTemplate();
	  $view->add_path(VIEW_DIR.$controller."/".$this->path);
	  $view->add_path(VIEW_DIR.$this->path);
	
	  foreach($this->data->template_paths as $pathdir) {
	    $view->add_path(substr($pathdir,0,strrpos($pathdir, "/"))."/".$this->path);
	  }	
    if(property_exists($this, "data")) foreach($this->data as $var=>$val) if(!$view->{$var}) $view->{$var}=$val;
		$this->output = $view->parse($this->format, "partial");
	}
	
	public function standalone_partial() {
	  if(!$this->routed_controller && WaxUrl::$params["controller"]) $controller = WaxUrl::$params["controller"];
    else if(!$this->routed_controller) $controller = WaxUrl::$default_controller;
    else $controller = $this->routed_controller;
    
    $delegate = Inflections::slashcamelize($controller, true) . "Controller";
    $p_controller = new $delegate;        
    $p_controller->controller = $controller;
    $p_controller->use_layout = false;
    $p_controller->use_format = $this->format;
        
    
    if(strpos($this->path, "/")) {
      $partial = substr($this->path, strrpos($this->path, "/")+1);
    	$path = substr($this->path, 0, strrpos($this->path, "/")+1);
    	$path = $path.$partial;
    } else $partial = $this->path;
    
    
    if(is_array($this->data)) foreach($this->data as $var=>$val) $p_controller->{$var}=$val;

    

    $p_controller->use_view = $partial;
    
    if($p_controller->is_public_method($p_controller, $partial)) $p_controller->{$partial}(); 		
		$this->output = $p_controller->render_view();
	}
	
		
	



}
