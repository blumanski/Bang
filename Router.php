<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * Application router.
 * This will load the right module and action method.
 */
namespace Bang;

class Router
{

    /**
     * Routing array
     * 
     * @var array
     */
    public $Route;

    public function __construct()
    {}

    /**
     * The route will always need a module/controller/action section.
     * The method below combines the request_uri including the path and query part
     * to an array. The array will get assigned to $_GET as well.
     */
    public function getRoute()
    {
        $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '';
        
        // get request string
        $modulePath = $this->getUrlPart($_SERVER['REQUEST_URI']);
        
        // Get parameters attached to url after ?
        // This is needed for a website as may third party tools such as google search need this
        // to work properly
        $query = $this->getUrlPart($_SERVER['REQUEST_URI'], '?');
        $list = array_values(array_filter(explode("/", $modulePath), 'strlen'));
        
        parse_str($query, $qlist);
        
        // test if a different file is may requested
        $extension = pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION);
        
        // a different file is requested, bomb out
        if ($extension != '') {
            return false;
        }
        
        // The core parameters have strict rules
        if (is_array($list) && count($list)) {
            
            foreach ($list as $key => $value) {
                
                $this->setCoreParameters($key, $value);
                
                // form all other to an array
                if ($key > 2) {
                    // only uneven keys
                    if ($key % 2) {
                        
                        // dont overwrite and do not take the next value as key in the next round
                        if (! isset($this->Route[$value])) {
                            // key and next one as value
                            if (isset($list[$key + 1])) {
                                $this->Route[$value] = $list[$key + 1];
                            }
                        }
                    }
                }
            }
            
            // merge arrays together
            if (is_array($qlist) && count($qlist)) {
                $this->Route = array_merge($this->Route, $qlist);
            }
            
            // bomb out if not all three parts are given
            if (! isset($this->Route['module']) || ! isset($this->Route['controller']) || ! isset($this->Route['action'])) {
                return $this->setErrorRoute();
            }
            
            // add the variables to global $_GET;
            $_GET = $this->Route;
            
            return $this->Route;
        }
        
        return false;
    }

    /**
     * This is called from the foreach loop in getRoute
     * It will set the core parameters
     * 
     * @param int $key            
     * @param string $value            
     */
    private function setCoreParameters(int $key, string $value)
    {
        // module
        if ($key == 0 && ! empty($value) && Helper::validate($value, 'module', 65) === true) {
            $this->Route['module'] = $value;
        }
        
        // no controller, no fun
        if ($key == 1 && ! empty($value) && Helper::validate($value, 'module', 65) === true) {
            $this->Route['controller'] = $value;
        }
        
        // no action no fun
        if ($key == 2 && ! empty($value) && Helper::validate($value, 'alphanum', 100) === true) {
            $this->Route['action'] = $value;
        }
    }

    /**
     * set the route to an error
     */
    private function setErrorRoute()
    {
        $this->Route['module'] = 'error';
        $this->Route['controller'] = 'index';
        $this->Route['action'] = 'error404';
        
        return $this->Route;
    }

    /**
     *
     * @param string $str            
     * @param string $part            
     */
    private function getUrlPart($str, $part = 'url')
    {
        // The $str comes from outside and is potential dangerous
        // Using the multibyte safe version of strpos
        $quest = mb_strpos($str, '?', 0, 'UTF-8');
        
        // part with question mark wanted
        if ($quest !== false && $part == '?') {
            
            // multibyte safe
            return mb_substr($str, ($quest + 1), mb_strlen($str, 'UTF-8'));
        } elseif ($quest === false && $part != 'url') {
            // return null
            return;
            
            // part without question mark wanted
        } elseif ($part == 'url') {
            // multibyte safe
            $cmsPaths = mb_substr($str, 0, $quest, 'UTF-8');
        }
        
        if ($part == 'url') {
            if ($quest !== false) {
                // multibyte safe
                return mb_substr($str, 0, $quest, 'UTF-8');
            } else {
                
                return $str;
            }
        }
        
        // nothing above matched....
        return $str;
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