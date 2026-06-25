<?php

namespace Core\Controller\Cache;

class Disk extends Cache
{

    private string $folder;

    /**
     * init cache folder depending on which environment we are (pro or devel)
     */
    public function __construct()
    {
        parent::__construct();

        $environment  = IS_DEV ? 'dev' : 'prod';
        $this->folder = DIR_ROOT . 'src/cache/' . $environment . '/freimguork/';
        if (!is_dir($this->folder)) {
            @mkdir($this->folder, 0777, true);
        }
    }

    public function saveCache(string $key, mixed $content, int $expiration): bool
    {
        if ($expiration > 0) {
            $expiration = time() + $expiration;
        } else {
            $expiration = 0;
        }
        $cache = array('ttl' => $expiration, 'content' => $content);

        $path = $this->getPath($key);
        if (file_exists($this->folder)) {
            $ok = file_put_contents($path, serialize($cache));
            if ($ok !== false) {
                return true;
            }
        }
        return false;
    }

    public function getCache(string $key): mixed
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            $cache = file_get_contents($path);
            if ($cache !== false) {
                $cache = unserialize($cache);
                $ttl   = $cache['ttl'];
                if ($ttl >= time()) {
                    return $cache['content'];
                } else {
                    $this->delete($key);
                }
            }
        }

        return null;
    }

    public function delete(string $key): bool
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * get final path of the file that contains $key cache
     *
     * @param string $key name of the cache file
     *
     * @return string       full path of the cahce file
     */
    private function getPath(string $key): string
    {
        return $this->folder . $key;
    }

} 