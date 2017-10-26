<?php

namespace Core\Utils;

/**
 * Class Language
 *
 * Current language
 *
 * @package Core\Utils
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Language{

    const DOMAIN = 'messenges';

    private $language = null;
    private $culture = null;

    private $configuration = array(
        'ca' => 'ca_ES',
        'de' => 'de_DE',
        'es' => 'es_ES',
        'en' => 'en_UK',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
    );

    public function getLanguage(){
        return $this->language;
    }

    public function __construct($userLanguage, $currentProject){
        $this->initLanguage($userLanguage, $currentProject);
        $this->initGettext();
    }

    /**
     * check the language of the user
     * @param $userLanguage
     * @param $currentProject
     */
    private function initLanguage($userLanguage, $currentProject){
        $projectLanguages = $currentProject->getLanguages();
        if( $userLanguage ){
            // language from URL
            $this->language = $userLanguage;
        }else{
            $agentLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if( in_array($agentLanguage, $projectLanguages) ){
                // language from browser
                $this->language = $agentLanguage;
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
    private function initGettext(){
        putenv('LC_ALL='.$this->culture);
        setlocale(LC_ALL, $this->culture);

        bindtextdomain(self::DOMAIN, DIR_ROOT . 'locale');
        bind_textdomain_codeset(self::DOMAIN, 'UTF-8');
        textdomain(self::DOMAIN);
    }

    /**
     * get culture depending on language
     * @return string
     */
    private function initCulture(){
        $this->culture = $this->configuration[ $this->language ];
    }

}