<?php

namespace Core\Model\MySQL;

use Core\Utils\Config;
use Core\Utils\Exception;
use PDOException;
use PDOStatement;

class PDO
{

    private string $databaseName = '';

    private string $user = '';

    private string $password = '';

    private ?\PDO $pdo = null;

    private ?PDOStatement $statement = null;

    public bool $success = false;

    private bool $activeTransaction = false;

    /**
     * PDO constructor
     *
     * @param ?array   $dbConfig
     * @param boolean $throwError throw error or just ignore it
     *
     * @throws Exception
     */
    public function __construct(?array $dbConfig, bool $throwError)
    {
        if ($dbConfig === null) {
            $config   = Config::getInstance();
            $dbConfig = $config->get('db');
        }

        if (!empty($dbConfig) && count($dbConfig)) {
            if (isset($dbConfig['name'])) {
                $this->databaseName = $dbConfig['name'];
            } elseif (isset($dbConfig['databases']) && (sizeof($dbConfig['databases']) > 0)) {
                $this->databaseName = $dbConfig['databases'][ array_key_first($dbConfig['databases']) ];
            } else {
                $this->databaseName = '';
            }
            $host           = $dbConfig['host'];
            $this->user     = $dbConfig['user'];
            $this->password = $dbConfig['password'];
            $database       = $this->databaseName;

            if ($host != '' && $this->user != '' && $database != '') {
                $dsn = 'mysql:dbname=' . $database . ';host=' . $host;
                try {
                    $this->pdo = new \PDO($dsn, $this->user, $this->password);
                    $this->pdo->exec("SET CHARACTER SET utf8mb4");
                    $this->pdo->exec("SET SESSION group_concat_max_len = 10000000");
//                    $this->pdo->exec(
//                        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'"
//                    );
                } catch (PDOException $e) {
                    if ($throwError) {
                        throw new Exception("PDO connection error: <em>" . $e->getMessage() . "</em>");
                    }
                }
            } else {
                if ($throwError) {
                    throw new Exception("PDO connection error: <em> no database specified</em>");
                }
            }
        }
    }

