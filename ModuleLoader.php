<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-03-20
 *
 * The ModuleLoader is loading the module - controller - action
 */

Namespace Bang;

Use Bang\Helper;

class ModuleLoader
{
    /**
     * Instance of ErrorLOg
     * @var object
     */
    private $ErrorLog;
    
    /**
     * Instance of autoloader
     * @var object
     */
    private $Loader;
    
    /**
     * Di Container object
     * @var object
     */
    private $DI;
    
    /**
     * This class is getting the the di container 
     * injected via setter method, which is doing the setup of the dependencies as well.
     * @param \stdClass $di
     */
    public function __construct(\stdClass $di)
    {
        $this->ErrorLog = $di->ErrorLog;
        $this->Loader	= $di->Loader;
        $this->DI		= $di;
    }
    
    
    /**
     * Lowest level of string validation for the router
     */
    private function lowLevelParameterValidation(string $module, string $controller, string $action)
    {
    	// The first test is a string validation, module and controllers must only have alpha characters
    	// controller actions can have alphanumeric characters
    	if($this->validateAppParamaters($module, $controller, $action) === false) {
    		 
    		// Validation failed and the route is changing to an error route
    		$this->Route['module']        = 'error';
    		$this->Route['controller']    = 'index';
    		$this->Route['action']        = 'error404';
    		 
    		// Log this error, that will probably fill up the log with "valuable" information
    		$this->ErrorLog->logError(
    				'App',
    				'App Parameter Test | Test failed -> | Module -> '.htmlspecialchars($this->Route['module'], ENT_QUOTES, 'UTF-8').
    				' | Controller  -> '.htmlspecialchars($this->Route['controller'], ENT_QUOTES, 'UTF-8').
    				' | Action -> '.htmlspecialchars($this->Route['action'], ENT_QUOTES, 'UTF-8'),
    				__FILE__.' -> Line '.__LINE__ .' -> '. __METHOD__
    				);
    
    		return $this->Route;
    	}
    }
    
    /**
     * Test the incoming route values for the right data type
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    private function validateAppParamaters(string $module, string $controller, string $action)
    {
    	// they have to be all true or all are invalid
    	// test module parameter
    	if(Helper::validate($module, 'module', 65) === true && !empty($module)) {
    		// test controller parameter
    		if(Helper::validate($controller, 'module', 65) === true && !empty($controller)) {
    			// test action parameter
    			if(Helper::validate($action, 'alphanum', 100) === true && !empty($action)) {
    				return true;
    			}
    		}
    	}
    	 
    	return false;
    }
    
    /**
     * Load the module controller
     * 1. First check if the file exists
     * 2. Start an instance of the controller
     * 3. Load controllerAction
     * @Note Parameters was already tested before getting into this method
     * @see $this->loadModuleControllerAction
     * @param string $module
     */
    public function executeModule(string $module, string $controller, string $action, array $params = array(), bool $return = false)
    {
    	$this->lowLevelParameterValidation($module, $controller, $action);
    	
    	// path to the module
    	$moduleDirectory = CONFIG['app']['path'].DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR;
    
    	// Make sure the directory exists
    	if(file_exists($moduleDirectory)) {
    
    		// Make sure controller file exists
    		if(file_exists($moduleDirectory.'controllers'.DIRECTORY_SEPARATOR.$controller.'Controller.php')) {
    
    			// Register module in autoloader, controller and model
    			$this->Loader->addNamespace('\Bang\Modules\\'.ucfirst($module), $moduleDirectory.'controllers');
    			$this->Loader->addNamespace('\Bang\Modules\\'.ucfirst($module).'\\Models', $moduleDirectory.'models');
    
    			// Combine the string to load the module controller
    			$controllerToLoad   = 'Bang\\Modules\\'.ucfirst($module).'\\'.$controller.'Controller';
    			$actionMethod       = $action.'Action';
    			
    			// Load actionMethod
    			// Those strings will get validated in the method itself
    			return $this->loadModuleControllerAction($controllerToLoad, $actionMethod, $params, $return);
    
    		} else {
    			 
    			// add to internal error log
    			$this->ErrorLog->logError(
    					'App',
    					'Action Call | File '.MOD.htmlspecialchars($module, ENT_QUOTES, 'UTF-8').'/Controller/'.
    					htmlspecialchars($controller, ENT_QUOTES, 'UTF-8').'Controller.php'.' does not exist',
    					__FILE__.' -> Line '.__LINE__ .' -> '. __METHOD__ .' -> Module ->  '.
    					htmlspecialchars($module, ENT_QUOTES, 'UTF-8').' | Controller -> '.
    					htmlspecialchars($controller, ENT_QUOTES, 'UTF-8').
    					' | Action -> '.htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
    					);
    
    			// The controller wasn't found, bomb out with a 404
    			return '404';
    		}
    
    	} else {
    		 
    		// add error to internal error log
    		$this->ErrorLog->logError(
    				'App',
    				'Module Loader -> File '.MOD.htmlspecialchars($module, ENT_QUOTES, 'UTF-8').' does not exist',
    				__FILE__.' -> Line '.__LINE__ .' -> '. __METHOD__ .' -> Module ->  '.
    				htmlspecialchars($module, ENT_QUOTES, 'UTF-8').' | Controller -> '.
    				htmlspecialchars($controller, ENT_QUOTES, 'UTF-8').' | Action -> '.
    				htmlspecialchars($action, ENT_QUOTES, 'UTF-8')
    				);
    
    		// The module wasn't found, bomb out with a 404
    		return '404';
    	}
    }
    
