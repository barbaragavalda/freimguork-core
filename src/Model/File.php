<?php

namespace Core\Model;

use Core\Model\Utils\StringUtils;
use Core\Utils\Config;
use SimpleSoftwareIO\QrCode\BaconQrCodeGenerator;

/**
 * Class File
 *
 * saves images on disk, data base
 * o
 *
 * @package Core\Routing
 * @author B�rbara Gavald� <bgavalda@appaqui.com>
 * @date 25/10/2017
 */
class File extends Model {

    /**
     * @var int $folderID. Folder number where the file is
     */
    private $folderID = 0;

    /**
     * @var string $absoluteFolder. upload directory
     */
    private $absoluteFolder = '';

    /**
     * @var string $relativeFolder. upload directory
     */
    private $relativeFolder = '';

    /**
     * @var string $fileName. Name of file
     */
    private $fileName = '';

    public function __construct( $id = 0 ){
        parent::__construct();

        // init directory
        $config = Config::getInstance();

        $configDomains = $config->get('domains', 'upload');
        if( is_array($configDomains) && count($configDomains) ){
            $domains = $configDomains;
            $folder = $domains[array_rand($domains)];
            $url = parse_url($config->getBaseDomain() . 'public/upload/');
            $host = $url['host'];
            if( str_starts_with($host, 'pre.') ){
                $host = str_replace('pre.', '', $host);
            }
            $this->absoluteFolder = $url['scheme'] .'://' . $folder .'.'.$host.$url['path'];
        }else{
            $this->absoluteFolder = $config->getBaseDomain() . 'public/upload/';
        }
        $this->relativeFolder = 'upload/';

        //load current image
        $this->id = $id;
        if( $this->id ) $this->load();
    }

    public function getID(){
        return $this->id;
    }

    public function getRelativePath($suffix = ''){
        return $this->relativeFolder . $this->folderID . '/' . $this->getFileNameWidthSuffix($suffix);
    }

    public function getAbsolutePath($suffix = ''){
        $relativePath = $this->getRelativePath($suffix);
        if( file_exists($relativePath) ){
            return $this->absoluteFolder . $this->folderID . '/' . $this->getFileNameWidthSuffix($suffix);
        }
        return null;
    }

    private function getFileNameWidthSuffix($suffix = ''){
        $fileBasename = pathinfo($this->fileName, PATHINFO_FILENAME);
        $fileExtension = strtolower( pathinfo($this->fileName, PATHINFO_EXTENSION) );

        $fileName = $fileBasename . '.' . $fileExtension;
        if( $suffix != '' ) $fileName = $fileBasename . '-' . $suffix . '.' . $fileExtension;
        return $fileName;
    }

    /**
     * load
     */
    private function load(){
        $this->initFolder();

        $fileName = $this->getFileName();
        if( $fileName != "" ){
            $this->fileName = $fileName;
        }
    }

    /**
     * we calculate to which subfolder the file is based on its id
     * every 20 files go to a different folder
     */
    private function initFolder(){
        $this->folderID = ceil( $this->id / 20 );
    }

    /**
     * get the file name from the database
     * @return string
     */
    private function getFileName(){
        if( $this->id != '' ){
            $sql = '
				SELECT file_name
				FROM appacman_file
				WHERE id_appacman_file = :id_appacman_file
			';
            $params = array(
                'id_appacman_file' => array('value'=>$this->id, 'type'=>\PDO::PARAM_INT)
            );
            $img = $this->mysql->query($sql, $params);
            if( count($img) )  return $img[0]['file_name'];
        }
        return '';
    }

    /**
     * delete image form database (1,2) and disc (3)
     * @param string $table
     * @param string $field
     * @param integer $itemID
     * @return bool
     */
    public function delete($table, $field, $itemID){
        $tableDelete = false;
        if( $this->mysql->fieldExists($table, $field) ){
            $tableDelete = $table;
        }else if( $this->mysql->fieldExists($table.'_lang', $field) ){
            $tableDelete = $table . '_lang';
        }

        if( $tableDelete !== false ){
            // 1. delete from table
            $sql = '
                UPDATE '.$tableDelete.'
                SET '.$field.' = "0"
                WHERE id_'.$tableDelete.' = :id
            ';
            $params = array(
                'id' => array('value'=>$itemID, 'type'=>\PDO::PARAM_INT)
            );
            $this->mysql->query($sql, $params);

            // 2. delete from appacman_file
            $this->deleteFromFileTable();

            // 3. remove from disk
            $this->deleteFromDisk();

            return true;
        }
        return false;
    }

    public function deleteFromFileTable(){
        $sql = '
            DELETE FROM appacman_file
            WHERE id_appacman_file = :id
        ';
        $params = array(
            'id' => array('value'=>$this->id, 'type'=>\PDO::PARAM_INT)
        );
        $this->mysql->query($sql, $params);
    }

    public function deleteFromDisk(){
        $filePath = $this->getRelativePath();
        if( file_exists($filePath) && !is_dir($filePath) ) unlink($filePath);

        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        if( $fileName ){
            $files = scandir($this->relativeFolder . $this->folderID . '/');
            foreach($files as $file){
                if( $file != '.' && $file != '..' ){
                    if( StringUtils::startsWidth($file, $fileName) ){
                        $suffixFilePath = $this->relativeFolder . $this->folderID . '/' . $file;
                        if( file_exists($suffixFilePath) ) unlink($suffixFilePath);
                    }
                }
            }
        }
    }

