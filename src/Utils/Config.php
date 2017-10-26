<?php 

namespace Core\Utils;

/**
 * Class Language
 *
 * Load project configuration
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Config{

    /**
     * @var Config $instance.  Instance of the singleton
     */
    private static $instance;

    /**
     * @var array $projects. Project's configuration
     */
    private $projects = array();

    /**
     * @var array $config. Content of the config files (databases, email configurations,...)
     */
    private $config = array();

    /**
     * @var string $domain. Base domain
     */
    private $domain = '';

    /**
     * @var string $folder. Directory where must be all config files
     */
    private $folder = '';

    /**
     * load project configurations
     */
    private function __construct(){
        $this->folder = DIR_ROOT . 'config/';

        $projectFile = 'projects' . (( IS_DEV ) ? '.dev' : '.prod') . '.php';
        $this->projects = $this->load($this->folder . $projectFile);
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @return \Core\Config
     */
    public static function getInstance(){
        if( self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    /**
     * returns the projects configuration
     * @return array
     */
    public function getProjects(){
        return $this->projects;
    }

    /**
     * returns the base url
     * @return string
     */
    public function getDomain(){
        return $this->domain;
    }

    /**
     * sets the base url
     * @param $projectURL base URL
     * @param $language current language
     */
    public function setDomain($domain){
        $this->domain = $domain;
    }

    /**
     * load all the configurations on a specific folder
     * @param $folders
     */
    public function loadConfigs( $folders ){
        foreach($folders as $folder){
            // common folder
            $folder = $this->folder . $folder . '/';
            $this->loadFolder($folder);

            // environment folder
            $folder .= ( IS_DEV ) ? 'dev/' : 'prod/';
            if( is_dir($folder) ){
                // scan folder if exists
                $this->loadFolder($folder);
            }
        }
    }

    private function loadFolder($folder){
        $files = scandir($folder);
        foreach($files as $file){
            if( !is_dir($folder . $file) && !in_array($file, array('.', '..', '.DS_Store')) ){
                // load config
                $this->config = array_merge($this->config, $this->load($folder . $file));
            }
        }
    }

    /**
     * returns the value for the given key
     * @param $key
     * @return string
     */
    public function get( $key ){
        if( array_key_exists($key, $this->config) )
            return $this->config[$key];
        else
            return '';
    }

    /**
     * returns the value for the given key
     * @param $key
     * @return string
     */
    public function __get( $key ){
        return $this->get($key);
    }

    /**
     * load the config named $file_name
     * @param $file
     * @return mixed
     * @throws \Exception
     */
    private function load($file){
        if( @include($file) ) {
            return $config;
        }else{
            throw new \Exception("<span style='color:red'>The config file that you are trying to load (<em>".$file."</em>), doesn't exists</span>");
        }
    }
}