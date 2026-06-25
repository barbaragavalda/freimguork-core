<?php

namespace Core\Utils;

class Session
{

    const int DURATION = 86400;  // 1 day

    /**
     * @var ?Session $instance . Instance of the singleton
     */
    private static ?Session $instance = null;

    /**
     * @var string $path . The path that the cookie is available to
     */
    private string $path = '/';

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @return Session
     */
    public static function getInstance(): Session
    {
        if (self::$instance === null) {
            self::$instance = new Session();
        }
        return self::$instance;
    }

    /**
     * get value of a cookie
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (isset($_COOKIE[ $key ])) {
            return json_decode($_COOKIE[ $key ], true);
        }
        return null;
    }

    /**
     * set cookie
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->save($key, $value);
    }

    /**
     * delete cookie
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        if (isset($_COOKIE[ $key ])) {
            unset($_COOKIE[ $key ]);
            setcookie($key, '', time() - 3600, '/');
        }
    }

    /**
     * delete all cookies
     */
    public function clear(): void
    {
        foreach ($_COOKIE as $key => $value) {
            $this->delete($key);
        }
    }

    private function save(string $key, mixed $value): void
    {
        $value           = json_encode($value);
        $_COOKIE[ $key ] = $value;
        setcookie($key, $value, time() + self::DURATION, $this->path);
    }

}