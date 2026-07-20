<?php

namespace Core\Utils;

use Core\Model\Model;
use Core\Routing\Project;
use PDO;

class Language extends Model
{

    const string DOMAIN          = 'messenges';
    const string DOMAIN_APPACMAN = 'messenges_appacman';

    private string $app        = '';
    private array  $vendorApps = array();
    private string $language;
    private string $culture;

    const array CONFIGURATION = array(
        'ca' => 'ca_ES',
        'de' => 'de_DE',
        'es' => 'es_ES',
        'en' => 'en_GB',
        'fr' => 'fr_FR',
        'it' => 'it_IT',
        'eu' => 'eu_ES',
    );

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getCulture($languageID = null): string
    {
        if ($languageID == null) {
            return $this->culture;
        }

        $this->ensureConnected();
        $sql      = '
            SELECT culture
            FROM appacman_lang
            WHERE id_appacman_lang = :id
        ';
        $params   = array(
            'id' => array('value' => $languageID, 'type' => PDO::PARAM_INT)
        );
        $language = $this->mysql->query($sql, $params);
        if (count($language)) {
            return $language[0]['culture'];
        }
        return 'es';
    }

    public function setCulture(string $culture): void
    {
        $this->culture = $culture;
    }

    public static function getLocale($culture): string
    {
        return Language::CONFIGURATION[ $culture ];
    }

    public function __construct(string $userLanguage = '', ?Project $currentProject = null)
    {
        if ($currentProject != null) {
            $this->app        = $currentProject->getApp();
            $this->vendorApps = $currentProject->getVendorApps();
            $this->initLanguage($userLanguage, $currentProject);
            $this->initGettext();
        }
    }

    /**
     * check the language of the user
     *
     * @param string   $userLanguage
     * @param ?Project $currentProject
     */
    private function initLanguage(string $userLanguage, ?Project $currentProject): void
    {
        $projectLanguages = $currentProject->getLanguages();

        // resolved into a local variable rather than $this->language directly:
        // $this->language is a typed property with no default, so reading it
        // before it's definitely assigned (e.g. no URL/session/browser match)
        // would throw "must not be accessed before initialization"
        $language = null;
        if ($userLanguage) {
            // language from URL
            $language = $userLanguage;
        } else {
            $session = Session::getInstance();
            if ($session->get('lang_culture')) {
                $sessionLanguage = $session->get('lang_culture');
                if (in_array($sessionLanguage, $projectLanguages)) {
                    // language from session
                    $language = $sessionLanguage;
                }
            } else {
                if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
                    $agentLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                    $agentLanguage = $this->equivalents($agentLanguage, $projectLanguages[0]);
                    if (in_array($agentLanguage, $projectLanguages)) {
                        // language from browser
                        $language = $agentLanguage;
                    }
                }
            }
        }

        // language from config, if nothing else matched
        $this->language = $language ?: $projectLanguages[0];

        $this->initCulture();
    }

    /**
     * Language can be constructed without a DB connection (see __construct());
     * methods that need one lazily open it on first use.
     */
    private function ensureConnected(): void
    {
        if ($this->mysql == null) {
            parent::__construct();
        }
    }

    private function equivalents($language, $preferred): string
    {
        if ($language == $preferred) {
            return $language;
        }
        return match ($language) {
            'ca', 'eu', 'gl' => 'es',
            default => '',
        };
    }

    /**
     * initializes the gettext function with the current language
     */
    public function initGettext(): void
    {
        putenv('LC_ALL=' . $this->culture);
        setlocale(LC_ALL, $this->culture);

        $domain          = self::DOMAIN;
        $domainDirectory = DIR_ROOT . 'locale';
        if ($this->app == 'Appacman') {
            $domain          = self::DOMAIN_APPACMAN;
            $domainDirectory = APPACMAN_DIR . 'locale';
        }

        bindtextdomain($domain, $domainDirectory);
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);

        // a vendor package pulled in via Project::getVendorApps() (e.g.
        // freimguork-webservice) can ship its own translations under its own
        // domain, so its generic controllers can dgettext() them regardless
        // of which project ends up consuming them - same convention as
        // Appacman's own domain above, just not hardcoded to that one app
        foreach ($this->vendorApps as $vendorApp) {
            $vendorDomain    = self::DOMAIN . '_' . strtolower($vendorApp);
            $vendorDirectory = DIR_ROOT . 'vendor/optisistem/freimguork-' . strtolower($vendorApp) . '/src/locale';
            bindtextdomain($vendorDomain, $vendorDirectory);
            bind_textdomain_codeset($vendorDomain, 'UTF-8');
        }
    }

    public function initID(): void
    {
        $this->ensureConnected();

        $table = 'appacman_lang';
        if (!$this->mysql->tableExists($table)) {
            $table = 'language';
        }
        $sql      = '
            SELECT id_' . $table . ' AS id
            FROM ' . $table . '
            WHERE culture = :culture
        ';
        $params   = array(
            'culture' => array('value' => $this->language, 'type' => PDO::PARAM_STR)
        );
        $language = $this->mysql->query($sql, $params);

        if (count($language)) {
            $session = Session::getInstance();
            $session->set('lang_id', $language[0]['id']);
            $session->set('lang_culture', $this->language);
        }
    }

    /**
     * get culture depending on language
     *
     * @param ?string $languageID
     */
    public function initCulture(?string $languageID = null): void
    {
        if ($languageID !== null) {
            $this->language = $languageID;
        }
        $this->culture = self::CONFIGURATION[ $this->language ];
    }

    public function getLanguages($culture = null): array
    {
        $this->ensureConnected();

        $table = 'appacman_lang';
        if (!$this->mysql->tableExists($table)) {
            $table = 'language';
        }

        $where  = '';
        $params = array();
        if ($culture != null) {
            $where  = 'WHERE l.culture = :culture';
            $params = array(
                'culture' => array('value' => $this->language, 'type' => PDO::PARAM_STR)
            );
        }

        $sql       = "
            SELECT l.id_$table AS id, l.name, l.icon
            FROM $table AS l
            $where
            ORDER BY l.order ASC
        ";
        $languages = $this->mysql->query($sql, $params);

        if (count($languages)) {
            $config       = Config::getInstance();
            $staticDomain = $config->getStaticDomain();

            $session = Session::getInstance();
            $langID  = $session->get('lang_id');
            foreach ($languages as &$language) {
                $language['icon'] = $staticDomain . 'static/img/' . $language['icon'];
                if ($language['id'] == $langID) {
                    $language['current'] = true;
                }
            }
            return $languages;
        }
        return array();
    }

}