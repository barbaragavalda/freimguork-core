<?php

namespace Core\Routing;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;

/**
 * Class URL
 * Helper for URL functions
 */
class URL
{

    const string REG_PARAM = '[a-zA-Z0-9-_.%&]+';

    private string $userURL = '';
    private string $protocol;

    private array        $routing    = array();
    private string|array $controller = '';
    private array        $parts      = array();
    private array        $params     = array();

    public function __construct()
    {
        if (array_key_exists('HTTP_HOST', $_SERVER) && array_key_exists('REQUEST_URI', $_SERVER)) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if (str_starts_with($requestUri, 'http')) {
                $this->userURL = $requestUri
                        |> (fn($x) => str_replace('http://', '', $x))
                        |> (fn($x) => str_replace('https://', '', $x));
            } else {
                $this->userURL = $_SERVER['HTTP_HOST'] . $requestUri;
            }
        }
        if (!str_ends_with($this->userURL, '/')) {
            $this->userURL .= '/';
        }

        $this->protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://';
    }

    public function getUserURL(): string
    {
        return $this->userURL;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getFullUserURL(): string
    {
        return $this->protocol . $this->userURL;
    }

    public function getController(): string
    {
        if (is_array($this->controller)) {
            return $this->controller[0];
        }
        return $this->controller;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function setRouting($routing): void
    {
        $this->routing = $routing;
    }

    /**
     * check which route matches the user request
     */
    public function loadParams(): void
    {
        $config  = Config::getInstance();
        $userURL = $this->getFullUserURL();

        $domain = $config->getDomain();
        if (str_starts_with($domain, '/')) {
            $domain = substr($domain, 0, -1);
        }

        $userPetition = str_replace($domain, '', $userURL);
        if (str_starts_with($userPetition, '/')) {
            $userPetition = substr($userPetition, 1);
        }
        $routingRegExp = $this->prepareRouting();

        $query = $_SERVER['QUERY_STRING'];
        if (!empty($query)) {
            $userPetition = str_replace('/?' . $query, '', $userPetition);
            $userPetition = str_replace('?' . $query, '', $userPetition);
            if ($userPetition == '/') {
                $userPetition = '';
            }
            $params = explode('&', $query);
            foreach ($params as $param) {
                $info                     = explode('=', $param, 2);
                $this->params[ $info[0] ] = $info[1];
            }
        }

        if ($userPetition == '/') {
            $userPetition = '';
        }
        foreach ($routingRegExp as $regExp) {
            if (preg_match($regExp['regExp'], $userPetition) === 1) {
                //controller found
                $this->controller = $regExp['controller'];

                //init parameters
                $explodeRouting = explode('/', $regExp['petition']);
                $explodeUser    = explode('/', $userPetition);
                for ($i = 0; $i < count($explodeRouting); $i++) {
                    if (str_starts_with($explodeRouting[ $i ], '{')) {
                        preg_match('/({' . self::REG_PARAM . '})(.+)?/', $explodeRouting[ $i ], $matches);
                        if (count($matches) > 1) {
                            if (count($matches) > 2) {
                                if (str_starts_with($explodeUser[ $i ], $matches[2])) {
                                    $explodeUser[ $i ] = str_replace($matches[2], '', $explodeUser[ $i ]);
                                } else {
                                    $this->controller = null;
                                }
                            }

                            $paramValue                 = $explodeUser[ $i ];
                            $paramName                  = str_replace('{', '', $matches[1]);
                            $paramName                  = str_replace('}', '', $paramName);
                            $this->params[ $paramName ] = $paramValue;
                        }
                    } else {
                        $this->parts[] = $explodeUser[ $i ];
                    }
                }

                if ($this->controller != null) {
                    break;
                }
            }
        }
    }

    /**
     * prepare the regular expressions
     * @return array
     */
    private function prepareRouting(): array
    {
        $routingRegExp = array();

        if ($this->routing) {

            foreach ($this->routing as $petition => $controller) {
                $regExp = '';
                if ($petition == '') {
                    $regExp .= '$^';
                } else {
                    $explode = explode('/', $petition);
                    foreach ($explode as $part) {
                        if ($regExp != '') {
                            $regExp .= '(/)';
                        }
                        if (StringUtils::startsWidth($part, '{')) {
                            $regExp .= '(' . self::REG_PARAM . ')';
                        } else {
                            if ($part == '') {
                                $regExp .= '$^';
                            } else {
                                $regExp .= '(' . $part . ')';
                            }
                        }
                    }
                    if (!str_ends_with($regExp, '(/)')) {
                        $regExp .= '(/)';
                    }
                }

                $routingRegExp[] = array(
                    'controller' => $controller,
                    'petition'   => $petition,
                    'regExp'     => '/^' . str_replace('/', '\/', $regExp) . '$/'
                );
            }
        }

        return $routingRegExp;
    }

}