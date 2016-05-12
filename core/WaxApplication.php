<?php

/**
  *  @package PHP-Wax
  *  This is essentially a FrontController whose job in life
  *  is to parse the request and delegate the job to another
  *  controller that cares.
  *
  *  In making this decision it will consult the application configuration for guidance.
  *  It's also this lovely class's job to provide a limited amount of wiring to the rest of
  *  the application and setup some kind of Database Connection if required.
  *
  */

class WaxApplication
{


    public $request = false;
    public $response = false;
    public $renderer;

    /**
     *  Step 1. Setup an environment.
     *  Step 2. Find out if we're having a database and set it up.
     *  Step 3. Pass on the work to a delegate controller.
     *
     */
    public function __construct($delegate)
    {
        WaxEvent::run("wax.start");
        $this->setup_environment();
        $this->initialise_database();
        if ($delegate) {
            $this->execute();
        } else {
            $this->response = new WaxResponse;
        }
    }


    /**
	 *	Instantiates a config object and constructs the route.
	 *  @access private
     *  @return void
    **/
	private function setup_environment()
    {
		if(defined('ENV')) {
		  Config::set_environment(ENV);
		} elseif($_SERVER["ENV"]) {
			define("ENV", $_SERVER["ENV"]);
		  Config::set_environment($_SERVER["ENV"]);
		} elseif(Config::get($_SERVER["SERVER_NAME"])) {
		  Config::set_environment($_SERVER["SERVER_NAME"]);
		  define("ENV", $_SERVER["SERVER_NAME"]);
		} else{
              define("ENV", 'production');
              Config::set_environment('production');
            }

		//  Looks for an environment specific file inside app/config
		if(is_readable(CONFIG_DIR.ENV.".php")) require_once(CONFIG_DIR.ENV.".php");
    }

    /**
	 *	Instantiates a database connection. It requires PDO which is available in PHP 5.1
	 *  It then passes this information to the ActiveRecord object.
	 *
	 *  A few defaults are allowed in case you are too lazy to specify.
	 *  Dbtype defaults to *mysql*
	 *  Host defaults to *localhost*
	 *  Port defaults to *3306*
	 *
	 *  @access private
     *  @return void
     */

    private function initialise_database()
    {
        if($db = Config::get('db')) {
            if($db['dbtype']=="none") return false;
            WaxModel::load_adapter($db);
        }
    }


    /**
	 *	The main application method, triggers all application events.
	 *  Delegates response handling to WaxResponse.
     *
     **/
    public function execute()
    {
        WaxEvent::run("wax.request");
	    $this->request = WaxUrl::$params;
	    WaxEvent::run("wax.post_request", $this->request);
	    $this->response = new WaxResponse;

        $delegate = WaxUrl::get("controller");

        if(!is_callable($delegate)) {
            $delegate = Inflections::slashcamelize(WaxUrl::get("controller"), true)."Controller";
            $controller = new $delegate($this);
            $controller->controller = WaxUrl::get("controller");
        } else {
            $controller = $delegate($this);
            $controller->controller = get_class($controller);
        }

	    WaxEvent::run("wax.controller", $controller);
	    WaxEvent::run("wax.pre_render", $controller);
	    if($controller->render !==false) {
            $response = $this->execute_controller($controller);
            if ($response) {
                return $response;
            }
        }
		WaxEvent::run("wax.post_render", $this->response);
		$this->response->execute();
    }


    /**
     * Takes the controller and triggers the relevant action.
     * @param WaxController $controller
     *
     * @return string $response | null
     * @throws WXRoutingException
     */
    public function execute_controller(&$controller)
    {
        # Initialise this to null, it will only get set if an action returns its own response
        $response = null;

        $controller->action = WaxUrl::get("action");
        $controller->route_array = explode("/", trim(WaxUrl::$original_route, "/"));
        $controller->use_format = WaxUrl::get("format");

        WaxEvent::run("wax.controller_global", $controller);
        $controller->controller_global();
        WaxEvent::run("wax.before_filter", $controller);
        $controller->run_filters("before");
        if (!$this->is_public_method($controller, $controller->action)) {
            if ($this->is_public_method($controller, Inflections::underscore($controller->action))) {
                $underscore_action = Inflections::underscore($controller->action);
                WaxEvent::run("wax.action", $controller);
                $response = $controller->{$underscore_action}();
            } elseif (method_exists($controller, 'method_missing')) {
                $response = $controller->method_missing();
            } else {
                $class = get_class($controller);
                WaxEvent::run("wax.404", $controller);
                throw new WXRoutingException("No Public Action Defined for - " . $controller->action . " in controller {$class}.",
                    "Missing Action");
            }
        } else {
            WaxEvent::run("wax.action", $controller);
            $response = $controller->{$controller->action}();
        }

        WaxEvent::run("wax.after_filter", $controller);
        $controller->run_filters("after");

        # This short circuits the built in response write and returns it.
        # This allows newer controller actions to build and return their own string response without
        # needing to do the layout / view split.
        if ($response !== null) {
            return $response;
        }

        $controller->content_for_layout = $controller->render_view();
        WaxEvent::run("wax.layout", $controller);

        if ($content = $controller->render_layout()) {
            $this->response->write($content);
        } elseif ($controller->content_for_layout) {
            $this->response->write($controller->content_for_layout);
        } else {
            $this->response->write("");
        }
    }

    ### Application Helper Methods

    /**
     *  Surely it's self-documenting?.
     * @param $object
     * @param $method
     * @return bool
     */
    public static function is_public_method($object, $method)
    {
        if (!method_exists($object, $method)) {
            return false;
        }
        $this_method = new ReflectionMethod($object, $method);
        if ($this_method->isPublic()) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param mixed $renderer
     */
    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }


}

