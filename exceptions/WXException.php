<?php
/**
 *
 * @package PHP-Wax
 * @author Ross Riley
 **/
 
/**
 *  Base exception class.
 *  Handling will depend upon the environment, in development mode errors are trapped and reported to the screen
 *  In production mode errors are handled quietly and optionally emailed or logged. 
 */
class WXException extends WaxException{}
