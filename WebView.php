<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * This is a web view to display a website like template with sub templates
 * One can write it's own view using this class as template
 * 
 */
namespace Bang;

class WebView extends \Bang\View
{

    /**
     * Tpls variables which will get assigned
     * to use in templates
     * 
     * @var array
     */
    public $tplVar = array();

    /**
     * A module can assign it's template filled with content to the view
     * These are collected in this variable
     * 
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
     * 
     * @var string
     */
    public $PagePrint = NULL;

    /**
     * Instance of the language class
     * 
     * @var object
     */
    public $Lang;

    /**
     * Instance of session object
     * 
     * @var object
     */
    public $Session;

    /**
     * Array with scripts to load
     * 
     * @var array/string
     */
    public $Scripts = array();

    /**
     * Array with styles to load
     * 
     * @var array/string
     */
    public $Styles = array();

    /**
     * Path from public folder to current template
     * 
     * @var string
     */
    public $TemplatePath = '';

    /**
     * Keep some data about the current user
     * 
     * @var array
     */
    public $CurrentUser = array();

    /**
     * Holding the di container
     * 
     * @var object $DI
     */
    private $DI;

    /**
     * Set di container from controller
     * And set up class.
     * The class is instanciated very early,
     * that is why it uses a setter to get the Di container.
     * As it is in the DI container itself...
     * 
     * @param \stdClass $di            
     */
    public function setDI(\stdClass $di)
    {
        $this->Session = $di->Session;
        $this->DI = $di;
        
        $this->CurrentUser = $this->Session->getUser();
    }

    /**
     * Overwrite/Set the main template
     * Can only get set to the frontend or back end template
     * This is pointing to the template folder only, the templates itself can
     * have as many templates as they need
     * 
     * @param string $template            
     */
    public function setMainTemplate($template)
    {
        // Whitelist templates
        if ($template == CONFIG['app']['backendmaintpl'] || $template == CONFIG['app']['frontmaintpl']) {
            $this->MainTpl = $template;
            $this->TemplatePath = DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template . DIRECTORY_SEPARATOR;
            return true;
        }
        
        return false;
    }

    /**
     * Set the main template in terms of index.php or login.php as example
     */
    public function setMainIndex(string $template)
    {
        $this->MainIndex = $template;
    }

    /**
     * Assign a value to a new tpl variable
     * In a template you can call a variable by using $this->tplVar['key']
     * 
     * @param string $key
     *            | alphanum
     * @param string $value            
     */
    public function setTplVar(string $key, $value)
    {
        // only alphanum characters allowed as key
        if (Helper::validate($key, 'alphanum') === true) {
            // This will just overwrite an existing key with the same name
            $this->tplVar[$key] = $value;
            return true;
        } else {
            
            return 'The value key must be alphanum only.';
        }
    }

    /**
     * Add a script to may load in template
     * 
     * @param string $path            
     * @param int $sort
     *            @Todo at type parameter
     */
    public function addStyle(string $path, int $sort = 5)
    {
        $this->Styles['path']['path'] = $path;
        $this->Styles['path']['sort'] = (int) $sort;
    }

    /**
     * Write the scripts to the template
     * @Todo at type parameter
     * 
     * @see addScript
     */
    public function getStyles()
    {
        usort($this->Styles, function ($a, $b) {
            return $a['sort'] - $b['sort'];
        });
        
        $output = '';
        
        if (is_array($this->Styles) && count($this->Styles)) {
            foreach ($this->Styles as $key => $value) {
                if ($value != '') {
                    $output .= ' <link href="' . $value['path'] . '" type="text/css" rel="stylesheet" />';
                }
            }
        }
        
        echo $output;
    }

    /**
     * Add a script to may load in template
     * 
     * @param string $path            
     * @param string $type            
     * @param int $sort
     *            @Todo at type parameter
     */
    public function addScript(string $path, int $sort = 5)
    {
        $this->Scripts['path']['path'] = $path;
        $this->Scripts['path']['sort'] = (int) $sort;
    }

    /**
     * Write the scripts to the template
     * @Todo at type parameter
     * 
     * @see addScript
     */
    public function getScripts()
    {
        usort($this->Scripts, function ($a, $b) {
            return $a['sort'] - $b['sort'];
        });
        
        $output = '';
        
        if (is_array($this->Scripts) && count($this->Scripts)) {
            foreach ($this->Scripts as $key => $value) {
                if ($value != '') {
                    $output .= '<script src="' . $value['path'] . '" type="text/javascript"></script>';
                }
            }
        }
        
        echo $output;
    }

    /**
     * Set the main language
     * Is set from the main controller but can get overwritten from modules
     * 
     * @param object $lang            
     */
    public function setLanguage(\Bang\Lang $lang)
    {
        $this->Lang = $lang;
        $lgn = strtolower(substr($lang->LangLoaded, 3, 5));
        
        // @todo make this dynamic
        if (! isset(CONFIG['langwhitelist'][$lgn])) {
            $lgn = 'en';
        }
        
        $this->setTplVar('thislang', $lgn);
    }

    /**
     * Change loaded language only
     */
    public function setLoadedLanguage(string $lang)
    {
        if (in_array($lang, CONFIG['langwhitelist'])) {
            $this->Lang->loadMainLanguageFile($lang);
            $this->Lang->LangLoaded = $lang;
        }
    }

