<?php
/**
 * 	@package wx.php.core
 */

/**
	* 	@package wx.php.core
  *   The absolute base class. This will instantiate all of the other 
  *   four base classes, ConfigBase, ModelBase, ControllerBase, ViewBase.
  *
  *   Methods Available to sub-classes.....
  *   fetch_config()
  *   process_exception()
  *   process_error()
  *   inspect()
  */
class ApplicationBase
{
/**
	*	 Stores a reference to the config object
	*  @access private
	*  @var reference
	*/
	private 	$config_object=null;
/**
	*		Stores the name of the controller to be run.
	*		@access protected
	*		@var 		string
	*/
	protected $controller;
	/**
	 *	Stores an array of actions.
	 *	Initially this is all sections of the url
	 *	As the controller and action are loaded this is
	 *	then reduced to the remaining part of the url
	 *	@access protected
	 *	@var 		array
	 */
	protected $actions=array();
	/**
	 *	Stores the name of the action to be run.
	 *	@access protected
	 *	@var 		string
	 */
	protected $action;
	/**
	 *	Stores a reference to the controller object
	 *	This is then used to run the action within the controller.
	 *	@access private
	 *	@var reference
	 */
	protected $controller_object;
	/**
	 *	Stores a string containing the processed HTML for the view.
	 *	@access protected
	 *	@var 		string
	 */
	protected $view_html;
	/**
	 *	Stores a string containing the processed HTML for the layout.
	 *	@access protected
	 *	@var 		string
	 */
	protected $layout_html;
	
	
	/**
	 *	Sets up the application and orchestrates progression.
	 *  @access public
   *  @return void
   */
	function __construct()
	{		
    set_exception_handler(array($this, 'process_exception'));    
    set_error_handler(array($this, 'process_error'), 259);
		$this->load_config(); 
		$this->copy_javascript();
		$this->mysql_db_backup(); 
    // Clean User Input
    $filter=new InputFilter(array(), array(), 1,1);
    $_POST=$filter->process($_POST);
    $_GET=$filter->process($_GET);
    //Start a session
    Session::start(); 
    $this->controller_object=$this->load_controller(); 
    $this->load_view($this->controller_object);
    $this->wrap_layout($this->controller_object);
    }

	/**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
   *  @return void
   */
	private function load_config() 
	{
		if(!$this->config_object) { $this->config_object=new ConfigBase; }
		$this->controller=$this->config_object->make_controller_route();
		$this->actions=$this->config_object->read_actions();
    }
	
	/**
	 *	Looks up a configuration value from the loaded
	 *	config object. Returns array of values.
	 *  @access protected
   *  @return array
   */
	protected function fetch_config($config)
	{
		$this->load_config();
		if($this->config_object)
		{
		return $this->config_object->return_config($config);	
		} else {
			return false;
		}
	}
		
	/**
	 *	Maps the controller to the controller file.
	 *	Decides on the action to run: Either the named action
	 *	if passed, an 'index' action or finally a fallback action
	 *	named 'missing_action'.
	 *	Returns a reference to the controller object.
	 *  @access private
   *  @return obj
   */	
	private function load_controller()
	{
	   $controller=$this->controller."_controller"; 
	   $this->action=$this->actions[0];
	   array_shift($this->actions);
	   $final_route=$this->actions;
	   if(strlen($this->action)<1) { $this->action="index"; }
	   try
	     {
	       	$cnt=new $controller();
	        $cnt->set_routes($final_route);
	        $cnt->set_action($this->action);
	        $cnt->controller_global();
	     }
	   catch(Exception $e) 
        {
            $this->process_exception($e);
        }
		$cnt->before_action($cnt->action);
		$cnt->{$cnt->action}();
		$cnt->filter_routes();
		$cnt->after_action($cnt->action);
		return $cnt;   
	}

