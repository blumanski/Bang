<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-04-03
 *
 * Wrapper for Pusher.com
 */
namespace Bang\Tools;

class PusherWrapper
{

    /**
     * Pusher Client
     * 
     * @var array
     */
    private $Push = array();

    public function __construct()
    {
        $options = array(
            'cluster' => CONFIG['pusher']['cluster'],
            'encrypted' => CONFIG['pusher']['encrypted']
        );
        
        $this->Push = new \Pusher(CONFIG['pusher']['key'], CONFIG['pusher']['secret'], CONFIG['pusher']['appid'], $options);
    }

    /**
     * Trigger a message event
     * 
     * @param string $message            
     * @param string $channel            
     * @param string $event            
     */
    public function triggerEvent(string $channel, string $event, array $data)
    {
        return $this->Push->trigger($channel, $event, $data);
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
            
            if ($propName !== 'DI') {
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