<?php

namespace Core\Controller;

use Core\Controller\Cache\Disk;

class CacheManager
{

    private Disk $cache;

    private string $key;
    private int    $ttl;

    public function __construct()
    {
        $this->cache = new Disk();
    }

    public function getCache($cacheDefinition): mixed
    {
        if ($cacheDefinition === false) {
            return null;
        }

        $this->key = $cacheDefinition['key'];
        $this->ttl = $cacheDefinition['ttl'];
        return $this->cache->get($this->key);
    }

    public function saveCache($content): void
    {
        $this->cache->set($this->key, $content, $this->ttl);
    }

} 