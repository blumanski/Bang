<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This is bang main controller, all requests go through this controller.
 * This is the only access point for the application.
 * The controller will load the requested module - controller and controllerAction.
 * If a module - controller or action is not available it will load an error response.
 * Further, the controller is setting up the dependency container and is starting the core classes.
 * Finally, it is adding the modules namespace to the autoloader.
 * 
 * The class is extending SuperController which does not have much in it yet,
 * it can be used in the future to implement more features which are needed in all controllers.
 */

Namespace Bang;

ini_set('display_errors', 1);
error_reporting(E_ALL);

USE BangConfig\Cnf,
    Bang\Router,
    Bang\PdoWrapper, 
    Bang\SessionHandler,
    Bang\ModuleLoader,
    stdClass;
    
class Controller extends SuperController
{
    /**
     * Database/PDO Wrapper instance
     * @var object
     */
    private $PdoWrapper;
    
    /**
     * Instance of Redis key value store
     * @var object
     */
    private $Redis;
    
    /**
     * View instance
     * @var object
     */
    private $View;
    
    /**
     * Router instance
     * @var object
     */
    private $Router;
    
    /**
     * Keeps the current route
     * @var unknown
     */
    private $Route;
    
    /**
     * Autoload instance
     * @var object
     */
    private $Loader;
    
    /**
     * Instance of Lang
     * @var object
     */
    private $Lang;
    
    /**
     * Dependency injection container
     * @var object
     */
    private $DI;
    
    /**
     * Constructor, setting up the class environment.
     * @param object $loader
     */
    public function __construct(\Loader\Autoloader $loader)
    {
    	// Get config, start db class, whitelist tablenames and set config to constant
        $this->prepareConfig();
        $this->startApplication($loader);
    }
    
    /**
     * Start app and execute model
     */
    private function startApplication($loader)
    {
    	if(!isset($_SERVER['HTTP_HOST'])) {
    		$_SERVER['HTTP_HOST'] = '';
    	}
    	
    	if($_SERVER['HTTP_HOST'] == CONFIG['app']['backendurl']) {
    		define('ENV', 'Backend');
    	} else {
    		define('ENV', 'Frontend');
    	}
    	
    	// set up classes
    	$this->setUpClasses($loader);
    	
    	// Session handler
    	$this->setUpSessionHandler();
    	
    	// try to get the current session user
    	// to get the language that we want to load
    	$current = $this->Session->getUser();
    	
    	$languageToLoad = CONFIG['app']['language'];
    	// if the user has a default language, use it
    	if(is_array($current) && isset($current['lang'])) {
    		if(in_array($current['lang'], CONFIG['langwhitelist'])) {
    			$languageToLoad = $current['lang'];
    		}
    	}
    	
    	// set timezone, use users timezone if available or default to config default
    	if(is_array($current) && isset($current['timezone'])) {
    		date_default_timezone_set($current['timezone']);
    	} else {
    		date_default_timezone_set(CONFIG['app']['timezone']);
    	}
    	
    	// Load the core language file
    	$this->Lang->loadMainLanguageFile($languageToLoad);
    	
    	// Load the app view, standard is webview or response view
    	$this->loadView();
    	
    	// Set the default language in the View,
    	// this can get overwritten on module level
    	$this->View->setLanguage($this->Lang);
    	
    	// inject classes
    	$this->injectClassesIntoDiContainer();
    	
    	// Inject DI container using setter
    	$this->injectDiSetter();
    	
    	// This view is now fully set up
    	// if backend - Test the backend url against the host and assign default route
    	// Differentiate between backend and front end
    	if(ENV === 'Backend') {
    		$this->View->setMainTemplate(CONFIG['app']['backendmaintpl']);
    	} else {
    		$this->View->setMainTemplate(CONFIG['app']['frontmaintpl']);
    	}
    	
    	// Translate route to array
    	$this->Route = $this->getRoute();
    	
    	$this->cors();
    	
    	// execute module and dispatch
    	$this->callAndDispatch();
    }
    
    /**
     * Execute the module
     */
    private function callAndDispatch()
    {
    	$this->ModuleLoader = new ModuleLoader($this->DI);
    	
    	// will be always right regarding string format.
    	// Execute module call, this will trigger further tests and execute the module call
    	if($this->ModuleLoader->executeModule($this->Route['module'], $this->Route['controller'], $this->Route['action']) === true) {
    		 
    		// Pre-dispatch, this is putting all templates and content together
    		$this->View->preDispatch();
    		$this->View->dispatch();
    		 
    	} else {
    		 
    		// If above wasn't working, start over and use an error
    		// Error is already logged
    		$this->Route['module']        = 'error';
    		$this->Route['controller']    = 'index';
    		$this->Route['action']        = 'error404';
    		 
    		if($this->ModuleLoader->executeModule($this->Route['module'], $this->Route['controller'], $this->Route['action']) === true) {
    	
    			// Pre-dispatch, this is putting all templates and content together
    			$this->View->preDispatch();
    			$this->View->dispatch();
    	
    		} else {
    			// if that above fails, should never ever happen...
    			return false;
    		}
    	}
    }

