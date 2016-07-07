<?php
/**
 * @author Oliver Blum <blumanski@gmail.com>
 * @date 2016-04-03
 *
 * Very simple way to search the google book api for some useful data regardign a book
 * 
 * Is work in progress
 */
namespace Bang\Tools;

class Books
{

    public function __construct()
    {}

    /**
     * Get book authors from SibnDb
     * May use this at one stage
     * 
     * @param string $author            
     */
    public function searchIsbnDbAusthor(string $author): array
    {
        $authors = array();
        
        if (isset(CONFIG['isbndb']) && isset(CONFIG['isbndb']['apikey']) && ! empty(CONFIG['isbndb']['apikey'])) {
            
            $result = file_get_contents('http://www.isbndb.com/api/authors.xml?access_key=' . CONFIG['isbndb']['apikey'] . '&index1=name&value1=' . $author);
            
            $result = simplexml_load_string($result);
            $result = json_encode($result);
            $result = json_decode($result, true);
            
            if (is_array($result) && isset($result['AuthorList'])) {
                
                if (isset($result['AuthorList']) && isset($result['AuthorList']['AuthorData'])) {
                    
                    $last = '';
                    
                    foreach ($result['AuthorList']['AuthorData'] as $key => $value) {
                        
                        if (isset($value['Name'])) {
                            
                            if (levenshtein($last, $value['Name']) > 2) {
                                $new[$key]['name'] = $value['Name'];
                            }
                            
                            $last = $value['Name'];
                        }
                    }
                }
                
                print '<pre>';
                print_r($new);
                print '</pre>';
                
                die();
            }
        }
        
        return $result;
    }

    /**
     * Get book information from google books
     * <ay use it, may not
     * 
     * @param string $searchString            
     */
    public function searchGoogleBooks(string $searchString): array
    {
        $new = array();
        
        if (isset(CONFIG['google']) && isset(CONFIG['google']['bookApiKey']) && ! empty(CONFIG['google']['bookApiKey'])) {
            
            $result = file_get_contents("https://www.googleapis.com/books/v1/volumes?key=" . CONFIG['google']['bookApiKey'] . "&q=" . $searchString);
            
            print '<pre>';
            print_r($result);
            print '</pre>';
            
            die();
            
            $result = json_decode($result, true);
            
            foreach ($result['items'] as $key => $value)
                
                if (isset($value['volumeInfo'])) {
                    
                    if (isset($value['volumeInfo']['title'])) {
                        // print '<strong>'.$value['volumeInfo']['title'].'</strong><br />';
                    }
                    
                    if (isset($value['volumeInfo']['authors']) && is_array($value['volumeInfo']['authors'])) {
                        
                        foreach ($value['volumeInfo']['authors'] as $val) {
                            $new[] = $val;
                        }
                    } else {
                        
                        if (isset($value['volumeInfo']['authors'])) {
                            $new[] = $value['volumeInfo']['authors'];
                        }
                    }
                }
        }
        
        $new = array_flip($new);
        
        return $new;
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