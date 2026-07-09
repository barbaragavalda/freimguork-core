<?php

namespace Core\Utils;

use JetBrains\PhpStorm\NoReturn;

class Exception extends \Exception
{

    /**
     * creation of the exception
     *
     * @param string      $message
     * @param int         $code
     * @param ?\Throwable $previous the original throwable, when this wraps
     *                              something that isn't itself a
     *                              Core\Utils\Exception (see Bootstrap::run())
     *                              - logged instead of this wrapper, since it
     *                              points at the real failure site
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * logs the real error server-side, then:
     * if debug mode is on: shows a description of the problem
     * else: shows a simple error message
     */
    #[NoReturn]
    public function showException(): void
    {
        // when this wraps another throwable (see Bootstrap::run()), that's
        // the real failure site - $this only points at where it got wrapped
        $source = $this->getPrevious() ?? $this;
        error_log((string) $source);

        header('HTTP/1.1 ' . ($this->code ?: 500));

        if (IS_DEV) {
            echo '
                <h2>' . $source->getMessage() . '</h2>
                Error on file: <b>' . $source->getFile() . '</b> line <b>' . $source->getLine() . '</b>
            ';
        } else {
            echo '<h1>Page not found</h1>';
        }
        exit;
    }
}