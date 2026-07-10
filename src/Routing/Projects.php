<?php

namespace Core\Routing;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;
use Core\Utils\Exception;

/**
 * Class Projects
 * Load all projects and determines the current one depending on projects.<env>.php configuration
 */
class Projects
{

    const string LANG_PATTERN                = '{lang}';
    const string LANG_PATTERN_REG_EXPRESSION = '/(\w*{lang}\w*)/';
    const string LANG_CODE_REG_EXPRESSION    = '[a-z]{2}';

    private Config $config;

    // must default to null (not just be nullable) - $currentProject is read
    // via `== null` in searchProject() as a fallback check, and reading an
    // uninitialized typed property (as opposed to one actually holding null)
    // throws instead of comparing falsy, if no configured domain matches the
    // current URL at all
    private ?Project $currentProject = null;

    private URL $url;

    /**
     * asks the controller name
     * @throws \Core\Utils\Exception
     */
    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->url    = new URL();
        $this->searchProject();
    }

    /**
     * language set on URL (if any)
     * @return bool|string
     */
    public function getUserLanguage(): bool|string
    {
        if (($position = $this->currentProject->getLangPosition()) > 0) {
            $protocol   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
            $baseDomain = str_replace($protocol, '', $this->config->getBaseDomain());
            $userURL    = str_replace($baseDomain, '', $this->url->getUserURL());
            $language   = preg_filter(
                $this->currentProject->getRegularExpression(),
                '$' . $position,
                $userURL
            );
            if (in_array($language, $this->currentProject->getLanguages())) {
                return $language;
            }
        }
        return false;
    }

    /**
     * user can specify language on url?
     * @return bool
     */
    public function hasCustomLanguage(): bool
    {
        return str_contains($this->currentProject->getURL(), '{lang}');
    }

    public function getProject(): Project
    {
        return $this->currentProject;
    }

    /**
     * base URL
     *
     * @param string $language
     *
     * @return array
     */
    public function getDomains(string $language): array
    {
        //app domain
        $url      = str_replace('{lang}', $language, $this->currentProject->getURL());
        $protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
        if (empty($url)) {
            $url = $protocol . $_SERVER['HTTP_HOST'];
        }
        if (!str_starts_with($url, $protocol)) {
            $url = $this->config->getBaseDomain() . $url;
        }
        if (!str_starts_with($url, '/')) {
            $url .= '/';
        }

        return array(
            'app'    => $url,
            'static' => $this->config->getBaseDomain()
        );
    }

    /**
     * search for the current project
     * @throws \Core\Utils\Exception
     */
    private function searchProject(): void
    {
        $projects = $this->config->getProjects();

        $currentURL     = $this->url->getUserURL();
        $defaultProject = new Project();
        foreach ($projects as $domain => $project) {
            if (array_key_exists('isDefault', $project) && $project['isDefault']) {
                $domainRegExpInfo = $this->getRegularExpression($domain, $project);
                $domainRegExp     = $domainRegExpInfo['regExp'];

                $defaultProject->setURL($domain);
                $defaultProject->setRegularExpression($domainRegExp);
                $defaultProject->setInfo($project);
            }
        }

        foreach ($projects as $domain => $project) {
            $domainRegExpInfo = $this->getRegularExpression($domain, $project, $defaultProject);
            $domainRegExp     = $domainRegExpInfo['regExp'];
            $found            = false;

            if (preg_match($domainRegExp, $currentURL)) {
                $found = true;
            } else {
                if (str_ends_with($domain, self::LANG_PATTERN) || str_ends_with($domain, self::LANG_PATTERN . '/')) {
                    //if not found, try if the user didn't enter the language
                    $domainRegExp = str_replace(self::LANG_PATTERN . '/', '', $domain)
                            |> (fn($x) => str_replace(self::LANG_PATTERN, '', $x))
                            |> (fn($x) => str_replace('/', '\/', $x));

                    $domainRegExpLangSlash = '/' . $domainRegExp . '(' . self::LANG_CODE_REG_EXPRESSION . '/)/';

                    if (preg_match($domainRegExpLangSlash, $currentURL)) {
                        $found = true;
                    }
                }
            }

            if ($found) {
                $this->currentProject = new Project();
                $this->currentProject->setURL($domain);
                $this->currentProject->setRegularExpression($domainRegExp);
                $this->currentProject->setLangPosition($domainRegExpInfo['langPosition']);
                $this->currentProject->setInfo($project);
                break;
            }
        }

        if ($this->currentProject == null) {
            $this->currentProject = $defaultProject;
        }

        if ($this->currentProject->isEmpty()) {
            throw new Exception("No project matches the current configuration");
        }
    }

    /**
     * Regular expresión for the URL
     *
     * @param string   $domain         current domain
     * @param array    $project        current project
     * @param ?Project $defaultProject by reference, default project if no project found
     *
     * @return array                                    regular expression for the domain and language position
     */
    private function getRegularExpression(string $domain, array $project, ?Project $defaultProject = null): array
    {
        $domainRegExp = '(' . $domain . ')';
        $langPosition = 0;

        if (($pos = strpos($domain, self::LANG_PATTERN)) !== false) {
            //has language
            $explode      = preg_split(
                self::LANG_PATTERN_REG_EXPRESSION,
                $domain,
                0,
                PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
            );
            $domainRegExp = '';
            $i            = 1;
            foreach ($explode as $part) {
                if ($part == self::LANG_PATTERN) {
                    $domainRegExp .= '(' . self::LANG_CODE_REG_EXPRESSION . ')';
                    $langPosition = $i;
                } else {
                    $domainRegExp .= '(' . $part . ')';
                }
                $i++;
            }
        }

        $domainRegExp = '/' . str_replace('/', '\/', $domainRegExp) . '(.*)/';
        if ($defaultProject != null) {
            // $pos is false (not 0) when the domain has no {lang} pattern at
            // all - `== 0` would treat "not found" the same as "found at the
            // very start", silently populating the default project fallback
            // from whichever non-{lang} project happens to be processed
            // first, instead of only the one actually flagged isDefault
            if ($pos === 0 && $defaultProject->isEmpty()) {
                $defaultProject->setURL($domain);
                $defaultProject->setRegularExpression($domainRegExp);
                $defaultProject->setInfo($project);
            }
        }

        return array(
            'regExp'       => $domainRegExp,
            'langPosition' => $langPosition
        );
    }

}