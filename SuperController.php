<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This is the bang main controller, all request go through this controller
 * This is the only access point for the whole application.
 * The controller will load the requested module - controller and controllerAction
 * It also is setting up the dependency injection container which is provided 
 * automatically to all dynamically loaded module calls.
 * 
 * Anything in this framework is a module call.
 */

Namespace Bang;

class SuperController 
{	
	/**
	 * send 404 header 
	 */
	protected function return404()
	{
		header("HTTP/1.1 404 Not Found");
		exit();
	}
	
	/**
	 * Send 403 header
	 */
	protected function return403()
	{
		header("HTTP/1.1 403 Not Found");
		exit();
	}
	
	/**
	 * retrun the path to the overwrite directory for the backend
	 */
	protected function getBackTplOverwrite()
	{
		return PUB.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['backendmaintpl'].DIRECTORY_SEPARATOR.'mod_tpl_overwrites'.DIRECTORY_SEPARATOR;
	}
	
	/**
	 * retrun the path to the overwrite directory for the frontend
	 */
	protected function getFrontTplOverwrite()
	{
		return PUB.'templates'.DIRECTORY_SEPARATOR.CONFIG['app']['frontmaintpl'].DIRECTORY_SEPARATOR.'mod_tpl_overwrites'.DIRECTORY_SEPARATOR;
	}
	
	/**
	 * Exit calls, wrap in in function to be able to stub it during testing
	 */
	protected function callExit($message = NULL)
	{
		exit($message);
	}
	
	/**
	 * Change the language for this session
	 */
	public function changeLanguage()
	{
		$params = Helper::getRequestParams('get');
	
		if(is_array($params) && isset($params['lang'])) {
	
			if(isset(CONFIG['langwhitelist'][$params['lang']])) {
				
				\Locale::setDefault(CONFIG['langwhitelist'][$params['lang']]);
	
				$lastPage = '/';
	
				if(isset($_SERVER['HTTP_REFERER'])) {
					// test url
					$url = parse_url ($_SERVER['HTTP_REFERER']);
						
					if(is_array($url) && isset($url['host']) && $url['host'] == CONFIG['app']['backendurl']) {
						$lastPage = $_SERVER['HTTP_REFERER'];
					}
				}
	
				$this->Session->setToUser('lang', CONFIG['langwhitelist'][$params['lang']]);
	
				Helper::redirectTo($lastPage);
				exit;
			}
		}
	
		die();
	}
	
}