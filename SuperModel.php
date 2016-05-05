<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-01-02
 * 
 * Super class which is extends all model classes
 * Not much in here yet... work in progress...
 */
namespace Bang;

Use Exception;

class SuperModel
{
    /**
     * Return the database table name which is to inject
     * into the query
     * This is using a white list generated from the table names in the db.
     * 1. Get whitelist
     * 2. Validate $key against whitelist array
     * 3. return db table including the table suffix
     * 4. bomb out with error
     */
    protected function addTable(string $key)
    {
        $whitelist = CONFIG['database']['whitelist'];
        
        // key exists in whitelist and is not null
        if (isset($whitelist[CONFIG['database']['suffix'] . $key])) {
            return CONFIG['database']['suffix'] . $key;
        }
        
        throw new Exception("Database Table error");
    }
}