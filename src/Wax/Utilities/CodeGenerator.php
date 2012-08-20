<?php
namespace Wax\Utilities;
/**
 *  The class can be used for code generation.
 *  Usually delegated to from one of the command line scripts.  
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
class CodeGenerator {  
  
  
  static public function helper_wrappers($class, $methods) {
    $helper_code = "";
    foreach($methods as $method) {
      $code ="function $method(){return call_user_func_array(array('$class', '$method'), func_get_args());}";
      if(!function_exists($method)) $helper_code .= $code;
    }
      eval($helper_code);
  }
  
  static public function alias($from, $to) {
    eval("function $from() { call_user_func_array($to, func_get_args()); }");
  }
  
  
  
}