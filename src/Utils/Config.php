<?php

namespace Core\Utils;

define('APPACMAN_DIR', DIR_ROOT . 'vendor/optisistem/freimguork-appacman/src/');

class Config
{

    /**
     * @var ?Config $instance .  Instance of the singleton
     */
    private static ?Config $instance = null;

    private array $projects;

    /**
     * @var array $config . Content of the config files (databases, email configurations,...)
     */
    private array $config = array();

    private string $baseDomain = '';

    private string $domain = '';

    private string $staticDomain = '';

    private array $folders = array();

    private string $language = '';

    private array $ignore = array('.', '..', '.DS_Store', 'projects.php', 'projects.dev.php', 'projects.prod.php');

    /**
     * load project configurations
     * @throws \Exception
     */
    private function __construct()
    {
        $this->initFolders();

        // load projects info
        $projectFile = $this->folders[0] . 'projects.php';
        if (!file_exists($projectFile)) {
            $projectFile = $this->folders[0] . 'projects' . ((IS_DEV) ? '.dev' : '.prod') . '.php';
        }
        $this->projects = $this->load($projectFile);
        if (array_key_exists('base_domain', $this->projects)) {
            $this->baseDomain = $this->projects['base_domain'];
            unset($this->projects['base_domain']);
        }

        // create base domain if not specified
        if (!$this->baseDomain && array_key_exists('SERVER_NAME', $_SERVER)) {
            $protocol         = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
            $this->baseDomain = $protocol . $_SERVER['SERVER_NAME'];
            if (!in_array($_SERVER['SERVER_PORT'], array(80, 443))) {
                $this->baseDomain .= ':' . $_SERVER['SERVER_PORT'];
            }
            $this->baseDomain .= '/';
        }
    }

    private function initFolders(): void
    {
        $this->folders = array(
            DIR_ROOT . 'config/',
            DIR_ROOT . 'config/' . ((IS_DEV) ? 'dev/' : 'prod/')
        );

        $ownVendorsPath = DIR_ROOT . 'vendor/optisistem/';
        $ownVendors     = @scandir($ownVendorsPath);
        if ($ownVendors !== false) {
            foreach ($ownVendors as $vendor) {
                if (is_dir($ownVendorsPath . $vendor) && !in_array($vendor, $this->ignore)) {
                    $this->folders[] = $ownVendorsPath . $vendor . '/src/config/';
                }
            }
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @return \Core\Utils\Config
     */
    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getStaticDomain(): string
    {
        return $this->staticDomain;
    }

    public function setDomains(array $domain): void
    {
        $this->domain       = $domain['app'];
        $this->staticDomain = $domain['static'];
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * load all the configurations on a specific folder
     *
     * @param array $projectFolders
     *
     * @throws \Exception
     */
    public function loadConfigs(array $projectFolders): void
    {
        foreach ($this->folders as $folder) {
            $this->loadFolder($folder);

            foreach ($projectFolders as $projectFolder) {
                // common folder
                $dir = $folder . $projectFolder . '/';
                $this->loadFolder($dir);

                // environment folder
                $projectFolder .= (IS_DEV) ? '/dev/' : '/prod/';
                $dir           = $folder . $projectFolder;
                if (is_dir($dir)) {
                    // scan folder if exists
                    $this->loadFolder($dir);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function loadFolder($projectFolder): void
    {
        $files = @scandir($projectFolder);
        if ($files !== false) {
            foreach ($files as $file) {
                if (!is_dir($projectFolder . $file) && pathinfo($file, PATHINFO_EXTENSION) == 'php'
                    && !in_array(
                        $file,
                        $this->ignore
                    )) {
                    // load config
                    $this->config = array_merge_recursive($this->config, $this->load($projectFolder . $file));
                }
            }
        }
    }

    /**
     * returns the value for the given key
     * @return string|array
     */
    public function get(): string|array
    {
        $args      = func_get_args();
        $argsCount = count($args);

        $config = $this->config;
        for ($i = 0; $i < $argsCount; $i++) {
            $key = $args[ $i ];
            if (array_key_exists($key, $config)) {
                if ($i == $argsCount - 1) {
                    return $config[ $key ];
                } else {
                    $config = $config[ $key ];
                }
            } else {
                break;
            }
        }

        return '';
    }

    /**
     * returns the value for the given key
     *
     * @param string $key
     *
     * @return string
     */
    public function __get(string $key): string
    {
        return $this->get($key);
    }

    /**
     * load the config named $file_name
     *
     * @param string $file
     *
     * @return array
     * @throws \Exception
     */
    public function load(string $file): array
    {
        if (@include($file)) {
            return $config;
        } else {
            throw new Exception(
                "The config file that you are trying to load (<em>" . $file . "</em>), doesn't exists."
            );
        }
    }
}