<?php

namespace Wax\Core;

class FrameworkLoader extends Loader {
  
  public $constants =  array(
     'WAX_START_TIME' => array('function'=>'microtime', 'params'=>true),
     'WAX_START_MEMORY' => array('function'=>'memory_get_usage')
  );
  


}