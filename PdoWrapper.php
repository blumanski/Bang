<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * PdoWrapper class
 * This class is adding additional features to the php pdo wrapper
 * Such as result caching and slow query log
 * 
 * @todo Redis query result cache
 */

Namespace Bang;

Use PDO,
	PDOException, 
	stdClass,
    Exception;


class PdoWrapper
{
    /**
     * pdo connection
     * @var object
     */
    private $Connection;
    
    /**
     * DB statement
     * @var object
     */
    private $Statement; 
    
    /**
     * App config array
     * @var array
     */
    private $CNF = array();
    
    /**
     * Instance of Redis
     * @var object
     */
    private $Redis;
    
    /**
     * Set up the class
     * The config is injected by the main controller
     * This class is only called once, the constructor will call the connection method.
     * The application config is class internal available. This is needed to implement the memcache
     * and slow query log on/off switches.
     * 
     * @param array $cnf
     */
    public function __construct(array $cnf)
    {
        $this->CNF = $cnf;
        $this->connectDatabase();
    }
    
    /**
     * Connect to the database, if not already established.
     * PHP can handle an error here on its own.
     */
    private function connectDatabase()
    {
        if($this->Connection instanceof PDO === false) {
            
            try {
                $this->Connection = new PDO(''.$this->CNF['database']['type'].':host='.$this->CNF['database']['host'].';dbname='.$this->CNF['database']['name'].'', 
                $this->CNF['database']['user'], $this->CNF['database']['pass']);
            
            } catch (PDOException $e) {
                
            	$message = $e->getMessage();
            	$message .= $e->getTraceAsString();
            	$message .= $e->getCode();

            	exit('Can\'t connect to database.');
            }
            
            if ($this->CNF['database']['errorlog'] === true) {
                $this->Connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
                // Will be redis, soon
            if($this->CNF['database']['redis'] === true) {
            	$this->startRedis();
            }
        }
        
        return true;
    }
    
    /**
     * Prepare Redis for caching
     */
    private function startRedis()
    {
    	if(isset($this->CNF['dbredis']) && $this->CNF['dbredis']['hosts'] != '') {
    		
    		$this->Redis	= new \Redis();
    		$this->Redis->connect($this->CNF['dbredis']['hosts'], $this->CNF['dbredis']['port']);
    		$this->Redis->auth($this->CNF['dbredis']['auth']);
    	}
    }
    
    /**
     * Get the db tables names
     * Needed to create a whitelist of tables as early as possible in the application.
     */
    public function getTableNames()
    {
        $query  = "SELECT GROUP_CONCAT(DISTINCT `TABLE_NAME`) AS `tables` 
                    FROM `information_schema`.`tables`
                    WHERE 
                        `table_schema` = :dbname
        ";
        
        try {
            
            $p      = $this->Connection->prepare($query);
            $p->bindValue(':dbname', $this->CNF['database']['name'], PDO::PARAM_STR);
            $p->execute();
            $result = $p->fetch(PDO::FETCH_ASSOC);
            
            if(is_array($result) && isset($result['tables'])) {
                return array_flip(explode(',', $result['tables']));
            }
            
        } catch (\PDOException $e) {
            // if this error comes up, the application can't run
            throw new Exception("Could not fetch db tables");
        }
    
        return false;
    }
    
    /**
     * log db errors
     *
     * @param string $message
     * @param string $location
     * @param string $type
     * @return bool
     */
    public function logError($message, $location, $type)
    {
    	$query = "INSERT INTO `".$this->CNF['database']['suffix']."error_log` 
	                    (`type`, `message`, `location`, `logtime`)
	                  VALUES
	                    (:type, :message, :location, :logtime)
	        ";
	
	        $p = $this->Connection->prepare($query);
	        
	        $params = array(
	            ':type'         => $type,
	            ':message'      => $message,
	            ':location'     => $location,
	            ':logtime'      => date('Y-m-d H:i:s')
	        );
	
	        return $p->execute($params);
    }
    
    /**
     * save slow log query to db table
     */
    private function saveSlowQueryLog($data)
    {
    	if (empty($data['query'])) {
    		$data['query'] = 'Empty';
    	}
    	
    	$query = "INSERT INTO `".$this->CNF['database']['suffix']."slow_query_log`
          			(`query`, `timeused`, `method`, `class`, `date`, `line`, `inpdo`, `file`, `cached`, `backtrace`)
				  VALUES
					(:query, :timeused, :method, :class, :date, :line, :inpdo, :file, :cached, :backtrace)
		";
    	
    	$p = $this->Connection->prepare($query);
    	
    	$pdoCall = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6);
    	$pdoCall['function'] = $pdoCall[1]['function'];
    	
    	if (! isset($pdoCall[2]['class'])) {
    		$pdoCall[2]['class'] = '';
    	}
    	if (! isset($pdoCall[3]['class'])) {
    		$pdoCall[3]['class'] = '';
    	}
    	if (! isset($pdoCall[3]['function'])) {
    		$pdoCall[3]['function'] = '';
    	}
    	if (! isset($pdoCall[4]['class'])) {
    		$pdoCall[4]['class'] = '';
    	}
    	if (! isset($pdoCall[4]['function'])) {
    		$pdoCall[4]['function'] = '';
    	}
    	if (! isset($pdoCall[5]['class'])) {
    		$pdoCall[5]['class'] = '';
    	}
    	if (! isset($pdoCall[5]['function'])) {
    		$pdoCall[5]['function'] = '';
    	}
    	
    	$values = array(
    			':query'        => $data['query'],
    			':timeused'     => $data['timeused'],
    			':method'       => $data['function'],
    			':class'        => $data['class'],
    			':date'         => $data['date'],
    			':line'         => $data['line'],
    			':inpdo'        => $pdoCall['function'],
    			':file'         => $data['file'],
    			':cached'       => $data['cached'],
    			':backtrace'    => $pdoCall[5]['class'] . ' -> ' . $pdoCall[5]['function'] . ' -> ' . $pdoCall[4]['class'] . ' -> ' . $pdoCall[4]['function'] . ' -> ' . $pdoCall[3]['class'] . ' -> ' . $pdoCall[3]['function'] . ' -> ' . $pdoCall[2]['class'] . ' -> ' . $pdoCall[2]['function']
    	);
    	
    	$p->execute($values);
    	
    	return false;
    }
    
