<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * 
 * Dealing with session data
 *
 */
namespace Bang;

class Session
{

    public function __construct()
    {
        @session_cache_limiter('private_no_expire');
        ini_set('session_use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        @session_name("bangsess");
        @ini_set('session.gc_maxlifetime', CONFIG['app']['sessionlength']);
        
        @session_start();
        // set session id
        $_SESSION['bangsess'] = session_id();
        $_SESSION['user'] = $_SESSION['user'] ?? array();
        $_SESSION['token'] = $_SESSION['token'] ?? '';
        $_SESSION['app'] = $_SESSION['app'] ?? array();
        $_SESSION['app']['error'] = $_SESSION['app']['error'] ?? array();
        $_SESSION['app']['warning'] = $_SESSION['app']['warning'] ?? array();
        $_SESSION['app']['success'] = $_SESSION['app']['success'] ?? array();
        $_SESSION['formData'] = $_SESSION['formData'] ?? array();
        
        $this->setUpGuestUser();
    }

    /**
     * Set a form token
     */
    public function setToken()
    {
        $_SESSION['token'] = Helper::generateCode(32);
    }

    /**
     * return form token
     */
    public function getToken()
    {
        return $_SESSION['token'];
    }

    /**
     * Reset token
     * 
     * @return boolean
     */
    public function resetToken()
    {
        $_SESSION['token'] = '';
        return true;
    }

    /**
     * Set up all users as guest user until they login
     */
    private function setUpGuestUser()
    {
        $user = $this->getUser();
        $ip = \Bang\Helper::getClientIp();
        
        if (\Bang\Helper::validate($ip, 'ip', 15) === false) {
            $ip = '';
        }
        
        if (! isset($user['username']) || empty($user['username'])) {
            
            $this->setToUser('id', 0);
            $this->setToUser('username', 'Guest');
            $this->setToUser('ip', $ip);
            $this->setToUser('sess', $_SESSION['bangsess']);
            $this->setToUser('session_start', date('Y-m-d H:i:s'));
        }
    }

    /**
     * return error
     */
    public function getWarning()
    {
        $warning = $_SESSION['app']['warning'];
        $_SESSION['app']['warning'] = '';
        
        return $warning;
    }

    /**
     * return error
     */
    public function getError()
    {
        $error = $_SESSION['app']['error'];
        $_SESSION['app']['error'] = '';
        
        return $error;
    }

    /**
     * return success
     */
    public function getSuccess()
    {
        $success = $_SESSION['app']['success'];
        $_SESSION['app']['success'] = '';
        
        return $success;
    }

    /**
     * set an error
     * 
     * @param string $value            
     */
    public function setError($value)
    {
        $_SESSION['app']['error'] = $value;
    }

    /**
     * set an error
     * 
     * @param string $value            
     */
    public function setWarning($value)
    {
        $_SESSION['app']['warning'] = $value;
    }

    /**
     * set success message
     * 
     * @param string $value            
     */
    public function setSuccess($value)
    {
        $_SESSION['app']['success'] = $value;
    }

    /**
     * return the form data array
     */
    public function getFormData()
    {
        return $_SESSION['formData'];
    }

    /**
     * Set post data to session for reuse on current form on error
     * So, that the form is prefilled after en error came up and the form is reloaded
     * 
     * @param array $data            
     */
    public function setPostData(array $data)
    {
        $_SESSION['formData'] = $data;
    }

    /**
     * After a post was completed, remove the post data from session
     */
    public function clearFormData()
    {
        $_SESSION['formData'] = array();
    }

    /**
     * Test if the current user has a particular permission assigned
     * 
     * @param int $id            
     */
    public function hasPermission($id)
    {
        $permissions = $this->getCurrentUsersPermisionsArray();
        
        if (is_array($permissions) && isset($permissions['allPermissions']) && isset($permissions['allPermissions'][$id])) {
            return true;
        }
        
        return false;
    }

    /**
     * Test if the current user is memeber in a particular group
     * 
     * @param int $id            
     */
    public function isMemberOfGroupWithId(int $groupid): bool
    {
        $permissions = $this->getCurrentUsersPermisionsArray();
        
        if (isset($_SESSION['user']['perms']) && is_array($_SESSION['user']['perms'])) {
            
            foreach ($_SESSION['user']['perms'] as $key => $value) {
                if (isset($value['groupid']) && (int) $value['groupid'] == (int) $groupid) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Return current user permissions
     */
    public function getCurrentUsersPermisionsArray()
    {
        if (isset($_SESSION['user']['perms']) && is_array($_SESSION['user']['perms'])) {
            return $_SESSION['user']['perms'];
        }
        
        return false;
    }

    /**
     * Set a user variable, overwrites existing keys
     * 
     * @param string $key            
     * @param mixed $value            
     */
    public function setToUser(string $key, $value)
    {
        if (\bang\Helper::validate($key, 'keys', 50) !== true) {
            throw new \Exception('Key must be alpha-numeric.');
            die();
        }
        
        $_SESSION['user'][$key] = $value;
    }

    /**
     * Add key to Session
     */
    public function set(string $key, $value)
    {
        if (\bang\Helper::validate($key, 'keys', 50) !== true) {
            throw new \Exception('Key must be alpha-numeric.');
            die();
        }
        
        $_SESSION[$key] = $value;
    }

    /**
     * Get session key
     */
    public function get(string $key)
    {
        if (\bang\Helper::validate($key, 'keys', 50) !== true) {
            throw new \Exception('Key must be alpha-numeric.');
            die();
        }
        
        return $_SESSION[$key];
    }

    /**
     * Get session user array
     */
    public function getUser()
    {
        return $_SESSION['user'];
    }

    /**
     * Return the id of the current user
     */
    public function getUserId()
    {
        if (isset($_SESSION['user']['id']) && (int) $_SESSION['user']['id'] > 0) {
            return $_SESSION['user']['id'];
        }
        
        return false;
    }

    /**
     * return curent users email address
     * 
     * @return string|boolean
     */
    public function getUserEmail()
    {
        if (isset($_SESSION['user']['email']) && ! empty($_SESSION['user']['email'])) {
            return $_SESSION['user']['email'];
        }
        
        return false;
    }

    /**
     * Return the id of the current user
     */
    public function getUserLang()
    {
        if (isset($_SESSION['user']['lang']) && $_SESSION['user']['lang'] != '') {
            return $_SESSION['user']['lang'];
        }
        
        return false;
    }

    /**
     * Test if a user is logegd in
     * 1.
     * userid test
     * 2. accountid test
     * 3 session timeout test
     */
    public function loggedIn($login = false)
    {
        $user = $this->getUser();
        
        // 1. test for user id
        if (is_array($user) && isset($user['id']) && (int) $user['id'] > 0) {
            
            // 2. test if accountid exists
            if (isset($user['accountid']) && (int) $user['accountid'] > 0) {
                
                // 3. test session time
                if (isset($user['session_start'])) {
                    
                    if ($login === true) {
                        return true;
                    }
                    
                    // test if the session has run out
                    if ((strtotime($user['session_start']) + (int) CONFIG['app']['sessionlength']) >= time()) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Destroy a session
     */
    public function logout(string $path = '/')
    {
        session_destroy();
        unset($_SESSION);
        unset($_GET);
        unset($_POST);
        $_SESSION = array();
        $_GET = array();
        $_POST = array();
        
        header('location: ' . $path);
        exit();
    }
}