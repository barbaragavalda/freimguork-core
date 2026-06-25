<?php

namespace Core\Controller\Cache;

use Core\Utils\Config;

abstract class Cache
{

    private bool $isCaching;

    public function __construct()
    {
        $config          = Config::getInstance();
        $this->isCaching = $config->get('cache', 'is_caching');
    }

    private function getKey(string $parameters): string
    {
        return sha1(serialize($parameters));
    }

    /**
     * check if cache is enabled to save something on it
     *
     * @param string $key        name of the cache file
     * @param mixed  $content    content to be cached
     * @param int    $expiration cache expiration
     *
     * @return bool                 can be saved or not
     */
    public function set(string $key, mixed $content, int $expiration): bool
    {
        if ($this->isCaching) {
            $key = $this->getKey($key);
            return $this->saveCache($key, $content, $expiration);
        }

        return false;
    }

    /**
     * check if cache is enabled to return it's content
     *
     * @param string $key name of the cache file
     *
     * @return mixed   null = cannot be returned | mixed = cache content
     */
    public function get(string $key): mixed
    {
        if ($this->isCaching) {
            $key     = $this->getKey($key);
            $content = $this->getCache($key);
            if ($content !== false) {
                return $content;
            }
        }

        return null;
    }

    /**
     * saves cache
     *
     * @param string $key        name of the cache file
     * @param mixed  $content    to be cached
     * @param int    $expiration cache expiration
     *
     * @return bool                 can be saved or not
     */
    abstract protected function saveCache(string $key, mixed $content, int $expiration): bool;

    /**
     * returns the content of the cache if isn't expired
     *
     * @param string $key name of cache
     *
     * @return mixed   null = cannot be returned | mixed = cache content
     */
    abstract protected function getCache(string $key): mixed;

    /**
     * delete a key from cache
     *
     * @param string $key name of the cache file
     *
     * @return bool         can be deleted or not
     */
    abstract protected function delete(string $key): bool;

} 