    /**
     * Test if a prepared query is a "select" query, not insert, update,,,
     * @note May have to get back to this after a while of tedting
     * Not sure if that is the best solution
     */
    private function isSelectQuery()
    {
    	// get pepared query
    	$queryString = $this->debugDumpParams();
    	// split to get the query start
    	$queryString = explode('] ', $queryString);
    	
    	// array key 1 is the real query start
    	if(is_array($queryString) && isset($queryString[1])) {
    		
    		if(strtolower(substr($queryString[1], 0, 7)) == 'select ') {
    			return true;
    		}
    	}
    	
    	return false;
    }
    
    /**
     * Prepare statement
     * @param string $query
     */
    public function prepare(string $query)
    {
        $this->Statement = $this->Connection->prepare($query);
    }
    
    /**
     * close cursor
     */
    public function closeCursor()
    {
        if($this->Statement instanceof PDOStatement === true) {
            return $this->sth->closeCursor();
        }
    }
    
    /**
     * return an SQL prepared command 
     */
    public function debugDumpParams()
    {
        ob_start();
        $this->Statement->debugDumpParams();
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
    
    /**
     * return affeted rows
     */
    public function rowCount()
    {
        return $this->Statement->rowCount();
    }
    
    /**
     * Test if a transaction is already open
     */
    public function inTransaction()
    {
        return $this->Connection->inTransaction();
    }
    
    /**
     * start a transaction
     */
    public function beginTransaction()
    {
        $this->Connection->beginTransaction();
    }
    
    /**
     * roll back
     */
    public function rollBack()
    {
        $this->Connection->rollBack();
        $this->closeCursor();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->Connection->commit();
    }
    
    /**
     * Get the insert id
     */
    public function lastInsertId()
    {
        return $this->Connection->lastInsertId();
    }
    
    /**
     * Quote a value
     * @param mixed $value
     */
    public function quote($value)
    {
        return $this->Connection->quote($value);
    }
    
    /**
     * Bind Value
     * @param string $parameter
     * @param mixed $value
     * @param string $dataType
     */
    public function bindValue($param, $value, $type)
    {
        $this->Statement->bindValue($param, $value, $type);
    }
    
    /**
     * Bind Value
     * @param string $parameter
     * @param mixed $value
     * @param string $dataType
     */
    public function bindParam($param, $value, $type)
    {
        $this->Statement->bindParam($param, $value, $type);
    }
    
    /**
     * Execute an SQL statement and return the number of affected rows
     * Should not be used, use prepared statements
     * @param string $query
     */
    public function exec($query)
    {
    	return $this->Statement->exec($query);
    	
    }
    
    /**
     * Execute query
     * This method has a few configuration conditions happening
     * It may has to get split into a few functions if possible 
     *
     * @param array $params
     * @return mixed
     */
    public function execute($params = array(), $key = '', $nocatch = false)
    {
	        $keepState = false;
	        
	        // Check first memcached if it it set up and wanted
	        if ($this->CNF['database']['redis'] === true && $key != '') {
	        	
	            // create unique key which identifies a cached query
	            $uniqueKey = md5($key);
	            
	            // in case the key exists it will load the last result set without executing the query via pdo
	            $fromCache = $this->Redis->get($this->CNF['dbredis']['prefix'].$uniqueKey);
	            
	            $fromCache = json_decode($fromCache, true);
	            
	            if (is_array($fromCache) && count($fromCache)) {
	                // have result and bomb out with an result set
	                return $fromCache;
	            }
	        } 
	        
	        // In case this query is supposed to get slow logged
	        // I need to know if this is a select query as only select queries are supposed to be 
	        // logged and assest.
	        $isSelect = false;
	        
	        // Test if this is a select query
	        if($this->isSelectQuery() === true) {
	            $isSelect = true;
	        }
	    
	        // Log all select queries to find slow queries or faults in the application process
	        // regarding database queries. 
	        if ($this->CNF['database']['slowlog'] === true && $isSelect === true) {
                
	            // get some debug data
	            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
	            
	            // No data, no log
	            if(is_array($bt) && isset($bt[1])) {
	                
	                // make sure everything is declared
	                $bt[1]['file']      = $bt[1]['file'] ?? '';
	                $bt[1]['line']      = $bt[1]['file'] ?? '';
	                $bt[1]['function']  = $bt[1]['file'] ?? '';
	                $bt[1]['class']     = $bt[1]['file'] ?? '';
	                 
	                $toLog 				= array();
	                $toLog['file'] 		= $bt[1]['file'];
	                $toLog['line'] 		= $bt[1]['line'];
	                $toLog['function'] 	= $bt[1]['function'];
	                $toLog['class'] 	= $bt[1]['class'];
	                $toLog['query'] 	= $this->Statement->queryString;
	                $toLog['date'] 		= date('Y-m-d H:i:s');
	                $toLog['cached'] 	= 'No';
	                // start time
	                $time_start 		= microtime(true);
	                
	            } else {
	                
	                return false;
	            }
	        }
	    
	        // if an array with params is given, take this way
	        if (is_array($params) && count($params)) {
	        	
	        	// execute
	        	// do not catch error if $nocatch is set true
	        	if($nocatch === true) {
	        		// execute the query
	        		$keepState = $this->Statement->execute($params);
	        		
	        	} else {
	        		
	        		// execute query
	        		try {
	        		
	        			$keepState = $this->Statement->execute($params);
	        			 
	        		} catch (\PDOException $e) {
	        			 
	        			$message = $e->getMessage();
	        			$message .= $e->getTraceAsString();
	        			$message .= $e->getCode();
	        			$this->logError($message, __METHOD__, 'db');
	        		}
	        	}
	    
	            // log the query to the slow query log table
	            if ($this->CNF['database']['slowlog'] === true && $isSelect === true) {
	                $time_end = microtime(true);
	                $toLog['timeused'] = round($time_end - $time_start, 10);
	                $this->saveSlowQueryLog($toLog);
	            }
	    
	            // bomb out
	            return $keepState;
	            
	            // no param array is injected, the params were bind using bindValue
	        } else {
	    
	        	// do not catch error if $nocatch is set true
	        	if($nocatch === true) {
	        		// execute the query
	        		$keepState = $this->Statement->execute();
	        		 
	        	} else {
	        		 
	        		// execute query
                    try {	        			 
	        			$keepState = $this->Statement->execute();
	        			 
	        		} catch (\PDOException $e) {
	        			 
	        			$message = $e->getMessage();
	        			$message .= $e->getTraceAsString();
	        			$message .= $e->getCode();
	        			$this->logError($message, __METHOD__, 'db');
	        		}
	        	}
	    
	            // log the query to the slow query log table
	            if ($this->CNF['database']['slowlog'] === true && $isSelect === true) {
	    
	                $time_end 			= microtime(true);
	                $toLog['timeused'] 	= round($time_end - $time_start, 10);
	                $this->saveSlowQueryLog($toLog);
	            }
	    
	            // bomb out
	            return $keepState;
	        }
        
        return false;
    }
    
    /**
     * Return result as array
     * Anyway, better to use prepared queries. 
     */
    public function query($query, $fetchmode = PDO::FETCH_OBJ)
    {
        $data = array();
        foreach($this->Connection->query($query, $fetchmode) AS $key => $value) {
            $data[$key] = $value;
        }
    
        if(count($data)) {
        	return $data;
        }
        
        return false;
    }
    
    /**
     * Fetch an assoc list and return result as array
     *
     * @param mixed $key
     * @param int $cacheTime
     */
    public function fetchAssoc($key = false, int $cacheTime = 0)
    {
    	$result = $this->Statement->fetch(PDO::FETCH_ASSOC);
    
    	// set to memcache if wanted, validate cachetime too
    	if ((int)$cacheTime > 0 && (int)$cacheTime <= (int)$this->CNF['database']['maxcachetime'] && $this->CNF['database']['redis'] === true) {
    		// memcache key
    		$key = md5($key);
    		// cache time in minutes
    		$cacheMinutes = $cacheTime * 60;
    		// add to redis for n minutes
    		$this->Redis->set($this->CNF['dbredis']['prefix'].$id, json_encode($data), (time() + (int) $cacheMinutes));
    	}
    
    	return $result;
    }
    
    /**
     * Fetch an assoc list and return result as array
     *
     * @param mixed $key
     * @param int $cacheTime
     */
    public function fetchAssocList($key = false, int $cacheTime = 0)
    {
        $result = $this->Statement->fetchAll(PDO::FETCH_ASSOC);
        
        // set to memcache if wanted, validate cachetime too
        if ((int)$cacheTime > 0 && (int)$cacheTime <= (int)$this->CNF['database']['maxcachetime'] && $this->CNF['database']['redis'] === true) {
            // memcache key
            $key = md5($key);
            // cache time in minutes
            $cacheMinutes = $cacheTime * 60;
            // add to redis for n minutes
            $this->Redis->set($this->CNF['dbredis']['prefix'].$key, json_encode($result), (time() + (int) $cacheMinutes));
        }
    
        return $result;
        
    }
    
    /**
     * fetch a result as object
     *
     * @param mixed $key
     * @param int $cacheTime
     */
    public function fetchObjectList($key = false, int $cacheTime = 0)
    {
        $result = $this->Statement->fetchAll(PDO::FETCH_OBJ);
        
    	// set to memcache if wanted, validate cachetime too
        if ((int)$cacheTime > 0 && (int)$cacheTime <= (int)$this->CNF['database']['maxcachetime'] && $this->CNF['database']['redis'] === true) {
            // memcache key
            $key = md5($key);
            // cache time in minutes
            $cacheMinutes = $cacheTime * 60;
            // add to redis for n minutes
            $this->Redis->set($this->CNF['dbredis']['prefix'].$key, json_encode($result), (time() + (int) $cacheMinutes));
        }
    
        return $result;
    }
    
    /**
     * Fetch a single row as object
     *
     * @param mixed $key
     * @param int $cacheTime
     */
    public function fetchObject($key = false, int $cacheTime = 0)
    {
        $result = $this->Statement->fetchObject('\stdClass');
        
    	// set to memcache if wanted, validate cachetime too
        if ((int)$cacheTime > 0 && (int)$cacheTime <= (int)$this->CNF['database']['maxcachetime'] && $this->CNF['database']['redis'] === true) {
            // memcache key
            $key = md5($key);
            // cache time in minutes
            $cacheMinutes = $cacheTime * 60;
            // add to redis for n minutes
            $this->Redis->set($this->CNF['dbredis']['prefix'].$key, json_encode($result), (time() + (int) $cacheMinutes));
        }
    
        return $result;
    }
    
    /**
     * fetch an associative array as listing
     *
     * @param mixed $key
     * @param int $cacheTime
     */
    public function fetchAll($key = false, int $cacheTime = 0)
    {
        $result = $this->Statement->fetchAll();
    
    	// set to memcache if wanted, validate cachetime too
        if ((int)$cacheTime > 0 && (int)$cacheTime <= (int)$this->CNF['database']['maxcachetime'] && $this->CNF['database']['redis'] === true) {
            // memcache key
            $key = md5($key);
            // cache time in minutes
            $cacheMinutes = $cacheTime * 60;
            // add to redis for n minutes
            $this->Redis->set($this->CNF['dbredis']['prefix'].$key, json_encode($result), (time() + (int) $cacheMinutes));
        }
    
        return $result;
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
     * May later
     */
    public function __destruct(){}
}