    /**
     * Load the Action method according to the parameter
     * @param String $toLoad
     * @param string $actionCall
     * @return boolean
     */
    public function loadModuleControllerAction(string $controllerToLoad, string $actionMethod, array $params = array(), bool $return = false)
    {
    	// First check if the class exists
    	if(class_exists($controllerToLoad, true)) {
    
    		// start a module controller instance
    		$ctrl   = new $controllerToLoad($this->DI);
    		 
    		// load controller action
    		if(method_exists($ctrl, $actionMethod)) {
    			
    			if($return === true) {
    				
    				return $ctrl->$actionMethod((array)$params);
    				
    			} else {
    				
    				$ctrl->$actionMethod();
    				return true;
    			}
    			 
    		} else {
    			// add to error log
    			$this->ErrorLog->logError(
    					'App',
    					'Action Call | Method '.
    					MOD.htmlspecialchars($controllerToLoad, ENT_QUOTES, 'UTF-8').'/'.
    					htmlspecialchars($actionMethod, ENT_QUOTES, 'UTF-8').' does not exist',
    					__FILE__.' -> Line '.__LINE__ .' -> '. __METHOD__ .' -> Controller -> '.
    					htmlspecialchars($controllerToLoad, ENT_QUOTES, 'UTF-8').' | ActionMethod -> '.
    					htmlspecialchars($actionMethod, ENT_QUOTES, 'UTF-8')
    					);
    		}
    		 
    	} else {
    
    		// add to error log
    		$this->ErrorLog->logError(
    				'App',
    				'Module Class Exists | Class '.
    				htmlspecialchars($controllerToLoad, ENT_QUOTES, 'UTF-8').
    				' does not exist.',
    				__FILE__.' -> Line '.__LINE__ .' -> '. __METHOD__ .
    				' -> Controller -> '.htmlspecialchars($controllerToLoad, ENT_QUOTES, 'UTF-8').
    				' | ActionMethod -> '.htmlspecialchars($actionMethod, ENT_QUOTES, 'UTF-8')
    				);
    
    	}
    
    	return false;
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
    		 
    		if($propName !== 'DI') {
    			//print '--> '.$propName.'<br />';
    			$varArray[$propName] = $this->$propName;
    		}
    	}
    
    	return $varArray;
    }
    
    /**
     * May later for clean up things
     */
    public function __destruct(){
        
    }
}