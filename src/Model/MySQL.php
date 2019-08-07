<?php

namespace Core\Model;

use Core\Utils\Config;
use Core\Utils\Exception;

/**
 * Class Model
 *
 * MySQL connection
 *
 * @package Core\Model
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
 * @date 26/10/2017
 */
class MySQL {

    /**
     * @var \Core\Model\MySQL. singleton
     */
    private static $instance;

    /**
     * @var string $databaseName
     */
    private $databaseName = '';

    /**
     * @var string $user
     */
    private $user = '';

    /**
     * @var string $password
     */
    private $password = '';

    /**
     * @var null. Database connection
     */
    private $pdo = null;

    /**
     * @var \PDOStatement null. Current statement
     */
    private $statement = null;

    /**
     * @var boolean null. was the statement successful
     */
    public $success = false;

    private function __construct() {
        $config = Config::getInstance();
        $dbConfig = $config->get('db');

        if( !empty($dbConfig) && count($dbConfig) ){
            $this->databaseName = $dbConfig['name'];
            $this->user = $dbConfig['user'];
            $this->password = $dbConfig['password'];
            $host       = $dbConfig['host'];

            if( $host != '' && $this->user != '' && $this->databaseName != '' ){
                $dsn = 'mysql:dbname=' . $this->databaseName . ';host=' . $host;
                try {
                    $this->pdo = new \PDO($dsn, $this->user, $this->password);
                    $this->pdo->exec('SET CHARACTER SET utf8');
                    $this->pdo->exec('SET SESSION group_concat_max_len = 10000000');
                    $this->pdo->exec('SET GLOBAL sql_mode = "STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"');
                } catch (\PDOException $e) {
                    throw new Exception('PDO connection error: <em>' . $e->getMessage() . '</em>');
                }
            }
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @return \Core\Model\MySQL
     */
    public static function getInstance(){
        if( self::$instance === null) {
            self::$instance = new MySQL();
        }
        return self::$instance;
    }

    /**
     * return the result of a simple query
     * @param string $sql       Query
     * @param array $params     binding params
     * @return array            Result
     */
    public function query($sql, $params = array()) {
        if( $this->pdo != null ){
            $this->statement = $this->pdo->prepare($sql);
            if( !empty($params) ){
                foreach( $params as $key => $param ){
                    $this->statement->bindParam(':' . $key, $param['value'], $param['type']);
                }
            }
            $this->success = $this->statement->execute();
            return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        return array();
    }

    public function getState(){
        return $this->success;
    }

    /**
     * new free ID form specific table
     * @param string $table
     * @return number|bool
     */
    public function getMaxId( $table ){
        $sql = '
    		SELECT MAX(id_'.$table.') +1 AS id
    		FROM '.$table.'
    	';
        $result = $this->query($sql);
        if( count($result) ){
            $id = $result[0]['id'];
            if( $id == '' ) $id = 1;
            return $id;
        }
        return false;
    }

    /**
     * how many rows where afected with the last query
     * @return int
     */
    public function rowCount(){
        return $this->statement->rowCount();
    }

    /**
     * check if table exisrts
     * @param string $table     table name
     * @return bool
     */
    public function tableExists($table){
        $sql = '
			SELECT table_name
			FROM information_schema.tables
			WHERE table_schema = :bbdd_name
			AND table_name = :table_name
		';
        $params = array(
            'bbdd_name'     => array('value'=>$this->databaseName,  'type'=>\PDO::PARAM_STR),
            'table_name'    => array('value'=>$table,               'type'=>\PDO::PARAM_STR),
        );
        $result = $this->query($sql, $params);

        if( count($result) ){
            return true;
        }
        return false;
    }

    /**
     * check if field exists in table
     * @param string $table     table name
     * @param string $field     field name
     * @return bool
     */
    public function fieldExists($table, $field){
        $sql = '
			SELECT * 
			FROM information_schema.columns 
			WHERE table_schema = :bbdd_name
			AND table_name = :table_name
			AND column_name = :field
		';
        $params = array(
            'bbdd_name'     => array('value'=>$this->databaseName, 'type'=>\PDO::PARAM_STR),
            'table_name'    => array('value'=>$table, 'type'=>\PDO::PARAM_STR),
            'field'         => array('value'=>$field, 'type'=>\PDO::PARAM_STR),
        );
        $result = $this->query($sql, $params);

        if( count($result) ){
            return true;
        }
        return false;
    }

    /**
     * data type and mandatory information of specific field
     * @param string $table     table name
     * @param string $field     field name
     * @return array
     */
    public function fieldDescription($table, $field){
        $fieldDesc = $this->field($table, $field);
        if( $fieldDesc === false ){
            $fieldDesc = $this->field($table.'_lang', $field);
        }

        if( count($fieldDesc) ){
            //r($fieldDesc);
            $required = $fieldDesc['Null'] == 'YES' ? false : true;
            return array(
                'type' => $fieldDesc['Type'],
                'required' => $required
            );
        }
        return array();
    }

    private function field($table, $field){
        $sql = '
			SHOW COLUMNS
			FROM '.$table.'
			WHERE Field = :field
		';
        $params = array(
            'field' => array('value'=>$field, 'type'=>\PDO::PARAM_STR)
        );
        $field = $this->query($sql, $params);

        if( count($field) ){
            return $field[0];
        }
        return false;
    }

    /**
     * magic method that allows to invoke any method of PDO
     * @param string $function_name     Function name
     * @param array $args               Parameters of the function
     * @return mixed                    result of the function
     * @throws Exception
     */
    public function __call($function_name, $args) {
        if (method_exists($this->pdo, $function_name)) {
            return call_user_func_array(array($this->pdo, $function_name), $args);
        } else {
            throw new Exception('The method <em>' . $function_name . '</em> doesn\'t exists on PDO. Check <a href="http://es.php.net/manual/en/book.pdo.php" target="_blank">the manual</a> for more information');
        }
    }

    /**
     * Dumps a given database to a given file.
     * @param string $bd        is the database to dump.
     * @param string $file      is the file where it'll be dumped to.
     * @param bool $inserts     if we need to add the table content to the dumped DB.
     * @return string           with the executed command.
     */
    public function dumpDB($bd,$file,$inserts=false){
        echo 'Generanting file for '.$bd.'...\n';

        if( $inserts )  $command = 'mysqldump -u:USER -p:PASSWORD --skip-comments --compact --add-drop-database --databases --add-drop-database --add-drop-table --set-charset :BD > :NOM_FITXER 2>&1';
        else $command = 'mysqldump -u:USER -p:PASSWORD -d --single-transaction --databases --add-drop-database --add-drop-table --set-charset :BD | sed "s/ AUTO_INCREMENT=[0-9]*\b/ AUTO_INCREMENT=1/" > :NOM_FITXER 2>&1';

        $command = str_replace(':USER',$this->user,$command);
        $command = str_replace(':PASSWORD',$this->password,$command);
        $command = str_replace(':BD',$bd,$command);
        $command = str_replace(':NOM_FITXER',$file,$command);

        echo $command;
        return system($command);
    }

    /**
     * Imports a database into MySQL given a filename.
     * @param string $db_name       name to use in the local MySQL for the database
     * @param string $sqlFile       path where the sql file to import is located.
     * @param string $sqlTmpFile    path where the temporary sql file must be stored.
     */
    public function importDatabase($db_name,$sqlFile,$sqlTmpFile){
        echo 'Importing '.$sqlFile.' as '.$db_name.' ('.$sqlTmpFile.')...\n';
        // We remove the database if it exists
        system('rm '.$sqlTmpFile);
        $this->query('DROP database '.$db_name);
        $command = 'cp '.$sqlFile.' '.$sqlTmpFile;
        system($command);

        // We get the name of the database to import.
        $db_info = shell_exec('cat '.$sqlTmpFile.' | grep USE');
        $db_info = explode('`',$db_info);
        $db_current_name = $db_info[1];

        // We replace the current name with the name we want to use.
        $command = 'echo "%s/'.$db_current_name.'/'.$db_name.'/g
					w
					q
					" | ex '.$sqlTmpFile;
        system($command);

        // We import the new database to our mysql server
        $command = 'mysql -u:USER -p:PASSWORD < '.$sqlTmpFile;
        $command = str_replace(':USER',$this->user,$command);
        $command = str_replace(':PASSWORD',$this->password,$command);
        system($command);
    }

    /**
     * This function compares two databases. It requires the installation of mysql-utilities (aptitude install mysql-utilities)
     * @param string $current_db    with the current database.
     * @param string $new_db        with the new database.
     */
    public function compareDatabase($current_db,$new_db){
        echo 'Comparing databases '.$current_db.' and '.$new_db.'...\n';
        $command = 'mysqldbcompare --server1=:USER::PASSWORD@localhost --server2=:USER::PASSWORD@localhost '.$current_db.':'.$new_db.' --run-all-tests --skip-data-check --skip-row-count';
        $command = str_replace(':USER',$this->user,$command);
        $command = str_replace(':PASSWORD',$this->password,$command);
        system($command);
    }

    /**
     * Starts MySQL
     * @return bool     if we were able to start MySQL
     */
    public function start(){
        $answer = shell_exec('sudo /etc/init.d/mysql start 2>&1');
        if(strcmp(trim($answer),'Starting mysql (via systemctl): mysql.service.')==0) $ok = true;
        else $ok = false;
        return $ok;
    }

    /**
     * Stops MySQL
     * @return bool     if we were able to stop MySQL
     */
    public function stop(){
        $answer = shell_exec('sudo /etc/init.d/mysql stop 2>&1');
        if(strcmp(trim($answer),'Stopping mysql (via systemctl): mysql.service.')==0) $ok = true;
        else $ok = false;
        return $ok;
    }

    /**
     * Restarts MySQL
     * @return bool     if we were able to start and stop MySQL
     */
    public function restart(){
        $ok = true;
        $ok *= $this->stop();
        $ok *= $this->start();
        return $ok;
    }

}