<?php

namespace Core\Model;

use Core\Model\Encryptor\TwoWay;
use Core\Model\MySQL\Manager;
use Core\Model\MySQL\PDO;
use Core\Utils\Session;

/**
 * Class Model
 * MySQL connection
 */
class Model
{

    /**
     * @var  ?PDO  Database connection
     */
    public ?PDO $mysql = null;

    /**
     * @var ?int  current lang id
     */
    protected ?int $langID = null;

    /**
     * @var ?int     identifier
     */
    protected ?int $id = 0;

    /**
     * @var string  encrypting key
     */
    protected string $key = '';

    /**
     * @var ?string    creation timestamp
     */
    protected ?string $created = null;

    public function __construct(?PDO $mysql = null, ?Session $session = null)
    {
        $this->mysql = $mysql ?? Manager::getInstance();

        $session       = $session ?? Session::getInstance();
        $this->langID = $session->get('lang_id');
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function setID(int $id): void
    {
        $this->id = $id;
    }

    protected function setKey(): void
    {
        $this->key = $this->id . '_' . $this->created . '_';
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    protected function getFile(?int $fileID = null, string $suffix = ''): ?string
    {
        if ($fileID === null) {
            return '';
        }

        $file = new File($fileID);
        $path = $file->getAbsolutePath($suffix);
        if ($path == null && $suffix != '') {
            $path = $file->getAbsolutePath();
        }
        return $path;
    }

    protected function getFBImage(int $fileID, string $suffix = ''): ?array
    {
        $file = new File($fileID);
        $size = $file->getSize($suffix);
        if ($size !== false) {
            return array(
                'image'  => $file->getAbsolutePath($suffix),
                'width'  => $size['width'],
                'height' => $size['height']
            );
        }
        return null;
    }

    protected function uploadFile(array $postFile, ?int $fieldID = null)
    {
        $file = null;
        if (!empty($postFile)) {
            $file = new File();
            if (!$file->save($postFile, $fieldID)) {
                return null;
            }
        }
        return $file;
    }

    /**
     * get image slider
     *
     * @param string  $table
     * @param string  $suffix
     * @param string  $where
     * @param array   $params
     * @param string  $fields
     * @param ?string $orderBy
     *
     * @return array
     */
    protected function getImageSlider(
        string $table,
        string $suffix = '',
        string $where = '',
        array $params = array(),
        string $fields = 'image',
        ?string $orderBy = null
    ): array {
        if ($orderBy != null) {
            $orderBy = "ORDER BY $orderBy ASC";
        }
        $sql    = "
            SELECT $fields
            FROM $table
            $where
            $orderBy
        ";
        $images = $this->mysql->query($sql, $params);

        if (count($images)) {
            foreach ($images as &$image) {
                $image['image'] = $this->getFile($image['image'], $suffix);
            }
            return $images;
        }
        return array();
    }

    /**
     * create where string
     *
     * @param array  $conditions
     * @param string $type
     *
     * @return string
     */
    public function getWhere(array $conditions, string $type = ' AND '): string
    {
        if (count($conditions)) {
            return 'WHERE ' . implode($type, $conditions);
        }
        return '';
    }

    /**
     * add fields to query
     *
     * @param array  $fields array('<name>' => true | false)
     * @param string $sql
     * @param array  $params
     * @param bool   $isPost
     */
    protected function addFields(array $fields, string &$sql, array &$params, bool $isPost = false): void
    {
        foreach ($fields as $field => $encrypted) {
            $value = $this->$field;
            if ($isPost) {
                $value = $_POST[ $field ] ?? '';
            }
            if (empty($value)) {
                $value = '';
            } else {
                if ($encrypted) {
                    $value = TwoWay::encrypt($value, $this->key . $field);
                }
            }
            $sql              .= ", `$field` = :$field";
            $params[ $field ] = array('value' => $value, 'type' => \PDO::PARAM_STR);
        }
    }

    protected function getGet($key, $default = '')
    {
        return $this->getFormField($key, $_GET, $default);
    }

    protected function getPost($key, $default = '')
    {
        return $this->getFormField($key, $_POST, $default);
    }

    protected function getFileForm($key)
    {
        return $this->getFormField($key, $_FILES, array());
    }

    private function getFormField($key, $array, $default)
    {
        if (isset($array[ $key ])) {
            return $array[ $key ];
        }
        return $default;
    }

    public function getCacheDef(string $method, array $params): mixed
    {
        return false;
        // or return array('ttl' => 300, 'key' => $params);
    }

    /**
     * Magic method that tries to call the function in the MySQL class instead of this (SQL Manager) class.
     *
     * @param string $method name of the function.
     * @param array  $args   with the parameters passed to the function
     *
     * @return mixed                    return of the called function
     */
    public function __call(string $method, array $args): mixed
    {
        if ($this->mysql && method_exists($this->mysql, $method)) {
            return call_user_func_array(array($this->mysql, $method), $args);
        }
        return null;
    }

}