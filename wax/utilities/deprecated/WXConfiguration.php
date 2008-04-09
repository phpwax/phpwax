<?php
/**
 * 	@package PHP-Wax
 */

/**
 *
 * @package PHP-Wax
 *
 *  Exposes the application configuration to other classes.
 *  
 *  
 *  The default method is to use load_yaml but since this only returns an array
 *  then it can easily be replaced with other methods.
 *  
 *  This is a Singleton object which once initialised cannot be duplicated, 
 *  the set() method allows infinite possibilites to alter the runtime
 *  environment, either by loading another config file or overwriting via php.
 *
 *  @deprecated
 *
 */
class WXConfiguration extends Config {}

?>