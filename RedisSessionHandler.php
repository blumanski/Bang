<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-02-07
 * 
 * Session Handling using Redis key value storage
 */
namespace Bang;

class RedisSessionHandler implements \SessionHandlerInterface
{

    /**
     * Redis instance
     * 
     * @param
     *            object
     */
    private $Redis;

    /**
     * Project prefix
     * 
     * @var string
     */
    private $Prefix;

    /**
     * Session livetime setting
     * 
     * @var string
     */
    private $Ttl;

    /**
     * Set up the class
     * 
     * @param
     *            object Redis $redis
     * @param string $prefix            
     */
    public function __construct(\Redis $redis, array $cnf)
    {
        $this->Redis = $redis;
        $this->Prefix = $cnf['sessionredis']['prefix'];
        $this->Ttl = $cnf['app']['sessionlength'];
    }

    /**
     * No actions required in this callback
     * 
     * @param string $savePath            
     * @param string $sessionName            
     * @return boolean
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Close session callback
     * No action required
     * 
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data by id
     * 
     * @param string $id            
     */
    public function read($id)
    {
        return $this->Redis->get($this->Prefix . $id);
    }

    /**
     * Write session
     * 
     * @param string $id            
     * @param string $data            
     */
    public function write($id, $data)
    {
        return $this->Redis->set($this->Prefix . $id, $data, $this->Ttl);
    }

    /**
     * Delete particular session
     * 
     * @param int $id            
     */
    public function destroy($id)
    {
        $this->Redis->delete($id);
    }

    /**
     * Garbage Collection callback
     * Redis will expire the sessions, no action needed
     * 
     * @param int $maxLifetime            
     * @return boolean
     */
    public function gc($maxLifetime)
    {
        return true;
    }
}