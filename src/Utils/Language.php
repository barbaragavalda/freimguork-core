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

    /**
     * inverse of getCulture($languageID) - looks up the id_appacman_lang
     * (or id_language, same table-name fallback as getCulture()/initID())
     * for a given 2-letter culture code, or null if it isn't one of this
     * project's configured languages. Added for Webservice\Controller\
     * Register to persist a user's language at signup, since that's a
     * fact worth storing rather than re-deriving from Accept-Language on
     * every later request (a returning user's browser/device might not
     * send the same header, or none at all)
     */
    public function getLanguageID(string $culture): ?int
    {
        $this->ensureConnected();

        $table = 'appacman_lang';
        if (!$this->mysql->tableExists($table)) {
            $table = 'language';
        }
        $sql    = '
            SELECT id_' . $table . ' AS id
            FROM ' . $table . '
            WHERE culture = :culture
        ';
        $params = array(
            'culture' => array('value' => $culture, 'type' => PDO::PARAM_STR)
        );
        $result = $this->mysql->query($sql, $params);
        return isset($result[0]['id']) ? (int) $result[0]['id'] : null;
    }

    /**
     * runs $callback with gettext temporarily switched to $culture (not
     * necessarily this request's own resolved language - Bootstrap-time
     * gettext setup, see initGettext(), is otherwise fixed for the whole
     * request), restoring the original locale afterward regardless of
     * whether $callback throws. Needed for anything translating into a
     * *stored* preference rather than the current request's own language -
     * e.g. Webservice\Controller\ForgotPassword's reset-code email, sent in
     * whatever language the recipient registered with, not necessarily
     * whatever Accept-Language this particular request happened to carry
     */
    public function withCulture(string $culture, callable $callback): mixed
    {
        $locale = self::CONFIGURATION[$culture] ?? null;
        if ($locale === null) {
            return $callback();
        }

        // getenv(), not setlocale(LC_ALL, 0)'s own current-locale-query
        // convention (passing int 0 as the "locale" arg) - same env-var-
        // driven approach initGettext() already uses to set it in the first
        // place, so it's guaranteed to be a valid restorable value here
        $previousLocale = getenv('LC_ALL');
        putenv('LC_ALL=' . $locale);
        setlocale(LC_ALL, $locale);

        try {
            return $callback();
        } finally {
            if ($previousLocale !== false) {
                putenv('LC_ALL=' . $previousLocale);
                setlocale(LC_ALL, $previousLocale);
            } else {
                putenv('LC_ALL');
            }
        }
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
                    $agentLanguage = $this->equivalents($agentLanguage, $projectLanguages);
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

    /**
     * @param string   $language         2-letter browser language (from Accept-Language)
     * @param string[] $projectLanguages every language this project actually supports
     *
     * Was previously only checked against a single $preferred language (the
     * project's first/default one) instead of the full list - correct on a
     * 2-language ca/es project only by coincidence, since 'es' has its own
     * match() case below anyway, but silently broken for any 3rd+ language
     * (confirmed empirically: 'en'/'fr'/etc. always fell through to '',
     * meaning the browser's language was *never* actually honored on a
     * project like tv-tracker-local's api sub-project, ['ca','es','en']).
     */
    private function equivalents(string $language, array $projectLanguages): string
    {
        if (in_array($language, $projectLanguages, true)) {
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