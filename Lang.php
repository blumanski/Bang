<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This class loads language files and returns and writes language strings
 * At start of the application the loadMainLanguageFile will be called to load the
 * appropriate language.
 * 
 * Modules can call the addLanguageFile to add their own language files.
 * 
 * There are methods to write a language string with option to sanatize the string
 * Strings can also get combined with dynamic input such as usernames, @see getCombine, combine
 * 
 */

Namespace Bang;

class Lang 
{
    /**
     * aray with all language keys in it
     * @var array
     */
    private $Store = array();
    
    /**
     * The currently loaded language
     * @var String
     */
    public $LangLoaded;
    
    
    public function __construct(){}
    
    /**
     * At the start, I load the main default language file
     * @param string $path
     */
    public function loadMainLanguageFile($languageFile)
    {
    	$lang = array();
    	
    	\Locale::setDefault(CONFIG['app']['language']);
        
    	// Path to the main language file
        $mainFile = dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.strtolower(ENV).DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$languageFile.'.ini';
        
        if(file_exists($mainFile)) {
        	$lang = parse_ini_file($mainFile, INI_SCANNER_RAW);
        }
        
        if(is_array($lang) && count($lang)) {
            $this->Store = $lang;
        }
        
        $this->LangLoaded = $languageFile;
        
        return false;
    }
    
    /**
     * Add language file
     * @param string $path
     */
    public function addLanguageFile(string $path)
    {
    	if(file_exists($path.'.ini')) {
    		
    		$lang = parse_ini_file($path.'.ini', INI_SCANNER_RAW);
    		
    		if(is_array($lang) && count($lang)) {
    			$this->Store = array_merge($this->Store, $lang);
    		}
    		
    	}
        
        return false;
    }
    
    /**
     * Print out a string from the language array
     * With parameter $safe you can enable or disable specialchars
     * For html code you may want to keep that off
     * @param string $key
     * @param $safe 
     */
    public function write(string $key, bool $safe = true)
    {
    	if(isset($this->Store[$key])) {
	    	
    		if($safe === true) {
	    	
    			echo htmlspecialchars($this->Store[$key], ENT_QUOTES, 'UTF-8');
	    		
	    	} else {
	    	
	    		echo $this->Store[$key];
	    	}
    	}
        
        return;
    }
    
    /**
     * return out a string from the language array
     * @param string $key
     */
    public function get(string $key)
    {
    	if(isset($this->Store[$key])) {
    		return htmlspecialchars($this->Store[$key], ENT_QUOTES, 'UTF-8');
    	}
    
    	return;
    }
    
    /**
     * return a string which has variables in it
     * @param string $key
     * @param array $val
     */
    public function getCombine(string $key, array $val)
    {
    	if(isset($this->Store[$key])) {
    		
    		ob_start();
    		
    		vprintf($this->Store[$key], $val);
    		
    		return ob_get_clean();
    	}
    
    	return;
    }
    
    /**
     * Render a string which has variables in it
     * For html code you may want to keep that off
     * @param string $key
     * @param array $val
     * @param $safe 
     */
    public function combine(string $key, array $val, bool $safe = true)
    {
        if(isset($this->Store[$key])) {
        	
        	if($safe === true) {
        		 
        		htmlspecialchars(vprintf($this->Store[$key], $val), ENT_QUOTES, 'UTF-8');
        		 
        	} else {
        		 
        		vprintf($this->Store[$key], $val);
        		 
        	}
        }
        
        return;
    }
    
    public function __destruct(){}
}