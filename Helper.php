<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * Helper class for static tasks, basically all functions in here are static.
 */

Namespace Bang;

class Helper
{
    
    /**
     * Return true if a request is an ajax request
     * @return boolean
     */
    public static function isAjax()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        if(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }
        
        return false;
    }

    /**
     * Is Joson, may a better option later
     * @param string $string
     */
    public static function isJson(string $string) : bool 
    {
    	if((int)$string > 0) {
    		return false;
    	}
    	
    	@json_decode($string);
    	return (json_last_error() == JSON_ERROR_NONE);
    }
    
    /**
     * 
     * @param string $string
     * @param int $count
     * @param string $append
     * @return string|\Bang\string|string
     */
    public static function cutString(string $string, int $count, string $append = NULL)
    {
    	if(strlen($string) > (int)$count) {
    		$string = mb_substr($string, 0, (int)$count).''.$append;
    		return $string;
    	}
    
    	return $string;
    }
    
    /**
     * Decode ajax vars which may have been encoded
     * @param array $data
     */
    public static function prepareAjaxValues(array $data)
    {
    	if (is_array($data) && count($data)) {
    		foreach ($data as $key => $value) {
    			$data[$key] = rawurldecode($value);
    		}
    
    		return $data;
    	}
    
    	return false;
    }
    
    /**
     * Redirect a page
     */
    public static function redirectTo($url)
    {
    	if($url == '//') {
    		$url = '/';
    	}
    	header("HTTP/1.1 303 See Other");
        header('location:'.$url);
        exit;
    }
    
    
    public static function getLocationById($ip) {
    	$url = 'http://api.ipinfodb.com/v3/ip-city/?key='.CONFIRM['app']['ipinfodbapi'].'&ip='.$ip.'&format=json';
    
    	$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec($ch);
		curl_close($ch);
    	
		return @json_decode($data, true);
    }
    
    /**
     * Found this on: http://stackoverflow.com/a/31107425/5294587
     * @param int $length
     */
    public static function generateCode(int $length)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        
        return $str;
    }
    
    
    /**
     * Get the ip address of a request
     */
    public static function getClientIp() 
    {
    	$ipaddress = '';
    	
    	if (isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
    		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    		
    	} else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			
    	} else if(isset($_SERVER['HTTP_X_FORWARDED']) && filter_var($_SERVER['HTTP_X_FORWARDED'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			
    	} else if(isset($_SERVER['HTTP_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			
    	} else if(isset($_SERVER['HTTP_FORWARDED']) && filter_var($_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
			
    	} else if(isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    		
			$ipaddress = $_SERVER['REMOTE_ADDR'];
			
    	} else {
    		
			$ipaddress = 'UNKNOWN';
    	}
    	
		return $ipaddress;
    }
    
    /**
     * @Note $_GET was already transformed by router
     * The core parameters are validated by the strict application rules (module/controller/action)
     * All other variables are still raw and will get dealt with where there are used.
     * @see $this->Router->getRoute()
     * 
     * @Note $_POST data is unchanged and dealt with when used
     * 
     * @param string $type
     * @return unknown|multitype:
     */
    public static function getRequestParams(string $type)
    {
        $_SERVER['PATH_INFO'] = $_SERVER['PATH_INFO'] ?? '';
        
        $request = explode("/", mb_substr($_SERVER['PATH_INFO'], 1, mb_strlen($_SERVER['PATH_INFO'], 'UTF-8'), 'UTF-8'));

        switch(strtolower($type)) {
            
            case 'get' :
                return $_GET;
            break;
            
            case 'post' :
                return $_POST;
            break;
            
            case 'files' :
                return $_FILES;
            break;
        }
    }
    
    /**
     * Return 
     * @param string $string
     * @param int $cost
     */
    public static function passwordHash(string $string, int $cost)
    {
        if((int)$cost > 15) {
            return false;
        }
        
        return password_hash($string, PASSWORD_BCRYPT, array(
            "cost" => (int)$cost
        ));
    }
    
    /**
     * Validate a value using a pattern or ruleset
     */
    public static function validate(string $value, string $type, int $max = 100)
    {
    	if(mb_strlen($value, 'UTF-8') > $max) {
    		return false;
    	}
    	
    	switch($type) {
    		
    		// raw text
    		case 'raw':
    			return true;
    		break;
    		
    		case 'ip':
    				
    			if (filter_var($value, FILTER_VALIDATE_IP)) {
    				return true;
    			}
    			
    		break;
    		
    		case 'username':
    			// use unicode to validate username, well, allows language specific usernames too
    			// String must start with letter, upper or lower case
    			// String can have hyphen but not on the start or end
    			// string can contain numbers
    			// preg_match retruns 1|0 instead of bool
    			return preg_match('/^[\p{Lu}|\p{Ll}][\p{L}|\p{N}]*(?:\p{Pd}[\p{L}|\p{N}]+)*$/u', $value) === 1 ? true : false;
    			
    			// Alternative code or fallback, more conservative version but still utf-8
    			//return preg_match('/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/u', $value);
    			
    		break;
    		
    		case 'email':
    		    // filter returns the filtered value instead of true
    		    if(filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
    		        return true;
    		    }
    		break;
    		
    		// alpha
    		case 'alpha':
    			if(ctype_alpha($value)) {
    				return true;
    			}
    		break;
    		
    		case 'module' :
    			if(ctype_alpha($value)) {
    				return true;
    			}
    			
    		break;
    		
    		case 'password':
    			if (strlen($value) > 7) {
    				// > 5
    				if (preg_match('|[A-Z]|', $value)) {
    					// at least one uppercase
    					if (preg_match('|[0-9]|', $value)) {
    						// at least one number
    						return true;
    					}
    				}
    			}
    			return false;
    		break;
    		
    		case 'keys':
    		      
    		    $allowed = array("-", "_");
    		    
    		    if (ctype_alnum(str_replace($allowed, '', $value ))) {
    		        return true;
    		    }
    		    
    		break;
    		
    		// alpha numeric
    		case 'alphanum':
    			if(ctype_alnum($value)) {
                    return true;
                }
            break;
            
            // integer
    		case 'int':
    			if (filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    return true;
                }
            break;
    	}
    	
    	return false;
    }
    
    /**
     * Sanitize a filename
     * @param string $string
     * @param bool $force_lowercase
     * @param bool $anal
     */
    public static function sanitizeUrl(string $string, bool $force_lowercase = true, bool $anal = false)
    {
    	// remove accents first
    	$string = self::replace_accents($string);
    	$string = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $string);
    
    	$strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "=", "+", "[", "{", "]",
    			"}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
    			"Ã¢â‚¬â€�", "Ã¢â‚¬â€œ", ",", "<", ">", "/", "?");
    	$clean = trim(str_replace($strip, "", strip_tags($string)));
    	$clean = preg_replace('/\s+/', "-", $clean);
    	$clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean ;
    	return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
    }
    
    /**
     * Replace accesnts from string
     * @param unknown $var
     * @return mixed
     */
    public static function replace_accents(string $string){ 
	    
	    $replace = array(
	    		'&amp;' => 'and',   '@' => 'at',    '©' => 'c', '®' => 'r', 'À' => 'a',
	    		'Á' => 'a', 'Â' => 'a', 'Ä' => 'a', 'Å' => 'a', 'Æ' => 'ae','Ç' => 'c',
	    		'È' => 'e', 'É' => 'e', 'Ë' => 'e', 'Ì' => 'i', 'Í' => 'i', 'Î' => 'i',
	    		'Ï' => 'i', 'Ò' => 'o', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ö' => 'o',
	    		'Ø' => 'o', 'Ù' => 'u', 'Ú' => 'u', 'Û' => 'u', 'Ü' => 'u', 'Ý' => 'y',
	    		'ß' => 'ss','à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'å' => 'a',
	    		'æ' => 'ae','ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
	    		'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ò' => 'o', 'ó' => 'o',
	    		'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u',
	    		'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'p', 'ÿ' => 'y', 'Ā' => 'a',
	    		'ā' => 'a', 'Ă' => 'a', 'ă' => 'a', 'Ą' => 'a', 'ą' => 'a', 'Ć' => 'c',
	    		'ć' => 'c', 'Ĉ' => 'c', 'ĉ' => 'c', 'Ċ' => 'c', 'ċ' => 'c', 'Č' => 'c',
	    		'č' => 'c', 'Ď' => 'd', 'ď' => 'd', 'Đ' => 'd', 'đ' => 'd', 'Ē' => 'e',
	    		'ē' => 'e', 'Ĕ' => 'e', 'ĕ' => 'e', 'Ė' => 'e', 'ė' => 'e', 'Ę' => 'e',
	    		'ę' => 'e', 'Ě' => 'e', 'ě' => 'e', 'Ĝ' => 'g', 'ĝ' => 'g', 'Ğ' => 'g',
	    		'ğ' => 'g', 'Ġ' => 'g', 'ġ' => 'g', 'Ģ' => 'g', 'ģ' => 'g', 'Ĥ' => 'h',
	    		'ĥ' => 'h', 'Ħ' => 'h', 'ħ' => 'h', 'Ĩ' => 'i', 'ĩ' => 'i', 'Ī' => 'i',
	    		'ī' => 'i', 'Ĭ' => 'i', 'ĭ' => 'i', 'Į' => 'i', 'į' => 'i', 'İ' => 'i',
	    		'ı' => 'i', 'Ĳ' => 'ij','ĳ' => 'ij','Ĵ' => 'j', 'ĵ' => 'j', 'Ķ' => 'k',
	    		'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'l', 'ĺ' => 'l', 'Ļ' => 'l', 'ļ' => 'l',
	    		'Ľ' => 'l', 'ľ' => 'l', 'Ŀ' => 'l', 'ŀ' => 'l', 'Ł' => 'l', 'ł' => 'l',
	    		'Ń' => 'n', 'ń' => 'n', 'Ņ' => 'n', 'ņ' => 'n', 'Ň' => 'n', 'ň' => 'n',
	    		'ŉ' => 'n', 'Ŋ' => 'n', 'ŋ' => 'n', 'Ō' => 'o', 'ō' => 'o', 'Ŏ' => 'o',
	    		'ŏ' => 'o', 'Ő' => 'o', 'ő' => 'o', 'Œ' => 'oe','œ' => 'oe','Ŕ' => 'r',
	    		'ŕ' => 'r', 'Ŗ' => 'r', 'ŗ' => 'r', 'Ř' => 'r', 'ř' => 'r', 'Ś' => 's',
	    		'ś' => 's', 'Ŝ' => 's', 'ŝ' => 's', 'Ş' => 's', 'ş' => 's', 'Š' => 's',
	    		'š' => 's', 'Ţ' => 't', 'ţ' => 't', 'Ť' => 't', 'ť' => 't', 'Ŧ' => 't',
	    		'ŧ' => 't', 'Ũ' => 'u', 'ũ' => 'u', 'Ū' => 'u', 'ū' => 'u', 'Ŭ' => 'u',
	    		'ŭ' => 'u', 'Ů' => 'u', 'ů' => 'u', 'Ű' => 'u', 'ű' => 'u', 'Ų' => 'u',
	    		'ų' => 'u', 'Ŵ' => 'w', 'ŵ' => 'w', 'Ŷ' => 'y', 'ŷ' => 'y', 'Ÿ' => 'y',
	    		'Ź' => 'z', 'ź' => 'z', 'Ż' => 'z', 'ż' => 'z', 'Ž' => 'z', 'ž' => 'z',
	    		'ſ' => 'z', 'Ə' => 'e', 'ƒ' => 'f', 'Ơ' => 'o', 'ơ' => 'o', 'Ư' => 'u',
	    		'ư' => 'u', 'Ǎ' => 'a', 'ǎ' => 'a', 'Ǐ' => 'i', 'ǐ' => 'i', 'Ǒ' => 'o',
	    		'ǒ' => 'o', 'Ǔ' => 'u', 'ǔ' => 'u', 'Ǖ' => 'u', 'ǖ' => 'u', 'Ǘ' => 'u',
	    		'ǘ' => 'u', 'Ǚ' => 'u', 'ǚ' => 'u', 'Ǜ' => 'u', 'ǜ' => 'u', 'Ǻ' => 'a',
	    		'ǻ' => 'a', 'Ǽ' => 'ae','ǽ' => 'ae','Ǿ' => 'o', 'ǿ' => 'o', 'ə' => 'e',
	    		'Ё' => 'jo','Є' => 'e', 'І' => 'i', 'Ї' => 'i', 'А' => 'a', 'Б' => 'b',
	    		'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ж' => 'zh','З' => 'z',
	    		'И' => 'i', 'Й' => 'j', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n',
	    		'О' => 'o', 'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u',
	    		'Ф' => 'f', 'Х' => 'h', 'Ц' => 'c', 'Ч' => 'ch','Ш' => 'sh','Щ' => 'sch',
	    		'Ъ' => '-', 'Ы' => 'y', 'Ь' => '-', 'Э' => 'je','Ю' => 'ju','Я' => 'ja',
	    		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
	    		'ж' => 'zh','з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l',
	    		'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
	    		'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
	    		'ш' => 'sh','щ' => 'sch','ъ' => '-','ы' => 'y', 'ь' => '-', 'э' => 'je',
	    		'ю' => 'ju','я' => 'ja','ё' => 'jo','є' => 'e', 'і' => 'i', 'ї' => 'i',
	    		'Ґ' => 'g', 'ґ' => 'g', 'א' => 'a', 'ב' => 'b', 'ג' => 'g', 'ד' => 'd',
	    		'ה' => 'h', 'ו' => 'v', 'ז' => 'z', 'ח' => 'h', 'ט' => 't', 'י' => 'i',
	    		'ך' => 'k', 'כ' => 'k', 'ל' => 'l', 'ם' => 'm', 'מ' => 'm', 'ן' => 'n',
	    		'נ' => 'n', 'ס' => 's', 'ע' => 'e', 'ף' => 'p', 'פ' => 'p', 'ץ' => 'C',
	    		'צ' => 'c', 'ק' => 'q', 'ר' => 'r', 'ש' => 'w', 'ת' => 't', '™' => 'tm'
	    );
	    
	    return strtr($string, $replace);
	}
    
    /**
     * generate list of timezones with offset
     */
    public static function getTiemzoneList()
    {
		$timezones = \DateTimeZone::listIdentifiers( \DateTimeZone::ALL );
    	
		$timezone_offsets = array();
		foreach( $timezones as $timezone ) {
			$tz = new \DateTimeZone($timezone);
			$timezone_offsets[$timezone] = $tz->getOffset(new \DateTime);
    	}
    	
		// sort timezone by offset
		asort($timezone_offsets);
    	
		$timezone_list = array();
		foreach( $timezone_offsets as $timezone => $offset ) {
			$offset_prefix = $offset < 0 ? '-' : '+';
			$offset_formatted = gmdate( 'H:i', abs($offset) );    	

			$pretty_offset = "UTC${offset_prefix}${offset_formatted}";
			$timezone_list[$timezone] = "(${pretty_offset}) $timezone";
    	}
    	
    	return $timezone_list;
    }
    
}