    /**
     * Prepare the config and db including whitelist table names
     * @todo May have a hard coded whitelist for tables in production to save one db query
     */
    private function prepareConfig() 
    {
    	$env = Cnf::Env;
    	// Get the app configuration array
    	$cnf    = Cnf::$env();
    	
    	// Start core classes
    	$this->PdoWrapper	= new PdoWrapper($cnf);
    	
    	// create whitelist of table names
    	// For production replace with hard coded list
    	$dbTables   = $this->PdoWrapper->getTableNames();
    	// add the whitelist to the config array
    	$cnf['database']['whitelist'] = $dbTables;
    	// get the current path, app starts in backend/public or frontend/public
    	$directory = dirname($this->getCurrentFolder());
    	
    	define('MOD', $directory.DIRECTORY_SEPARATOR.'modules/');
    	// add path to the config constant
    	$cnf['app']['path'] = $directory;
    	
    	// Below I assign the config array to a constant.
    	// must be >= php.5.6
    	// I add the config array to a constant as I do not want the config to change
    	// during runtime. There will be another configuration later in the db (key , value)
    	define('CONFIG', $cnf);
    	// unset the config so it can't be used anymore
    	// one must use the constant from now on
    	unset($cnf);
    }
    
    /**
     * Get the route
     */
    private function getRoute()
    {
    	$route = $this->Router->getRoute();
    	
    	// At start, set the default route to the front end route
    	$default = CONFIG['app']['defaultroutefront'];
    	
    	// if backend - Test the backend url against the host and assign default route
    	if(ENV == 'Backend') {
			$default = CONFIG['app']['defaultrouteback'];    		
    	}
    	
		$route['module']        = $route['module'] ?? $default['module'];
		$route['controller']    = $route['controller'] ?? $default['controller'];
		$route['action']        = $route['action'] ?? $default['action'];
    	
    	return $route;
    }
    
    /**
     * Some classes use setter to get the di container
     */
    private function injectDiSetter()
    {
    	// Inject the di container into ErrorLog instance
    	$this->ErrorLog->setDI($this->DI);
    	// Inject the di container into View instance
    	$this->View->setDI($this->DI);
    }
    
    /**
     * Inject classes into di container
     */
    private function injectClassesIntoDiContainer()
    {
    	// Add instances to dependency container
    	// Autoloader to load classes from the modules
    	$this->addDepToDiContainer('Loader', $this->Loader);
    	$this->addDepToDiContainer('View', $this->View);
    	$this->addDepToDiContainer('PdoWrapper', $this->PdoWrapper);
    	$this->addDepToDiContainer('ErrorLog', $this->ErrorLog);
    	$this->addDepToDiContainer('Session', $this->Session);
    	
    	// If redis is available add it to the di container
    	if($this->Redis && $this->Redis instanceof \Redis) {
    		$this->addDepToDiContainer('Redis', $this->Redis);
    	}
    }
    
    // Load the view
    private function loadView()
    {
    	if(ENV == 'Frontend') {
    		
    		if(CONFIG['app']['frontendview'] == 'responder') {
    			// View for API like app.
    			$this->View = new ResponderView();
    			 
    		} elseif(CONFIG['app']['frontendview'] == 'web') {
    			// View for web site like app.
    			$this->View = new WebView();
    		}
    		
    	} else { 
    		
    		if(CONFIG['app']['view'] == 'responder') {
    			// View for API like app.
    			$this->View = new ResponderView();
    			 
    		} elseif(CONFIG['app']['view'] == 'web') {
    			// View for web site like app.
    			$this->View = new WebView();
    		}
    	}
    }
    
    /**
     * Set up session handler
     */
    private function setUpSessionHandler()
    {
    	// If redis is set to be the session handler, set this up, otherwise it will use the standard php handler
    	if(CONFIG['app']['sessionhandler'] == 'redis' && CONFIG['sessionredis']['hosts'] != '') {
    		$this->SessionHandler 	= new RedisSessionHandler($this->Redis, CONFIG);
    		session_set_save_handler($this->SessionHandler, true);
    	}
    	
    	// Start instance of session
    	$this->Session = new Session();
    }
    
    /**
     * Set up the classes needed for the application
     */
    private function setUpClasses($loader)
    {
    	$this->Router   		= new Router();
    	$this->ErrorLog 		= new ErrorLog();
    	$this->DI       		= new stdClass();
    	$this->Lang     		= new Lang();
    	$this->Loader   		= $loader;
    	
    	// If redis is available, set it up
    	if(CONFIG['sessionredis']['hosts'] != '') {
    		$this->Redis            = new \Redis();
    		$this->Redis->connect(CONFIG['sessionredis']['hosts'], CONFIG['sessionredis']['port']);
    		$this->Redis->auth(CONFIG['sessionredis']['auth']);
    	}
    }
    
    /**
     * As we have two environments to run, we need to know wich one it is
     */
    private function getCurrentFolder()
    {
        return getcwd();
    }
    
    /**
     * Add instance to di container
     * @param string $name
     * @param object $value
     */
    private function addDepToDiContainer(string $name, $value)
    {
        $this->DI->$name    = $value;
    }
    
    /**
     * @todo Finalise this with config entries and domain
     */
    private function cors()
    {
		header("Access-Control-Allow-Origin: *");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE, OPTIONS');
		header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
		header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    
    /**
     * Must be in all classes
     * @return array
     */
    public function __debugInfo() {
    
    	$reflect	= new \ReflectionObject($this);
    	$varArray	= array();
    
    	foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
    		$propName = $prop->getName();
    		 
    		if($propName !== 'DI' && $propName != 'CNF') { 
				//print '--> '.$propName.'<br />';
				$varArray[$propName] = $this->$propName;
			}
    	}
    
    	return $varArray;
    }
    
    /**
     * 
     */
    public function __destruct(){}
}