    /**
     * save
     * saves image on disk and database
     * @param array $file
     * @return false|int
     */
    public function save($file){
        if( $file['error'] == 0 ){
            $this->prepareSave($file['name']);
            $path = $this->getRelativePath();

            if( move_uploaded_file($file['tmp_name'], $path) ){
                $this->checkImageOrientation($path);
                return $this->saveToDatabase();
            }
        }
        return false;
    }

    /**
     * copy file to uploads
     * @param string $fileName
     * @param string $origin
     * @return false|int
     */
    public function copy($fileName, $origin){
        $this->prepareSave($fileName);
        $path = $this->getRelativePath();

        if( copy($origin, $path) ){
            $this->checkImageOrientation($path);
            return $this->saveToDatabase();
        }
        return false;
    }

    public function saveQr($text, $qrName, $size = 1000){
        $this->prepareSave($qrName);
        $path = $this->getRelativePath();

        $qr = new BaconQrCodeGenerator();
        $qr->format('png');
        $qr->size($size);
        $qr->generate($text, $path);
        return $this->saveToDatabase();
    }

    public function prepareSave($fileName, $withID = true){
        $this->id = $this->mysql->getMaxId('appacman_file');

        $this->fileName = '';
        if ($withID) {
            $this->fileName = $this->id . '_';
        }
        $this->fileName .= StringUtils::removeSpecialCharacters($fileName);

        // prepare path
        $this->initFolder();
        $this->createFolder($this->relativeFolder);
        $this->createFolder($this->relativeFolder . $this->folderID);
    }

    public function saveToDatabase(){
        $sql = '
            INSERT INTO appacman_file
            SET id_appacman_file = :id, file_name = :file_name
        ';
        $params = array(
            'id'        => array('value'=>$this->id,        'type'=>\PDO::PARAM_INT),
            'file_name' => array('value'=>$this->fileName,  'type'=>\PDO::PARAM_STR)
        );
        $this->mysql->query($sql, $params);
        if( $this->mysql->rowCount() > 0 ){
            return $this->id;
        }else{
            $this->deleteFromDisk();
        }
        return false;
    }

    /**
     * createFolder
     * @param string $folder
     */
    private function createFolder( $folder ){
        if( !(file_exists($folder) && is_dir($folder)) ){
            mkdir($folder, 0777);
        }
    }

    /**
     * rotates the image if needed (only jpg images)
     * @param string $path
     * @return bool
     */
    function checkImageOrientation($path){
        $ext = $this->getImageExtension();
        if( $ext == 'jpg' || $ext == 'jpeg' ){
            $image = imagecreatefromjpeg($path);
            $exif = @exif_read_data($path);
            if (empty($exif['Orientation'])) {
                return false;
            }

            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, - 90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
            imagejpeg($image, $path);

            return true;
        }
        return false;
    }

    /**
     * resize image and save it
     * @param array $dimensions
     */
    public function resize($dimensions){
        foreach($dimensions as $dimension){
            $originPath = $this->getRelativePath();
            $destinationPath = $this->getRelativePath($dimension['suffix']);
            $emptyImage = $this->resizedImage($originPath, $dimension['width'], $dimension['height']);

            $error = false;
            switch($this->getImageExtension()){
                case 'jpg':
                case 'jpeg':
                    $error = imagejpeg($emptyImage, $destinationPath);
                    break;
                case 'gif':
                    $error = imagegif($emptyImage, $destinationPath);
                    break;
                case 'png':
                    $error = imagepng($emptyImage, $destinationPath);
                    break;
                default:
                    $error = true;
                    break;
            }

            if( !$error ) chmod($destinationPath, 0777);
        }
    }

    /**
     * create image with new dimensions keeping aspect ratio
     * @param string $image
     * @param integer $max_width
     * @param integer $max_height
     * @return resource
     */
    private function resizedImage($image, $max_width, $max_height){
        $size = $this->getSize();
        $orig_width = $size['width'];
        $orig_height = $size['height'];
        $width = $orig_width;
        $height = $orig_height;

        # height
        if ($height > $max_height) {
            $width = ($max_height / $height) * $width;
            $height = $max_height;
        }

        # width
        if ($width > $max_width) {
            $height = ($max_width / $width) * $height;
            $width = $max_width;
        }

        $source = $this->createEmptyImage( $image );
        return imagescale($source, $width, $height);
    }

    public function getSize($suffix = ''){
        $relativePath = $this->getRelativePath($suffix);
        if( file_exists($relativePath) ){
            $size = getimagesize($relativePath);
            if( $size !== false ){
                return array(
                    'width' => $size[0],
                    'height' => $size[1]
                );
            }
        }
        return false;
    }

    /**
     * getImageExtension
     * @return int
     */
    private function getImageExtension(){
        $path = $this->getAbsolutePath();
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * createEmptyImage
     * creates empty image depending on image extension
     * @param string $image
     * @return resource
     */
    private function createEmptyImage( $image ){
        $image_type = $this->getImageExtension();
        $src = null;
        switch ($image_type){
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($image);
                break;
            case 'gif':
                $src = imagecreatefromgif($image);
                break;
            case 'png':
                $src = imagecreatefrompng($image);
                break;
            default: $src = null;  break;
        }
        return $src;
    }

}
