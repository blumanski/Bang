<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-04-03
 *
 * This class is a wrapper for aws s3 client.
 * This class can get called from controllers to manage uploads/delete/listing of files
 * on Amazon S3 Servers
 */
namespace Bang\Tools;

Use Bang\Helper;

class Awss3
{

    /**
     * S3 Client
     * 
     * @var object
     */
    private $Client;

    /**
     * Keep the config array for aws
     * 
     * @var array
     */
    private $AWSConfig = array();

    public function __construct()
    {
        $this->AWSConfig = [
            'version' => 'latest',
            'region' => CONFIG['awss3']['region'],
            'credentials' => [
                'key' => CONFIG['awss3']['s3key'],
                'secret' => CONFIG['awss3']['secret']
            ]
        ];
        
        // Create an SDK class used to share configuration across clients.
        $sdk = new \Aws\Sdk($this->AWSConfig);
        
        // Create an Amazon S3 client using the shared configuration data.
        $this->Client = $sdk->createS3();
    }

    /**
     * Upload an object to aws s3 server
     * 
     * @param string $filePath
     *            - $_FILES['file']['tmp_name']
     * @param string $filename
     *            - $_FILES['file']['name']
     * @param string $destinationPath
     *            - subfolder to save the file
     * @param string $cachetime
     *            - cache time in strtotime format
     */
    public function putObject(string $filePath, string $filename, string $destinationPath, string $cachetime = '+1 week')
    {
        $expire = gmdate("D, d M Y H:i:s", strtotime($cachetime));
        
        // sanitize filename
        $filename = Helper::sanitizeUrl($filename);
        
        // make sure the subfolder path is right
        if (! empty($destinationPath)) {
            if (substr($destinationPath, 0, 1) != '/') {
                $destinationPath = '/' . $destinationPath;
            }
            
            if (substr($destinationPath, - 1) != '/') {
                $destinationPath = $destinationPath . '/';
            }
        }
        
        try {
            
            // Send a PutObject request and get the result object.
            $result = $this->Client->putObject([
                'Bucket' => CONFIG['awss3']['bucket'],
                'Key' => CONFIG['awss3']['subfolder'] . $destinationPath . $filename,
                'Body' => 'this is the body!',
                'SourceFile' => $filePath,
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ACL' => 'public-read',
                'CacheControl' => 'public, max-age=604800, must-revalidate',
                'Expires' => $expire
            ]);
            
            if (is_object($result) && isset($result['ObjectURL'])) {
                
                return $result['ObjectURL'];
            }
        } catch (\S3Exception $e) {
            
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('AWS-S3', $message, __METHOD__ . ' - Line: ' . __LINE__);
        }
        
        return false;
    }

    /**
     * Upload an object to aws s3 server
     * 
     * @param
     *            string Body - Text or otehr content
     * @param string $filename
     *            - $_FILES['file']['name']
     * @param string $destinationPath
     *            - subfolder to save the file
     * @param string $cachetime
     *            - cache time in strtotime format
     */
    public function putObjectAsString(string $body, string $filename, string $destinationPath, string $cachetime = '+1 week')
    {
        $expire = gmdate("D, d M Y H:i:s", strtotime($cachetime));
        
        // sanitize filename
        $filename = Helper::sanitizeUrl($filename);
        
        // make sure the subfolder path is right
        if (! empty($destinationPath)) {
            if (substr($destinationPath, 0, 1) != '/') {
                $destinationPath = '/' . $destinationPath;
            }
            
            if (substr($destinationPath, - 1) != '/') {
                $destinationPath = $destinationPath . '/';
            }
        }
        
        try {
            
            // Send a PutObject request and get the result object.
            $result = $this->Client->putObject([
                'Bucket' => CONFIG['awss3']['bucket'],
                'Key' => CONFIG['awss3']['subfolder'] . $destinationPath . $filename,
                'Body' => $body,
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ACL' => 'public-read',
                'CacheControl' => 'public, max-age=604800, must-revalidate',
                'Expires' => $expire
            ]);
            
            if (is_object($result) && isset($result['ObjectURL'])) {
                
                return $result['ObjectURL'];
            }
        } catch (\S3Exception $e) {
            
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('AWS-S3', $message, __METHOD__ . ' - Line: ' . __LINE__);
        }
        
        return false;
    }

    /**
     * Get all files from a destination "directory/object"
     * 
     * @param string $type            
     * @return array
     */
    public function getObjectList(string $subdir = 'images')
    {
        $newArray = array();
        
        $iterator = $this->Client->getIterator('ListObjects', array(
            'Bucket' => CONFIG['awss3']['bucket'],
            'Prefix' => CONFIG['awss3']['subfolder'] . '/' . $subdir . '/'
        ));
        
        $i = 0;
        foreach ($iterator as $key => $value) {
            $newArray[strtotime($value['LastModified']) . '-' . $i]['filename'] = $value['Key'];
            $newArray[strtotime($value['LastModified']) . '-' . $i]['size'] = $value['Size'];
            $i ++;
        }
        
        krsort($newArray);
        
        return $newArray;
    }

    /**
     * Delete object from s3
     * 
     * @param string $filename            
     */
    public function deleteObject(string $filename)
    {
        // empty filename bomb out as it could potentially delete a
        // whole "directory" (object path)
        if (empty($filename)) {
            return false;
        }
        
        $bucket = CONFIG['awss3']['bucket'];
        if (substr($bucket, 0, - 1) == '/') {
            $bucket = substr($bucket, 0, - 1);
        }
        
        try {
            
            $result = $this->Client->deleteObject(array(
                'Bucket' => $bucket,
                'Key' => $filename
            ));
            
            if (is_array($result) && $result['DeleteMarker'] === true) {
                return true;
            }
        } catch (\S3Exception $e) {
            
            $message = $e->getMessage();
            $message .= $e->getTraceAsString();
            $message .= $e->getCode();
            $this->ErrorLog->logError('AWS-S3', $message, __METHOD__ . ' - Line: ' . __LINE__);
        }
        
        return false;
    }

    /**
     * Test if an object already exists
     * 
     * @param string $name
     *            - filename including sub directories
     */
    public function fileExists(string $name)
    {
        try {
            $result = $this->S3->headObject(array(
                'Bucket' => $this->apiConfig['s3bucket'],
                'Key' => $key
            ));
            
            if (is_object($result)) {
                return true;
            }
        } Catch (\Aws\S3\Exception\S3Exception $e) {
            
            //
        }
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