    /**
     * return the result of a simple query
     *
     * @param string $sql    . Query
     * @param array  $params . binding params
     *
     * @return array. Result
     */
    public function query(string $sql, array $params = array()): array
    {
        if ($this->pdo != null) {
            $this->statement = $this->pdo->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $key => $param) {
                    $this->statement->bindParam(':' . $key, $param['value'], $param['type']);
                }
            }
            try {
                $this->success = $this->statement->execute();
            } catch (PDOException $e) {
                if (IS_DEV) {
                    r($sql, $params, $e->getMessage());
                }
            }
            return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return array();
    }

    public function getState(): bool
    {
        return $this->success;
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * new free ID form specific table
     *
     * @param string $table
     *
     * @return int|false
     */
    public function getMaxId(string $table): int|false
    {
        $sql    = '
    		SELECT MAX(id_' . $table . ') +1 AS id
    		FROM `' . $table . '`
    	';
        $result = $this->query($sql);
        if (count($result)) {
            $id = $result[0]['id'];
            if ($id == '') {
                $id = 1;
            }
            return $id;
        }
        return false;
    }

    public function getWhere(array $where, string $start = 'WHERE', string $operand = 'AND'): string
    {
        $str = '';
        if (count($where)) {
            $str = $start . ' ' . implode(' ' . $operand . ' ', $where);
        }
        return $str;
    }

    /**
     * how many rows where affected with the last query
     * @return int
     */
    public function rowCount(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * tableExists
     * check if table exists
     *
     * @param string $table . table name
     *
     * @return bool
     */
    public function tableExists(string $table): bool
    {
        if ($this->pdo != null) {
            $sql    = '
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = :bbdd_name
                AND table_name = :table_name
            ';
            $params = array(
                'bbdd_name'  => array('value' => $this->databaseName, 'type' => \PDO::PARAM_STR),
                'table_name' => array('value' => $table, 'type' => \PDO::PARAM_STR),
            );
            $result = $this->query($sql, $params);

            if (count($result)) {
                return true;
            }
        }
        return false;
    }

    /**
     * fieldExists
     * check if field exists in table
     *
     * @param $table
     * @param $field
     *
     * @return bool
     */
    public function fieldExists($table, $field): bool
    {
        if ($this->pdo != null) {
            $sql    = '
                SELECT * 
                FROM information_schema.columns 
                WHERE table_schema = :bbdd_name
                AND table_name = :table_name
                AND column_name = :field
            ';
            $params = array(
                'bbdd_name'  => array('value' => $this->databaseName, 'type' => \PDO::PARAM_STR),
                'table_name' => array('value' => $table, 'type' => \PDO::PARAM_STR),
                'field'      => array('value' => $field, 'type' => \PDO::PARAM_STR),
            );
            $result = $this->query($sql, $params);

            if (count($result)) {
                return true;
            }
        }
        return false;
    }

    /**
     * fieldDescription
     * data type and mandatory information of specific field
     *
     * @param string $table . table name
     * @param string $field . field name
     *
     * @return array
     */
    public function fieldDescription(string $table, string $field): array
    {
        $fieldDesc = $this->field($table, $field);
        if ($fieldDesc === false) {
            $fieldDesc = $this->field($table . '_lang', $field);
        }

        if ($fieldDesc !== false && count($fieldDesc)) {
            //r($fieldDesc);
            $required = !($fieldDesc['Null'] == 'YES');
            return array(
                'type'     => $fieldDesc['Type'],
                'required' => $required
            );
        }
        return array();
    }

    private function field($table, $field): array|false
    {
        if ($this->tableExists($table)) {
            $sql    = '
				SHOW COLUMNS
				FROM ' . $table . '
				WHERE Field = :field
			';
            $params = array(
                'field' => array('value' => $field, 'type' => \PDO::PARAM_STR)
            );
            $field  = $this->query($sql, $params);

            if (count($field)) {
                return $field[0];
            }
        }
        return false;
    }

    public function beginTransaction(): void
    {
        if (!$this->activeTransaction) {
            $this->activeTransaction = true;
            $this->pdo->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->activeTransaction) {
            $this->pdo->commit();
        }
        $this->activeTransaction = false;
    }

    public function rollBack(): void
    {
        if ($this->activeTransaction) {
            $this->pdo->rollBack();
        }
        $this->activeTransaction = false;
    }

    /**
     * magic method that allows to invoke any method of PDO
     *
     * @param string $function_name . Function name
     * @param array  $args          . Parameters of the function
     *
     * @return mixed. result of the function
     * @throws Exception
     */
    public function __call(string $function_name, array $args): mixed
    {
        if (method_exists($this->pdo, $function_name)) {
            return call_user_func_array(array($this->pdo, $function_name), $args);
        } else {
            throw new Exception(
                "The method <em>"
                . $function_name
                . "</em> doesn't exists on PDO. Check <a href='http://es.php.net/manual/en/book.pdo.php' target='_blank'>the manual</a> for more information"
            );
        }
    }

    /**
     * Dumps a given database to a given file.
     *
     * @param string $bd      is the database to dump.
     * @param string $file    is the file where it'll be dumped to.
     * @param bool   $inserts if we need to add the table content to the dumped DB.
     *
     * @return false|string           with the executed command.
     */
    public function dumpDB(string $bd, string $file, bool $inserts = false): false|string
    {
        echo 'Generating file for ' . $bd . '...\n';

        $command = 'mkdir -p ":FOLDER" && touch ":FILE"';
        $command = str_replace(':FOLDER', dirname($file), $command);
        $command = str_replace(':FILE', $file, $command);
        system($command);

        $command = 'mysqldump -u:USER -p:PASSWORD';
        if ($inserts) {
            $command .= ' --skip-comments --compact --add-drop-database --databases --add-drop-database --add-drop-table --set-charset --skip-tz-utc :BD > :FILE 2>&1';
        } else {
            $command .= ' -d --single-transaction --databases --add-drop-database --add-drop-table --set-charset --skip-tz-utc :BD | sed "s/ AUTO_INCREMENT=[0-9]*\b/ AUTO_INCREMENT=1/" > :FILE 2>&1';
        }

        $command = str_replace(':USER', $this->user, $command);
        $command = str_replace(':PASSWORD', $this->password, $command);
        $command = str_replace(':BD', $bd, $command);
        $command = str_replace(':FILE', $file, $command);

        echo PHP_EOL . PHP_EOL . $command;
        return system($command);
    }

    /**
     * Dumps given database tables into a given file
     *
     * @param string $bd      database that needs to be dumped
     * @param array  $tables  containing the tables that need to be dumped
     * @param string $file    where the information will be stored
     * @param bool   $inserts if we need to add the table content to the dumped DB.
     *
     * @return false|string
     */
    public function dumpTables(string $bd, array $tables, string $file, bool $inserts = false): false|string
    {
        $tables = implode(" ", $tables);
        echo "Generating file for database $bd: including tables: $tables ...";

        $command = 'mysqldump -u:USER -p:PASSWORD';
        if ($inserts) {
            $command .= ' --skip-comments --compact --add-drop-table --set-charset --skip-tz-utc :BD :TABLES > :FILE 2>&1';
        } else {
            $command .= ' -d --single-transaction --add-drop-table --set-charset --skip-tz-utc :BD :TABLES | sed "s/ AUTO_INCREMENT=[0-9]*\b/ AUTO_INCREMENT=1/" > :FILE 2>&1';
        }

        $command = str_replace(':USER', $this->user, $command);
        $command = str_replace(':PASSWORD', $this->password, $command);
        $command = str_replace(':BD', $bd, $command);
        $command = str_replace(':TABLES', $tables, $command);
        $command = str_replace(':FILE', $file, $command);

        echo PHP_EOL . PHP_EOL . $command;
        return system($command);
    }

    /**
     * Imports a sql file into an existing database
     *
     * @param string $db   name to use in the local MySQL for the database
     * @param string $file path where the sql file to import is located
     *
     * @return bool if import was executed correctly
     */
    public function importSQLFile(string $db, string $file): bool
    {
        echo "Importing $file into $db" . PHP_EOL;

        // We import the new database to our mysql server
        $command = 'mariadb -u:USER -p:PASSWORD :DB < ' . $file . ' 2>&1 '
                |> (fn($x) => str_replace(':USER', $this->user, $x))
                |> (fn($x) => str_replace(':PASSWORD', $this->password, $x))
                |> (fn($x) => str_replace(':DB', $db, $x));

        echo $command;
        if (!shell_exec($command)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Starts MySQL
     * @return bool     if we were able to start MySQL
     */
    public function start(): bool
    {
        $answer = shell_exec('sudo /etc/init.d/mysql start 2>&1');
        if (strcmp(trim($answer), 'Starting mysql (via systemctl): mysql.service.') == 0) {
            $ok = true;
        } else {
            $ok = false;
        }
        return $ok;
    }

    /**
     * Stops MySQL
     * @return bool     if we were able to stop MySQL
     */
    public function stop(): bool
    {
        $answer = shell_exec('sudo /etc/init.d/mysql stop 2>&1');
        if (strcmp(trim($answer), 'Stopping mysql (via systemctl): mysql.service.') == 0) {
            $ok = true;
        } else {
            $ok = false;
        }
        return $ok;
    }

    /**
     * Restarts MySQL
     * @return bool     if we were able to start and stop MySQL
     */
    public function restart(): bool
    {
        $ok = true;
        $ok *= $this->stop();
        $ok *= $this->start();
        return $ok;
    }

}