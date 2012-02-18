<?php
namespace Wax\Template;

/**
 * WaxPartial Class. Enables inline rendering of partials.
 *
 * @package default
 */

class PartialHelper extends Helper {
    
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
    $partial = new WaxPartial($path, $extra_vals, $format);
    WaxEvent::run("wax.partial", $partial);
    $res =  $partial->render();
    WaxEvent::run("wax.partial_render", $partial);
    return $res;
  }
  
  
  

  
}

Wax::register_helper_methods("WaxPartialHelper", array("partial"));
