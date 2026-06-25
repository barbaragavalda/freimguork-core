<?php

namespace Core\Utils;

use JetBrains\PhpStorm\NoReturn;

class Exception extends \Exception
{

    /**
     * creation of the exception
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct(string $message, int $code = 0)
    {
        parent::__construct($message, $code);
    }

    /**
     * if debug mode is on: shows a description of the problem
     * else: shows a simple error message
     */
    #[NoReturn]
    public function showException(): void
    {
        header('HTTP/1.1 ' . $this->code);

        if (IS_DEV) {
            echo '
                <h2>' . $this->getMessage() . '</h2>
                Error on file: <b>' . $this->file . '</b> line <b>' . $this->line . '</b>
            ';
        } else {
            echo '<h1>Page not found</h1>';
        }
        exit;
    }
}