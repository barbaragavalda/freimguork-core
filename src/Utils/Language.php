<?php

namespace Core\Utils;

use Core\Model\Model;

/**
 * Class Language
 *
 * Current language
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Language extends Model {

    const DOMAIN = 'messenges';

    private $language = null;
    private $culture = null;

    private $configuration = array(
        'ca' => 'ca_ES',
        'de' => 'de_DE',
        'es' => 'es_ES',
        'en' => 'en_GB',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
        'eu' => 'eu_ES',
    );

    public function getLanguage(){
        return $this->language;
    }

    public function __construct($userLanguage = null, $currentProject = null){
        if( $currentProject != null ){
            $this->initLanguage($userLanguage, $currentProject);
            $this->initGettext();
        }
    }

    /**
     * check the language of the user
     * @param string $userLanguage
     * @param string $currentProject
     */
    private function initLanguage($userLanguage, $currentProject){
        $projectLanguages = $currentProject->getLanguages();
        if( $userLanguage ){
            // language from URL
            $this->language = $userLanguage;
        }else{
            $session = Session::getInstance();
            if( $session->get('lang_culture') ){
                $sessionLanguage = $session->get('lang_culture');
                if( in_array($sessionLanguage, $projectLanguages) ){
                    // language from session
                    $this->language = $sessionLanguage;
                }
            }else{
                if( array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) ){
                    $agentLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                    if( in_array($agentLanguage, $projectLanguages) ){
                        // language from browser
                        $this->language = $agentLanguage;
                    }
                }
            }
        }

        if( !$this->language ){
            // language from config
            $this->language = $projectLanguages[0];
        }

        $this->initCulture();
    }

    /**
     * initializes the gettext function with the current language
     */
    public function initGettext(){
        putenv('LC_ALL='.$this->culture);
        setlocale(LC_ALL, $this->culture);

        bindtextdomain(self::DOMAIN, DIR_ROOT . 'locale');
        $appacmanLocale = APPACMAN_DIR . 'locale';
        if( is_dir($appacmanLocale) ) bindtextdomain('messenges_appacman', $appacmanLocale);

        bind_textdomain_codeset(self::DOMAIN, 'UTF-8');
        textdomain(self::DOMAIN);
    }

    public function initID(){
        if( $this->mysql == null ) parent::__construct();

        $table = 'appacman_lang';
        if( !$this->mysql->tableExists($table) ) $table = 'language';
        $sql = '
            SELECT id_' . $table . ' AS id
            FROM ' . $table . '
            WHERE culture = :culture
        ';
        $params = array(
            'culture' => array('value' => $this->language, 'type' => \PDO::PARAM_STR)
        );
        $language = $this->mysql->query($sql, $params);

        if( count($language) ){
            $session = Session::getInstance();
            $session->set('lang_id', $language[0]['id']);
            $session->set('lang_culture', $this->language);
        }
    }

    /**
     * get culture depending on language
     * @param string $languageID
     */
    public function initCulture($languageID = null){
        if( $languageID !== null ){
            $this->language = $languageID;
        }
        $this->culture = $this->configuration[ $this->language ];
    }

    public function setCulture($culture){
        $this->culture = $culture;
    }

    public function getLanguages($culture = null){
        if( $this->mysql == null ) parent::__construct();

        $table = 'appacman_lang';
        if( !$this->mysql->tableExists($table) ) $table = 'language';

        $where  = '';
        $params = array();
        if( $culture != null ){
            $where  = 'WHERE l.culture = :culture';
            $params = array(
                'culture' => array('value' => $this->language, 'type' => \PDO::PARAM_STR)
            );
        }

        $sql = '
            SELECT l.id_' . $table . ' AS id, l.name, l.icon
            FROM ' . $table . ' AS l
            ' . $where . '
            ORDER BY l.order ASC
        ';
        $languages = $this->mysql->query($sql, $params);

        if( count($languages) ){
            $config = Config::getInstance();
            $staticDomain = $config->getStaticDomain();

            $session = Session::getInstance();
            $langID = $session->get('lang_id');
            foreach($languages as &$language){
                $language['icon'] = $staticDomain . 'static/img/' . $language['icon'];
                if( $language['id'] == $langID ){
                    $language['current'] = true;
                }
            }
            return $languages;
        }
        return array();
    }

}