<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-09
 *
 * Interface for all controllers
 */

Namespace Bang;

/**
 * ControllerInterface is implemented by all module controllers.
 * It defines the minimum methods that must be available in a module controller.
 * @author oliver
 *
 */
interface ControllerInterface 
{
    /**
     * All controllers must have an indexAction method
     */
    public function indexAction();
    
    /**
     * All controlelrs need sort of permission testing
     */
    public function testPermisions();
    
    /**
     * All controllers need to implement this method to avoid
     * exposing sensible data
     */
    public function __debugInfo();
}