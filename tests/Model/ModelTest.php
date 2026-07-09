<?php

namespace Core\Tests\Model;

use Core\Model\Model;
use Core\Model\MySQL\PDO;
use Core\Utils\Session;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{

    public function testAcceptsInjectedPdoAndSessionInsteadOfTheSingletons(): void
    {
        // an empty $dbConfig + $throwError = false leaves the internal \PDO
        // null - a harmless stand-in connection for unit tests, no real
        // database needed
        $mysql = new PDO(array(), false);

        $model = new Model($mysql, new Session());

        $this->assertSame($mysql, $model->mysql);
    }

    public function testProxiesUnknownMethodCallsToTheInjectedConnection(): void
    {
        $mysql = new PDO(array(), false);
        $model = new Model($mysql, new Session());

        // Model::__call() proxies to PDO - a disconnected PDO's query() just
        // returns an empty result set rather than touching a real database
        $this->assertSame(array(), $model->query('SELECT 1'));
    }

}
