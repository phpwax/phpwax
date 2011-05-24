<?php
Class Session{
  public static $default_session;
  
  public static function __callStatic($name, $arguments){
    if(!self::$default_session) self::$default_session = new WaxSession;
    return call_user_func_array(array(self::$default_session, $name), $arguments);
  }
}
?>