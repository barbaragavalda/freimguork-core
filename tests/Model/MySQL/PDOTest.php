<?php

namespace Core\Tests\Model\MySQL;

use Core\Model\MySQL\PDO;
use PDO as NativePDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Core\Model\MySQL\PDO's constructor always builds its own MySQL DSN
 * internally, so a real failure is exercised here by injecting a real
 * SQLite-backed native \PDO via reflection instead - the same technique
 * used elsewhere in this suite (JsonTest, ProjectsTest, TwigTest) to
 * exercise real behavior without a real MySQL connection.
 */
class PDOTest extends TestCase
{

    public function testQuerySucceedsAndReturnsRows(): void
    {
        $pdo = $this->wrap($this->sqlite());

        $pdo->query('INSERT INTO t (id, name) VALUES (1, :name)', array(
            'name' => array('value' => 'Barbara', 'type' => NativePDO::PARAM_STR),
        ));

        $this->assertTrue($pdo->getState());
        $this->assertSame(array(array('id' => 1, 'name' => 'Barbara')), $pdo->query('SELECT * FROM t'));
    }

    public function testQueryReturnsAnEmptyArrayWhenTheStatementFails(): void
    {
        $pdo = $this->wrap($this->sqlite());
        $pdo->query('INSERT INTO t (id) VALUES (1)');

        $result = $pdo->query('INSERT INTO t (id) VALUES (1)'); // duplicate primary key

        $this->assertSame(array(), $result);
    }

    /**
     * regression: $success used to only ever be assigned on the success
     * path (`$this->success = $this->statement->execute();`), so when
     * execute() threw instead of returning, that assignment never ran and
     * getState() kept reporting whatever an earlier, unrelated query left it
     * at - a failed query immediately after a successful one incorrectly
     * reported success
     */
    public function testGetStateDoesNotStayStaleFromAnEarlierSuccessfulQuery(): void
    {
        $pdo = $this->wrap($this->sqlite());

        $pdo->query('INSERT INTO t (id) VALUES (1)');
        $this->assertTrue($pdo->getState());

        $pdo->query('INSERT INTO t (id) VALUES (1)'); // duplicate, fails
        $this->assertFalse($pdo->getState());
    }

    private function sqlite(): NativePDO
    {
        $native = new NativePDO('sqlite::memory:');
        $native->exec('CREATE TABLE t (id INTEGER PRIMARY KEY, name TEXT)');

        return $native;
    }

    private function wrap(NativePDO $native): PDO
    {
        $pdo = new PDO(array(), false);
        (new ReflectionClass(PDO::class))->getProperty('pdo')->setValue($pdo, $native);

        return $pdo;
    }

}
