<?php

namespace Core\Controller;

use Core\Controller\Cache\Disk;

/**
 * Class CacheManager
 *
 * Freimguork cache management
 *
 * @package Core\Controller
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class CacheManager {

    private $cache = null;

    private $key = null;
    private $ttl = null;

    public function __construct(){
        $this->cache = new Disk();
    }

    public function getCache($cacheDefinition) {
        if( $cacheDefinition === false ){
            return null;
        }

        $this->key = $cacheDefinition['key'];
        $this->ttl = $cacheDefinition['ttl'];
        return $this->cache->get($this->key);
    }

    public function saveCache($content){
        if( $this->cache != null ){
            $this->cache->set($this->key, $content, $this->ttl);
        }
    }

} 