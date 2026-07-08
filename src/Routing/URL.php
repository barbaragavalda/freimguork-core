<?php

namespace Core\Routing;

/**
 * Class URL
 * Helper for URL/domain parsing, used by Projects to resolve the current sub-project.
 */
class URL
{

    private string $userURL = '';
    private string $protocol;

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

}