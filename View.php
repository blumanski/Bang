<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This is View, all views inerhit from view.
 * It provides mandatory class vars and methods.
 */

Namespace Bang;


abstract class View
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
	public $MainIndex = 'index.php';
	
	/**
	 * The pages templates all together in a string
	 * @var string
	 */
	public $PagePrint = NULL;
	
	/**
	 * Instance of Language class
	 * @var object
	 */
	public $Lang;
	
	/**
	 * Path from public folder to current template
	 * @var string
	 */
	public $TemplatePath = '';
	
	/**
	 * Assign a value to a new tpl variable
	 * I a template you can call a variable by using $this->tplVar['key']
	 * @param string $key | alpha
	 * @param string $value 
	 */
	public function setTplVar(string $key, $value)
	{}
	
	/**
	 * Set the main language
	 * @param object $lang
	 */
	public function setLanguage(\Bang\Lang $lang)
	{}
	
	/**
	 * Set a mmodules template
	 */
	public function setModuleTpl(string $key, $sub = array())
	{}
	
	/**
	 * Render the module content
	 * This method will be called from the main template
	 */
	public function renderModule()
	{}
	
	/**
	 * Load a template and return it as string
	 * {@inheritDoc}
	 * @see \Bang\View::loadTemplate()
	 */
	public function loadTemplate(string $tpl)
	{}
	
	/**
	 * Compress that string a bit.
	 * @param string $string
	 */
	private function sanitize_output(string $string)
	{}
	
	/**
	 * Combine template with content and prepare for dispatch
	 * {@inheritDoc}
	 * @see \Bang\View::prepareDispatch()
	 */
	public function preDispatch()
	{}
	
	/**
	 * Displatch the template
	 * {@inheritDoc}
	 * @see \Bang\View::dispatch()
	 */
	public function dispatch()
	{}
	
}