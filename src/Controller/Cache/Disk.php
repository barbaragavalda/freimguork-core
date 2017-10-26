<?php
/**
 * Created by PhpStorm.
 * User: barbaragavaldabalada
 * Date: 19/03/14
 * Time: 19.32
 */

namespace Core\Controller\Cache;

/**
 * Class Disk
 *
 * Cache saved on disk
 *
 * @package Core\Controller\Cache
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class Disk extends Cache {

    /**
     * @var string $folder. folder where the cache is saved
     */
    private $folder = '';

    /**
     * init cache folder depending on which environment we are (pro or devel)
     */
    public function __construct() {
        parent::__construct();

        $environment = IS_DEV ? 'dev' : 'prod';
        $this->folder = DIR_ROOT . 'src/cache/' . $environment . '/freimguork/';
        if( !is_dir($this->folder) ){
            mkdir($this->folder, 0777, true);
        }
    }

    /**
     * saves a cache on disk
     * @param $key string       name of the cache file
     * @param $content mixed    content to be cached
     * @param $expiration int   cache expiration
     * @return bool         can be saved or not
     */
    public function saveCache($key, $content, $expiration) {
        if ($expiration > 0) {
            $expiration = time() + $expiration;
        } else {
            $expiration = 0;
        }
        $cache = array('ttl' => $expiration, 'content' => $content);

        $path = $this->getPath($key);
        $ok = file_put_contents($path, serialize($cache));
        if ($ok === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * returns the content of the cache if isn't expired
     * @param $key string   name of the cache file
     * @return null|mixed   null = cannot be returned | mixed = cache content
     */
    public function getCache($key) {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            $cache = file_get_contents($path);
            if ($cache !== false) {
                $cache = unserialize($cache);
                $ttl = $cache['ttl'];
                if( $ttl >= time() ){
                    return $cache['content'];
                } else {
                    $this->delete($key);
                }
            }
        }

        return null;
    }

    /**
     * delete a cache file
     * @param $key string   name of the cache file
     * @return bool     can be deleted or not
     */
    public function delete($key) {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * get final path of the file that contains $key cache
     * @param $key string   name of the cache file
     * @return string   full path of the cahce file
     */
    private function getPath($key) {
        return $this->folder . $key;
    }

} 