<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * The responder view is a bit simpler and is used for ajax requests.
 * As those outputs are json, there is not much rendering needed as in the web view.
 * 
 * One can use this or the web view to create an individual view and use it.
 * The view can get changed on module level.
 * 
 * So, one would be able to add soemthing like smarty as view.
 */

Namespace Bang;


class ResponderView extends \Bang\View
{
	/**
	 * Tpls variables which will get assigned
	 * to use in templates
	 * @var array
	 */
	public $tplVar = array();

	/**
	 * A module can assign it's template filled with content to the view
	 * These are collected in this variable
	 * @var array
	 */
	public $ModuleTpl = array();
	
	/**
	 * This will hold the name of the main template
	 * This can get changed in each module controller
	 */
	public $MainTpl = 'default';
	
	/**
	 * This is holding the index file of the template
	 * This can get changed in each module controller
	 */
	public $MainIndex = CONFIG['app']['frontindexfile'];
	
	/**
	 * The pages templates all together in a string
	 * @var string
	 */
	public $PagePrint = NULL;
	
	/**
	 * di container
	 * @var object
	 */
	public $DI;
	
	/**
	 * Instance of session object
	 * @var object
	 */
	public $Session;
	
	/**
	 * Set di container from controller
	 * @param \stdClass $di
	 */
	public function setDI(\stdClass $di)
	{
		$this->DI   		= $di;
		$this->Session		= $di->Session;
	}
	
	/**
	 * Exit with string response
	 * This is a short cut as this will bomb out
	 * The srting is supposed to be a json string.
	 *
	 * @param string $string
	 */
	public function respond(string $string)
	{
		header('Content-type:application/json;charset=utf-8');
		exit($string);
	}
	
	/**
	 * Set the main language
	 * @param object $lang
	 */
	public function setLanguage(\Bang\Lang $lang)
	{
	    $this->Lang = $lang;
	}
	
	/**
	 * Overwrite/Set the main template
	 * Can only get set to the frontend or back end template
	 * This is pointing to the template folder only, the templates itself can
	 * have as many templates as they need
	 * @param string $template
	 */
	public function setMainTemplate($template)
	{
		// Whitelist templates
		if($template == CONFIG['app']['backendmaintpl'] || $template == CONFIG['app']['frontmaintpl']) {
			$this->MainTpl = $template;
			$this->TemplatePath = DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$template.DIRECTORY_SEPARATOR;
			return true;
		}
	
		return false;
	}
	
	/**
	 * Assign a value to a new tpl variable
	 * I a template you can call a variable by using $this->tplVar['key']
	 * @param string $key | alpha
	 * @param string $value 
	 */
	public function setTplVar(string $key, $value) 
	{
		
	}
	
	/**
	 * Set a mmodules template
	 */
	public function setModuleTpl(string $key, $sub = array()) 
	{
		
	}
	
	/**
	 * Render the module content
	 * This method will be called from the main template
	 */
	public function renderModule()
	{
		
	}
	
	/**
	 * Load a template and return it as string
	 * {@inheritDoc}
	 * @see \Bang\View::loadTemplate()
	 */
	public function loadTemplate(string $tpl) 
	{
		
	}
	
	/**
	 * Compress that string a bit.
	 * @param string $string
	 */
	private function sanitize_output(string $string) 
	{
		
	}
	
	/**
	 * Combine template with content and prepare for dispatch
	 * {@inheritDoc}
	 * @see \Bang\View::prepareDispatch()
	 */
	public function preDispatch()
	{
		if (file_exists('templates/' . $this->MainTpl . '/' . $this->MainIndex)) {
		
		    // test if no other buffer is open, if so, flush it.
			if (ob_get_level() > 0) {
				ob_end_flush();
			}
			 
			ob_start();
			
			// this only require once...
			require_once ('templates/' . $this->MainTpl . '/' . $this->MainIndex);
		
			$this->PagePrint = ob_get_clean();
		}
		
		return false;
	}
	
	/**
	 * Displatch the template
	 * {@inheritDoc}
	 * @see \Bang\View::dispatch()
	 */
	public function dispatch()
	{
		print $this->PagePrint;
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
	
}