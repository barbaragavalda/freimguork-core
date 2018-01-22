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
 * @author Bàrbara Gavaldà <bgavalda@appaqui.com>
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
        $this->absoluteFolder = $config->getStaticDomain() . 'public/upload/';
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
        $fileName = $this->fileName;
        if( $suffix != '' ) $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '-' . $suffix . '.' . pathinfo($fileName, PATHINFO_EXTENSION);
        return $fileName;
    }

	/**
	 * load
	 * @return string
	 */
	private function load(){
		$this->initFolder();

		$fileName = $this->getFileName();
		if( $fileName != "" ){
			$this->fileName = $fileName;
		}
	}

	/**
	 * initFolder
	 * we calculate to which subfolder the file is based on its id
     * every 20 files go to a different folder
	 * @return int
	 */
	private function initFolder(){
		$this->folderID = ceil( $this->id / 20 );
	}

	/**
	 * getFileName
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
     * @param $table
     * @param $field
     * @param $itemID
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
                SET '.$field.' = ""
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
        unlink($filePath);

        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $files = scandir($this->relativeFolder . $this->folderID . '/');
        foreach($files as $file){
            if( StringUtils::startsWidth($file, $fileName) ){
                $suffixFilePath = $this->relativeFolder . $this->folderID . '/' . $file;
                unlink($suffixFilePath);
            }
        }
	}

    /**
     * save
     * saves image on disk and database
     * @param $file
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

	public function saveQr($text, $qrName){
        $this->prepareSave($qrName);
        $path = $this->getRelativePath();

        $qr = new BaconQrCodeGenerator();
        $qr->format('svg');
        $qr->generate($text, $path);
        return $this->saveToDatabase();
	}

    private function prepareSave($fileName){
        $this->id = $this->mysql->getMaxId('appacman_file');
        $this->fileName = $this->id . '_' . StringUtils::removeSpecialCharacters($fileName);

        // prepare path
        $this->initFolder();
        $this->createFolder($this->relativeFolder);
        $this->createFolder($this->relativeFolder . $this->folderID);
    }

    private function saveToDatabase(){
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
     * @param $path
     * @return bool
     */
    function checkImageOrientation($path){
        $ext = $this->getImageExtension();
        if( $ext == 'jpg' || $ext == 'jpeg' ){
            $image = imagecreatefromjpeg($path);
            $exif = exif_read_data($path);
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
     * @param $dimensions
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
                    $error = imagejpeg($emptyImage, $destinationPath, 100);
                    break;
                case 'gif':
                    $error = imagegif($emptyImage, $destinationPath);
                    break;
                case 'png':
                    $error = imagepng($emptyImage, $destinationPath, 0);
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
     * @param $image
     * @param $max_width
     * @param $max_height
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

        $newImage = imagecreatetruecolor($width,$height);
        if(  $this->getImageExtension() == 'png' ){
            imagealphablending( $newImage, false );
            imagesavealpha( $newImage, true );
        }
        $source = $this->createEmptyImage( $image );
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
        return $newImage;
    }

    public function getSize($suffix = ''){
        $size = getimagesize($this->getRelativePath($suffix));
        if( $size !== false ){
            return array(
                'width' => $size[0],
                'height' => $size[1]
            );
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