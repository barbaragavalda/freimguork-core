<?php

namespace Core\Controller\Cache;

use Core\Utils\Config;

/**
 * Class Disk
 *
 * Cache abstract class
 *
 * @package Core\Controller\Cache
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
abstract class Cache {

    private $isCaching = true;

    public function __construct() {
        $config = Config::getInstance();
        $this->isCaching = $config->get('cache', 'is_caching');
    }

    private function getKey($parameters) {
        $key = sha1(serialize($parameters));
        return $key;
    }

    /**
     * check if cache is enabled to save something on it
     * @param string $key           name of the cache file
     * @param mixed $content        content to be cached
     * @param integer $expiration   cache expiration
     * @return bool                 can be saved or not
     */
    public function set($key, $content, $expiration){
        if( $this->isCaching ){
            $key = $this->getKey($key);
            $ok =  $this->saveCache($key, $content, $expiration);
            return $ok;
        }

        return false;
    }

    /**
     * check if cache is enabled to return it's content
     * @param string $key   name of the cache file
     * @return null|mixed   null = cannot be returned | mixed = cache content
     */
    public function get($key){
        if( $this->isCaching ){
            $key = $this->getKey($key);
            $content = $this->getCache($key);
            if( $content !== false ){
                return $content;
            }
        }

        return null;
    }

    /**
     * saves cache
     * @param string $key           name of the cache file
     * @param mixed $content        to be cached
     * @param integer $expiration   cache expiration
     * @return bool                 can be saved or not
     */
    abstract protected function saveCache($key, $content, $expiration);

    /**
     * returns the content of the cache if isn't expired
     * @param string $key   name of cache
     * @return null|mixed   null = cannot be returned | mixed = cache content
     */
    abstract protected function getCache($key);

    /**
     * delete a key from cache
     * @param string $key   name of the cache file
     * @return bool         can be deleted or not
     */
    abstract protected function delete($key);

} 