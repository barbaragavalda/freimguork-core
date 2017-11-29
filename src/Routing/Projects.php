<?php 

namespace Core\Routing;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;
use Core\Utils\Exception;

/**
 * Class Projects
 *
 * Load all projects and determines the current one depending on projects.<env>.php configuration
 *
 * @package Core\Routing
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class Projects{

    const LANG_PATTERN = '{lang}';
    const LANG_PATTERN_REG_EXPRESSION = '/(\w*{lang}\w*)/';
    const LANG_CODE_REG_EXPRESSION = '([a-z]{2})';

    /**
     * @var \Core\Utils\Config $config. load some configs
     */
    private $config = array();

    /**
     * @var \Core\Routing\Project $controller. Name of the controller that correspond to the URL
     */
    private $currentProject = array();

    /**
     * @var obj $url. object that parses the URL
     */
    private $url = null;

    /**
     * asks the controller name
     */
    public function __construct(){
        $this->config = Config::getInstance();
        $this->url = new URL();
        $this->searchProject();
	}

    /**
     * language set on URL (if any)
     * @return bool|string
     */
    public function getUserLanguage(){
        if( ($position = $this->currentProject->getLangPosition()) > 0 ){
            return preg_filter(
                $this->currentProject->getRegularExpression(),
                '$'.$position,
                $this->url->getUserURL()
            );
        }
        return false;
    }

    /**
     * current project
     * @return \Core\Routing\Project
     */
    public function getProject(){
        return $this->currentProject;
    }

    /**
     * base URL
     * @param $language
     * @return string
     */
    public function getDomains($language){
        $protocol = $this->url->getProtocol();

        //ap domain
        $url = str_replace('{lang}', $language, $this->currentProject->getURL());

        //static domain
        $parts = explode('/', $url);
        $staticURL = $parts[0] . '/';
        //$staticURL = $url;

        return array(
            'app'       => $protocol . $url,
            'static'    => $protocol . $staticURL
        );
    }

    /**
     * search for the current project
     */
	private function searchProject(){
        try{
            $projects = $this->config->getProjects();

            $currentURL = $this->url->getUserURL();
            $defaultProject = new Project();
            foreach($projects as $domain => $project){
                $domainRegExpInfo = $this->getRegularExpression($domain, $project, $defaultProject);
                $domainRegExp = $domainRegExpInfo['regExp'];
                $found = false;

                if( preg_match($domainRegExp, $currentURL) ){
                    $found = true;
                }else if( StringUtils::endsWidth($domain, self::LANG_PATTERN) || StringUtils::endsWidth($domain, self::LANG_PATTERN.'/') ){
                    //if not found, try if the user didb't enter the language
                    $domainRegExp = str_replace(self::LANG_PATTERN .'/', '', $domain);
                    $domainRegExp = str_replace(self::LANG_PATTERN, '', $domainRegExp);
                    $domainRegExp = '/' . str_replace('/', '\/', $domainRegExp) . '(.*)/';
                    if( preg_match($domainRegExp, $currentURL) ){
                        $found = true;
                    }
                }

                if( $found ){
                    $this->currentProject = new Project();
                    $this->currentProject->setURL($domain);
                    $this->currentProject->setRegularExpression($domainRegExp);
                    $this->currentProject->setLangPosition($domainRegExpInfo['langPosition']);
                    $this->currentProject->setInfo($project);
                    break;
                }
            }

            if( $this->currentProject == null ){
                $this->currentProject = $defaultProject;
            }

            if( $this->currentProject->isEmpty() ){
                throw new Exception("No project matches the current configuration");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * regular expresion for the URL
     * @param $domain           current domain
     * @param $project          current project
     * @param $defaultProject   by reference, default project if no project found
     * @return array            regular expression for the doamin and language position
     */
    private function getRegularExpression($domain, $project, &$defaultProject){
        $domainRegExp = '(' . $domain . ')';
        $langPosition = 0;

        if( ($pos = strpos($domain, self::LANG_PATTERN)) !== false ){
            //has language
            $explode = preg_split(self::LANG_PATTERN_REG_EXPRESSION, $domain, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $domainRegExp = '';
            $i = 1;
            foreach($explode as $part){
                if( $part == self::LANG_PATTERN ){
                    $domainRegExp .= self::LANG_CODE_REG_EXPRESSION;
                    $langPosition = $i;
                }else{
                    $domainRegExp .= '(' . $part . ')';
                }
                $i++;
            }
        }

        $domainRegExp = '/' . str_replace('/', '\/', $domainRegExp) . '(.*)/';
        if( $pos == 0 && $defaultProject->isEmpty() ){
            $defaultProject->setURL($domain);
            $defaultProject->setRegularExpression($domainRegExp);
            $defaultProject->setInfo($project);
        }

        return array(
            'regExp' => $domainRegExp,
            'langPosition' => $langPosition
        );
    }

}