	/**
	 *	Constructs the view html for the defined action.
	 *	Uses PHPTAL to parse templates, maps all class variables
	 *	to template variables
	 *	Also prepends any user errors or messages to the top of the view.
	 *  @access private
   *  @return void
   */	
	private function load_view($cnt)
	{
	   !$cnt->use_view ? $use_view=$this->action : $use_view=$cnt->use_view ; 
	   try
	     {
	      $view=new PHPTAL(APP_DIR.'view/'.$this->controller."/".$use_view.".html");
	      $action_vars=get_object_vars($cnt);
	      foreach($action_vars as $viewVar=>$val)
	        {
	           $view->{$viewVar}=$val;
	        }
	      $this->view_html=$view->execute();
		  $eventSelectors = "<script type=\"text/javascript\" language=\"javascript\" charset=\"utf-8\">EventSelectors.start(Rules);</script>";
		  //$this->view_html .= $eventSelectors;
				if($cnt->show_errors && count(Session::get('errors'))>=1) { 
					$temphtml=$this->view_html;
					$errors=Session::get('errors');
					$prepend="<ul class='user_errors'>";
					foreach($errors as $error) {
						$prepend.="<li>$error</li>";
						}
					$prepend.="</ul>";
					$this->view_html=$prepend.$temphtml;
					Session::unset_var('errors');
					}
					if($cnt->show_messages && is_array(Session::get('user_messages'))) { 
						$temphtml=$this->view_html;
						$messages=Session::get('user_messages');
						$prepend="<ul class='user_messages'>";
						foreach($messages as $message) {
							$prepend.="<li>$message</li>";
							}
						$prepend.="</ul>";
						
						$this->view_html=$prepend.$temphtml;
						Session::unset_var('user_messages');
						}
        }
      catch(Exception $e) 
        {
            $this->process_exception($e);
        }	
	}
	/**
	 *	Constructs the layout html.
	 *	Uses PHPTAL to parse templates, Inserts view html into the defined slot. 
	 *	Also prepends a doctype and page head to the layout.
	 *  @access private
   *  @return void
   */	
	private function wrap_layout($cnt)
	{
      try
        {
          $page_head=new PHPTAL(FRAMEWORK_DIR.'lib_core/page_head.html');
          if($cnt->use_layout)
          {
          	$use_layout=$cnt->use_layout;
            $layout=new PHPTAL(APP_DIR.'view/layouts/'.$use_layout.".html");
            $layout->layout_content=$this->view_html;
            foreach(get_object_vars($cnt) as $var=>$val)
            {
              $layout->{$var}=$val;
              $page_head->{$var}=$val;
            }
            $layout_html=$layout->execute();
            $page_head->layout_html=$layout_html;
            $this->layout_html=$page_head->execute();
          }
          else 
           {
           	$layout=new PHPTAL();
           	$layout->setSource("<p tal:omit-tag='' tal:content='structure layout_content'></p>");
           	$layout->layout_content=$this->view_html;
           	$this->layout_html=$layout->execute();
           }
          echo $this->layout_html;
			    Session::set('referrer', "/".$_GET['route']);	

	    }
	   catch(Exception $e) 
        {
           $this->process_exception($e);
        }
	}
	
	
	
