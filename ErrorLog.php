<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 *
 * This error logger is logging errors silently and writing them to the database.
 * I use this to log database errors and application errors.
 * This should always run as appliction monitor.
 *
 * Every single sql request has this error log in the catch condition.
 * You dont want to miss out on sql errors.
 *
 * Errors logged will show up on the backend application dashboard.
 */
namespace Bang;

Use \Bang\PdoWrapper, PDO;

class ErrorLog
{

    /**
     * Instance of pdo wrapper
     *
     * @var object
     */
    private $PdoWrapper;

    /**
     * This class is getting the the di container
     * injected via setter method, which is doing the setup of the dependencies as well.
     *
     * @param \stdClass $di
     */
    public function setDI(\stdClass $di)
    {
        $this->PdoWrapper = $di->PdoWrapper;
    }

    /**
     * Log an error to the error log table
     *
     * @param string $type
     * @param string $message
     * @param string $location
     */
    public function logError(string $type, string $message, string $location)
    {
        // Consider the configuration setting for error log
        if (CONFIG['app']['errorlog'] !== true && strtolower($type) == 'app') {
            return;
        }

        // Consider the configuration setting for db query error log
        if (CONFIG['database']['errorlog'] !== true && strtolower($type) == 'db') {
            return;
        }

        $query = "INSERT INTO `" . CONFIG['database']['suffix'] . "error_log`
                    (`type`, `message`, `location`, `logtime`)
                  VALUES
                    (:type, :message, :location, :logtime)
        ";

        $this->PdoWrapper->prepare($query);

        try {

            $this->PdoWrapper->bindValue(':type', $type, PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':message', $message, PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':location', $location, PDO::PARAM_STR);
            $this->PdoWrapper->bindValue(':logtime', date('Y-m-d H:i:s'), PDO::PARAM_STR);

            return $this->PdoWrapper->execute();
        } catch (\PDOException $e) {
            
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            die($message);
        }

        return false;
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

    /**
     * May later for clean up things
     */
    public function __destruct()
    {}
}