    /**
     * As long as the system has no plugins for parts of modules
     * Those have to get set in a template.
     *
     * @Tag Plugins, Micro-Modules
     * @Note Future development
     *
     * 1. Test if array and keys available
     * 2. Validate module string
     * 3. Load language file for module and add to Lang
     */
    public function addModuleLanguageFromTemplate(array $modules)
    {
        if (is_array($modules) && count($modules)) {
            
            foreach ($modules as $key => $value) {
                
                if (Helper::validate($value, 'module', 65) === true) {
                    
                    if (file_exists(MOD . $value . DIRECTORY_SEPARATOR)) {
                        
                        $this->Lang->addLanguageFile(MOD . $value . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->Lang->LangLoaded);
                    }
                }
            }
        }
        
        return;
    }

    /**
     * Set a mmodules template
     */
    public function setModuleTpl(string $key, $sub = array())
    {
        // only alphanum characters allowed as key
        if (Helper::validate($key, 'alphanum') === true) {
            
            $this->ModuleTpl[$key] = $sub;
        } else {
            
            return 'The module key must be alphanum only.';
        }
    }

    /**
     * render a peace of a module
     */
    public function renderModuleCall(string $module, string $controller, string $action, array $params = array(), bool $return = true)
    {
        $this->ModuleLoader = new ModuleLoader($this->DI);
        
        // this can't have require_once
        return $this->ModuleLoader->executeModule($module, $controller, $action, $params, $return);
    }

    /**
     * Render application errors
     */
    public function renderSuccess()
    {
        $success = $this->Session->getSuccess();
        
        if (! is_array($success) && ! empty($success)) {
            
            echo '<div class="system-message success"><ul class="error">';
            echo '<li>';
            echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8');
            echo '</li>';
            echo '</ul></div>';
        } elseif (is_array($success) && count($success)) {
            
            echo '<div class="system-message success"><ul class="error">';
            foreach ($success as $key => $value) {
                echo '<li>';
                echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                echo '</li>';
            }
            echo '</ul></div>';
        }
    }

    /**
     * Render application warnings
     */
    public function renderWarnings()
    {
        $warning = $this->Session->getWarning();
        
        if (! is_array($warning) && ! empty($$warning)) {
            
            echo '<div class="system-message warning"><ul class="warning">';
            echo '<li>';
            echo htmlspecialchars($warning, ENT_QUOTES, 'UTF-8');
            echo '</li>';
            echo '</ul></div>';
        } elseif (is_array($warning) && count($warning)) {
            
            echo '<div class="system-message warning"><ul class="warning">';
            foreach ($warning as $key => $value) {
                echo '<li>';
                echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                echo '</li>';
            }
            echo '</ul></div>';
        }
    }

    /**
     * Render application errors
     */
    public function renderErrors()
    {
        $error = $this->Session->getError();
        
        if (! is_array($error) && ! empty($error)) {
            
            echo '<div class="system-message error"><ul class="error">';
            echo '<li>';
            echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
            echo '</li>';
            echo '</ul></div>';
        } elseif (is_array($error) && count($error)) {
            
            echo '<div class="system-message error"><ul class="error">';
            foreach ($error as $key => $value) {
                echo '<li>';
                echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                echo '</li>';
            }
            echo '</ul></div>';
        }
    }

    /**
     * render current users avatar
     */
    public function renderAvatar()
    {
        if (isset($this->CurrentUser['avatar']) && ! empty($this->CurrentUser['avatar']) && substr($this->CurrentUser['avatar'], 0, 4) == 'http') {
            return '<img src="' . $this->CurrentUser['avatar'] . '" alt="" class="circle responsive-img valign profile-image">';
        }
        
        return;
    }

    /**
     * Render the module content
     * This method will be called from the main template
     */
    public function renderModule()
    {
        // Loop through the module content
        if (is_array($this->ModuleTpl) && count($this->ModuleTpl)) {
            
            foreach ($this->ModuleTpl as $key => $value) {
                
                // may is array
                if (is_array($value) && count($value)) {
                    foreach ($value as $val) {
                        print $val;
                    }
                    
                    // may is not array
                } else {
                    
                    print $value;
                }
            }
        }
    }

    /**
     * Load a template and return it as string
     * 
     * {@inheritDoc}
     *
     * @see \Bang\View::loadTemplate()
     */
    public function loadTemplate(string $tpl)
    {
        if (file_exists($tpl)) {
            ob_start();
            // this can't have require_once
            require ($tpl);
            return ob_get_clean();
        }
        
        return false;
    }

    /**
     * Combine template with content and prepare for dispatch
     * 
     * {@inheritDoc}
     *
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
     * 
     * {@inheritDoc}
     *
     * @see \Bang\View::dispatch()
     */
    public function dispatch()
    {
        print $this->PagePrint;
    }

    /**
     * Must be in all classes
     * 
     * @return array
     */
    public function __debugInfo()
    {
        $reflect = new \ReflectionObject($this);
        $varArray = array();
        
        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $propName = $prop->getName();
            
            if ($propName !== 'DI' && $propName != 'CNF') {
                // print '--> '.$propName.'<br />';
                $varArray[$propName] = $this->$propName;
            }
        }
        
        return $varArray;
    }
}