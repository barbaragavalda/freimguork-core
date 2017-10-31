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

define("APPACMAN_DIR", DIR_ROOT . 'vendor/appaqui/freimguork-appacman/src/');

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
     * @var string $domain. App domain
     */
    private $domain = '';

    /**
     * @var string $staticDomain. Static domain
     */
    private $staticDomain = '';

    /**
     * @var array $folders. Directory where must be all config files
     */
    private $folders = array();

    /**
     * @var string $language. Current language
     */
    private $language = '';

    /**
     * load project configurations
     */
    private function __construct(){
        $this->folders = array(
            DIR_ROOT . 'config/',
            APPACMAN_DIR . 'config/'
        );

        //load projects info
        $projectFile = 'projects' . (( IS_DEV ) ? '.dev' : '.prod') . '.php';
        $this->projects = $this->load($this->folders[0] . $projectFile);
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
     * returns the app url
     * @return string
     */
    public function getDomain(){
        return $this->domain;
    }

    /**
     * returns the static url
     * @return string
     */
    public function getStaticDomain(){
        return $this->staticDomain;
    }

    /**
     * sets the base url
     * @param $domain array. Set app and static domains
     */
    public function setDomains($domain){
        $this->domain = $domain['app'];
        $this->staticDomain = $domain['static'];
    }

    /**
     * @return string. Get current language
     */
    public function getLanguage(){
        return $this->language;
    }

    /**
     * @param $language string. Set current language
     */
    public function setLanguage($language){
        $this->language = $language;
    }

    /**
     * load all the configurations on a specific folder
     * @param $projectFolders
     */
    public function loadConfigs( $projectFolders ){
        foreach($this->folders as $folder){
            foreach($projectFolders as $projectFolder){
                // common folder
                $dir = $folder . $projectFolder . '/';
                $this->loadFolder($dir);

                // environment folder
                $projectFolder .= ( IS_DEV ) ? '/dev/' : '/prod/';
                $dir = $folder . $projectFolder;
                if( is_dir($dir) ){
                    // scan folder if exists
                    $this->loadFolder($dir);
                }
            }
        }
    }

    private function loadFolder($projectFolder){
        $files = @scandir($projectFolder);
        if( $files !== false ){
            foreach($files as $file){
                if( !is_dir($projectFolder . $file) && !in_array($file, array('.', '..', '.DS_Store')) ){
                    // load config
                    $this->config = array_merge_recursive($this->config, $this->load($projectFolder . $file));
                }
            }
        }
    }

    /**
     * returns the value for the given key
     * @return string
     */
    public function get(){
        $args = func_get_args();
        $argsCount = count($args);

        $config = $this->config;
        for($i=0; $i<$argsCount; $i++){
            $key = $args[$i];
            if( array_key_exists($key, $config) ){
                if( $i ==  $argsCount-1 ){
                    return $config[$key];
                }else{
                    $config = $config[$key];
                }
            }else{
                break;
            }
        }

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
            throw new Exception("The config file that you are trying to load (<em>".$file."</em>), doesn't exists.");
        }
    }
}