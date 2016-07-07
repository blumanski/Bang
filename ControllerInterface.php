<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-09
 *
 * Interface for all module controllers
 */
namespace Bang;

/**
 * ControllerInterface is implemented by all module controllers.
 * It defines the minimum methods that must be available in a module controller.
 */
interface ControllerInterface
{

    /**
     * All controllers must have an indexAction method
     */
    public function indexAction();

    /**
     * All controllers need permission testing
     */
    public function testPermisions();

    /**
     * All controllers need to implement this method to avoid
     * exposing data
     */
    public function __debugInfo();
}