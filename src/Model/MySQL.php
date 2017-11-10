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
     * @var \Core\Utils\MySQL. singleton
     */
    private static $instance;

    /**
     * @var string $databaseName
     */
    private $databaseName = '';

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

        if( count($dbConfig) ){
            $this->databaseName =  $dbConfig['name'];

            $host       = $dbConfig['host'];
            $user       = $dbConfig['user'];
            $password   = $dbConfig['password'];
            $database   = $this->databaseName;

            if( $host != '' && $user != '' && $database != '' ){
                $dsn = 'mysql:dbname=' . $database . ';host=' . $host;
                try {
                    $this->pdo = new \PDO($dsn, $user, $password);
                    $this->pdo->exec("SET CHARACTER SET utf8");
                } catch (\PDOException $e) {
                    throw new Exception("PDO connection error: <em>" . $e->getMessage() . "</em>");
                }
            }
        }
    }

    /**
     * initializes the instance (if needed) based on the singleton pattern
     * @return \Core\Config
     */
    public static function getInstance(){
        if( self::$instance === null) {
            self::$instance = new MySQL();
        }
        return self::$instance;
    }

    /**
     * return the result of a simple query
     * @param string $sql . Query
     * @param array $params . binding params
     * @return array. Result
     */
    public function query($sql, $params = array()) {
        $this->statement = $this->pdo->prepare($sql);
        if( !empty($params) ){
            foreach( $params as $key => $param ){
                $this->statement->bindParam(':' . $key, $param['value'], $param['type']);
            }
        }
        $this->success = $this->statement->execute();
        return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * new free ID form specific table
     * @param string $table
     * @return number
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
     * tableExists
     * check if table exisrts
     * @param string $table. table name
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
     * fieldDescription
     * data type and mandatory information of specific field
     * @param string $table. table name
     * @param string $field. field name
     * @return array
     */
    public function fieldDescription($table, $field){
        $fieldDesc = $this->field($table, $field);
        if( $fieldDesc === false ){
            $fieldDesc = $this->field($table.'_lang', $field);
        }

        if( count($fieldDesc) ){
            //r($fieldDesc);
            $required = $fieldDesc['Null'] == 'YES' ? true : false;
            return array(
                'type' => $fieldDesc['Type'],
                'required' => $required
            );
        }
        return '';
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
     * @param string $function_name . Function name
     * @param array $args . Parameters of the function
     * @return mixed. result of the function
     * @throws Exception
     */
    public function __call($function_name, $args) {
        if (method_exists($this->pdo, $function_name)) {
            return call_user_func_array(array($this->pdo, $function_name), $args);
        } else {
            throw new Exception("The method <em>" . $function_name . "</em> doesn't exists on PDO. Check <a href='http://es.php.net/manual/en/book.pdo.php' target='_blank'>the manual</a> for more information");
        }
    }

}