	/**
	 *	The production environment exception handler.
	 *	Emails the trace and prints a generic user message to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception_prod($e)
	{
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
		$trace.="<h1>Application Error</h1>\n";
		$trace.="Oops, looks like there's been an error. Give it a few minutes and try again.";
		$trace.="</font>\n";
		echo $trace;
		$message=strip_tags($this->get_trace($e));
		//mail("ross@webxpress.com", "Application Error on production server", $message);
		exit();
	}
	/**
	 *	The development environment exception handler.
	 *	Prints the stack trace to the screen. 
	 *  @access public
   *  @return void
   */	
	public function process_exception($e)
	{
		if($this->fetch_config('environment')=='production') {
	    	$this->process_exception_prod($e); exit;
		}
		echo $this->get_trace($e);        
		exit();
	}
	/**
	 *	Converts the stack trace into a single html string.
	 *  @access public
   *  @return string
   */	
	public function get_trace($e) {
		$trace.="<font face=\"verdana, arial, helvetica, sans-serif\">\n";
    $trace.="<h1>Application Error</h1>\n";
    $trace.="<p>{$e->getMessage()}</p>\n";
    $trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px;\">";
    $trace.="<code>{$e->getTraceAsString()}</code></pre>\n";
    $trace.="<pre style=\"background-color: #eee;padding:10px;font-size: 11px; margin-top:5px;\">";
    $trace.="<code>{$e->getFile()}\nLine: {$e->getLine()}</code></pre>\n";
    $trace.="</font>\n";
		return $trace;
	}
	
	/**
	 *	Maps errors to the standard exception handler.
	 *  @access public
   *  @return void
   */	
	 public function process_error($errno, $errstr, $errfile, $errline) 
   {
     throw new Exception($errstr, $errno);
   }

	/**
	 *	Echos a formatted array to screen.
	 *	Superceded by inspect
	 *  @access protected
   *  @return void
	 *	@deprecated
   */	
	protected function inspect_array($array) {
		$this->inspect($array);
	}
	/**
	 *	Echos a formatted array to screen.
	 *  @access protected
   *  @return void
   */	
	public function inspect($array)
	{
	   echo "<pre>"; print_r($array); echo "</pre>"; 
	}
	

	/**
	 *	Copies javascript files from the javascript library
	 * 	into the runtime javascript folder.
	 *	There is a time restriction in place, the copies will only be performed
	 * periodically to improve performance.
	 *  @access private
   *  @return void
   */	
	private function copy_javascript() {
		 $fileArray=scandir(FRAMEWORK_DIR.'lib_core/javascript');
	   foreach($fileArray as $file)
        {
           if(preg_match("/^[a-zA-Z0-9_-\S]+\.js/",$file, $match))
           { $includeArray[]=$match[0]; }
        }
			$destdir=APP_DIR.'public/javascript/lib/';
			foreach($includeArray as $scriptfile) {
				$copyfile=true;
				if(file_exists($destdir.$scriptfile)) {
					$modtime=filemtime($destdir.$scriptfile);
					if($modtime>=(time() - 2592000 ) ) { $copyfile=false; $success=true; }
				}
				if($copyfile) { $success=copy(FRAMEWORK_DIR.'lib_core/javascript/'.$scriptfile, $destdir.$scriptfile); }
				if(!$success) { throw new Exception("Couldn't copy the javascript files"); }
				$success=false;
			}
	}
	
	/**
	 *	Includes an entire directory of php files.
	 *	This method is now handled by the AutoLoader class.
	 *	Left here for backwards compatibility.
	 *  @access public
   *  @return bool
	 *	@deprecated
   */	
	public function find_include_php($dir) {
		AutoLoader::include_dir($dir);
		return true;
	}

	/**
	 *	Writes a dump of the database to the app/model folder.
	 *	Occurs only in development environment and for mysql db only.
	 *	@todo Implement for other databases.
	 *  @access private
   *  @return bool
   */	
	private function mysql_db_backup() {
		if($this->fetch_config('environment')=='development') {
			$db=$this->fetch_config('db');
			if($db['dbtype']=="mysql") {
				$database=$db['database']; $user=$db['username']; $pass=$db['password'];
				$file=APP_DIR.'model/'.$database.'.sql';
				if(file_exists($file)) {
					$modtime=filemtime($file);
					if($modtime>=(time() - 1800 ) ) { return false; }
					unlink($file);
				}
 				$backup="/usr/local/mysql5/bin/mysqldump -u{$user} -p{$pass} $database  > $file";
				$result=passthru($backup);
				if($result) { return true; }
			}
		}
		return false;
	}

}


?>
