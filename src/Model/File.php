<?php

namespace Core\Model;
use Core\Utils\Config;

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
     * @var int $id. Id from table appacman_file
     */
	private $id = 0;

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

	private $path = '';			//ruta completa del arxiu

	private $id_contingut = '';	//id del contingut al que pertany
	private $id_item = '';		//id de l'item al que pertany
    private $nom_camp = '';
	
	private $error_upload = false;	//s'ha produit un error al pujar?

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

    public function getAbsolutePath(){
        $relativePath = $this->relativeFolder . $this->folderID . '/' . $this->fileName;
        if( file_exists($relativePath) ){
            return $this->absoluteFolder . $this->folderID . '/' . $this->fileName;
        }
        return null;
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


//    /**
//     * getArxiu
//     * nom del arxiu (p.ex: 1_arxiu1.png)
//     * @return string
//     */
//    public function getArxiu(){
//        return $this->file_name;
//    }
//
//    public function getId(){
//        return $this->id;
//    }
//
//    /**
//     * getPath
//     * nom del arxiu (p.ex: 1_arxiu1.png)
//     * @return string
//     */
//    public function getPath(){
//        return $this->id_carpeta.'/'.$this->file_name;
//    }
//
//	/**
//	 * getRuta
//	 * ruta completa del arxiu (p.ex: /appacman/upload/1/1_arxiu1.png)
//	 * @return string
//	 */
//	public function getRuta($relative = false){
//		if( $this->path != '' ){
//            if( $relative )
//                return ruta_upload.$this->path;
//            else
//                return ruta_upload_absoluta.$this->path;
//        }
//	}
//
//	/**
//	 * getRutaAbsoluta
//	 * ruta absoluta del arxiu (p.ex: http://appacman.dev/appacman/upload/1/1_arxiu1.png)
//	 * @return string
//	 */
//    public function getRutaAbsoluta(){
//        if( $this->path != '' )
//            return ruta_absoluta.ruta_upload.$this->path;
//    }
//
//    public function getErrorUpload(){
//        return $this->error_upload;
//    }
//
//	/**
//	 * setIdContingut
//	 * guardem el id del contingut al que pertan l'arxiu
//	 * @param int $id_contingut
//	 */
//	public function setIdContingut( $id_contingut ){
//		$this->id_contingut = $id_contingut;
//	}
//
//
//	/**
//	 * setIdItem
//	 * guardem el id de l'item al que pertan l'arxiu
//	 * @param int $id_item
//	 */
//	public function setIdItem( $id_item ){
//		$this->id_item = $id_item;
//	}
//
//	/**
//	 * existeixArxiu
//	 * comprova si l'arxiu existeix
//	 * @return boolean
//	 */
//	public function existeixArxiu(){
//		$ruta = ruta_upload.$this->path;
//		return file_exists($ruta) && !is_dir($ruta);
//	}
//
//	/**
//	 * existeixCarpeta
//	 * comprova si la capeta existeix
//	 * @return boolean
//	 */
//	private function existeixCarpeta( $dir ){
//		return file_exists($dir) && is_dir($dir);
//	}
//
//	/**
//	 * guardar
//	 * mou la imatge de la carpeta temp a upload
//	 * i afegeix la imatge a la carpeta contingut_arxiu
//	 * poden haver errors
//	 * @param string $nom_camp		camp on es guarda la imatge
//	 * @param string $file			$_FILE del post del formulari
//	 */
//	public function guardar($nom_camp, $file){
//		if( $file['error'] == 0 ){
//			//nom arxiu
//			$this->id = $GLOBALS['db']->getMaxId( 'appacman_file' );
//			$this->file_name = Utils::normalize( $this->id.'_'. str_replace(' ', '', $file['name']) );
//			$this->calculaIdCarpeta();
//			$ruta = ruta_upload.$this->id_carpeta.'/'.$this->file_name;
//
//			//creem carpetes
//			$this->creaCarpeta(ruta_upload);
//			$this->creaCarpeta(ruta_upload.$this->id_carpeta);
//
//			if( move_uploaded_file($file['tmp_name'], $ruta) ){
//                $this->image_fix_orientation($ruta);
//
//                //guardem la imatge a la taula imatge
//				$camps = array();
//				$camps['id_appacman_file'] = $this->id;
//				$camps['file_name'] = $this->file_name;
//				$GLOBALS['db']->insert('appacman_file', $camps);
//				//guardem a la taula contingut que toqui
//				$this->nom_camp = str_replace('arxiu-', '', $nom_camp);
//				$_POST[$this->nom_camp] = $this->id;
//
//                $this->path = $this->id_carpeta.'/'.$this->file_name;
//			}else{
//				$this->error_upload = true;
//			}
//		}
//	}
//
//    function image_fix_orientation($path){
//        $image = imagecreatefromjpeg($path);
//        $exif = exif_read_data($path);
//
//        if (empty($exif['Orientation'])) {
//            return false;
//        }
//
//        switch ($exif['Orientation']) {
//            case 3:
//                $image = imagerotate($image, 180, 0);
//                break;
//            case 6:
//                $image = imagerotate($image, - 90, 0);
//                break;
//            case 8:
//                $image = imagerotate($image, 90, 0);
//                break;
//        }
//
//        imagejpeg($image, $path);
//
//        return true;
//    }
//
//	/**
//	 * creaCarpeta
//	 * crea subcarpeta dins de upload (si no existeix)
//	 * @param string $dir
//	 */
//	private function creaCarpeta( $dir ){
//		if( !$this->existeixCarpeta($dir) ){
//			mkdir($dir, 0777);
//		}
//	}
//
//	/**
//	 * eliminar
//	 * elimina l'arxiu de les taules a les que pertany
//	 * @param string $nom_camp			camp on es guarda la imatge
//	 * @param int $id_taula_idioma		idioma de la imatge (si en te)
//	 * @return boolean					s'ha eliminat?
//	 */
//	public function eliminar( $nom_camp, $id_taula_idioma = '' ){
//		$retorn = '';
//
//		$this->checkContent( $this->id_contingut );
//		if( !$this->error ){
//			if( $id_taula_idioma == '' && $GLOBALS['db']->fieldExists($this->nom_taula, $nom_camp) ){
//				//actualitzem la taula $this->nom_taula
//				$taula = $this->nom_taula;
//				$camps = array();
//				$camps['id_'.$taula] = $this->id_item;
//				$camps[$nom_camp] = NULL;
//				$retorn = $GLOBALS['db']->update($taula, $camps);
//
//			}else if( $id_taula_idioma != '' && $GLOBALS['db']->fieldExists($this->nom_taula.'_lang', $nom_camp) ){
//				//actualitzem la taula $this->nom_taula.'_lang'
//				$taula = $this->nom_taula.'_lang';
//				$camps = array();
//				$camps['id_'.$taula] = $id_taula_idioma;
//				$camps[$nom_camp] = NULL;
//				$retorn = $GLOBALS['db']->update($taula, $camps);
//			}else
//				$retorn = false;
//		}else
//			$retorn = false;
//
//		if( $retorn ){
//			//eliminem imatge del tot
//			unlink(ruta_upload.$this->path);
//			$GLOBALS['db']->delete('appacman_file', 'id_appacman_file', $this->id);
//		}
//
//		return $retorn;
//	}
//
//	/**
//	 * resize
//	 * si s'han canviat les dimensions de la image, la redimensiona
//	 */
//	public function resize(){
//		$sql = '
//		    SELECT width, height, prefix
//		    FROM appacman_file_resize AS afr
//		    INNER JOIN appacman_field AS af ON af.id_appacman_field = afr.id_appacman_field AND af.id_appacman_content = :id_appacman_content AND af.field_name = :field_name
//		';
//        $params = array(
//            'id_appacman_content' => array('value'=>$this->id_contingut, 'type'=>\PDO::PARAM_INT),
//            'field_name' => array('value'=>$this->nom_camp, 'type'=>\PDO::PARAM_STR),
//        );
//        $resizes = $GLOBALS['db']->queryPrepared($sql, $params);
//        $this->saveResize($resizes);
//	}
//
//    public function saveResize($resizes){
//        foreach($resizes as $resize){
//            $origen_name = ruta_upload.$this->id_carpeta.'/'.$this->file_name;
//            $destination_name = ruta_upload.$this->id_carpeta.'/'.$resize['prefix'].$this->file_name;
//            $new_image = $this->resizeImage($destination_name, $origen_name, $resize['width'], $resize['height']);
//        }
//    }
//
//	/**
//	 * resizeImage
//	 * genera la imatge més petita
//	 * @param string 			desti
//	 * @param string 			origen
//	 * @param int $max_width		ample final
//	 * @param int $max_height		alcada final
//	 * @return string		ruta desti
//	 */
//	private function resizeImage($thumb_image_name, $image, $max_width, $max_height){
//        $newImage_resized = $this->resized($image, $max_width, $max_height);
//        $newImage = $newImage_resized[0];
//        $this->createImage($newImage,$thumb_image_name, $image);
//		chmod($thumb_image_name, 0777);
//
//		return $thumb_image_name;
//	}
//
//	/**
//	 * createEmptyImage
//	 * inicialitzacio de la imatge buida
//	 * @param string $image		origen
//	 * @return resource
//	 */
//	private function createEmptyImage( $image ){
//		$image_type = $this->getImageType( $image );
//		$src = null;
//		switch ($image_type){
//			case 1: $src = imagecreatefromgif($image); break;
//			case 2: $src = imagecreatefromjpeg($image);  break;
//			case 3: $src = imagecreatefrompng($image); break;
//			default: $src = null;  break;
//		}
//		return $src;
//	}
//
//    private function getPngImage($image){
//        list($bg_w, $bg_h) = getimagesize($image);
//        $base_image = imagecreatetruecolor($bg_w,$bg_h);
//        imagealphablending($base_image, false);
//        $col = imagecolorallocatealpha($base_image,255,255,255,127);
//        imagefilledrectangle($base_image,0,0,$bg_w,$bg_h,$col);
//        imagealphablending($base_image,true);
//        imagesavealpha($base_image, true);
//        return $base_image;
//    }
//
//    private function resized($image, $max_width, $max_height){
//        list($orig_width, $orig_height) = getimagesize($image);
//        $width = $orig_width;
//        $height = $orig_height;
//
//        # height
//        if ($height > $max_height) {
//            $width = ($max_height / $height) * $width;
//            $height = $max_height;
//        }
//
//        # width
//        if ($width > $max_width) {
//            $height = ($max_width / $width) * $height;
//            $width = $max_width;
//        }
//
//        $newImage = imagecreatetruecolor($width,$height);
//        if(  $this->getImageType( $image ) == 3 ){
//            imagealphablending( $newImage, false );
//            imagesavealpha( $newImage, true );
//        }
//        $source = $this->createEmptyImage( $image );
//        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
//        return array($newImage, $width, $height);
//    }
//
//	/**
//	 * createImage
//	 * copia la imatge
//	 * @param string $image		image object
//	 * @param string $filepath	desti
//	 */
//	private function createImage($image, $filepath, $original){
//		$ok = false;
//		$image_type = $this->getImageType( $original );
//		switch($image_type){
//			case 1: $ok = imagegif($image, $filepath); break;
//			case 2: $ok = imagejpeg($image, $filepath, 100); break;
//			case 3: $ok = imagepng($image, $filepath, 0); break;
//		}
//	}
//
//	/**
//	 * getImageType
//	 * donada una imatge, retorna el tipus en funcio de la extensio
//	 * @param string $image
//	 * @return int
//	 */
//	private function getImageType( $image ){
//		list($width, $height, $image_type) = getimagesize($image);
//		return $image_type;
//	}
//
//    public function extra(){
//        if( w_appacman_actual == 'aguelo' ){
//            $background_image = '';
//            switch( $this->nom_taula ){
//                case 'dish':
//                case 'local':
//                    $background_image = ruta_upload . 'marca-de-agua-plato.jpg';
//                    break;
//                case 'cocktail':
//                    $background_image = ruta_upload . 'marca-de-agua-coctel.jpg';
//                    break;
//            }
//            if( $background_image ){
//                $image = ruta_upload.$this->id_carpeta.'/'.$this->file_name;
//
//                // create base image and make it transparent
//                $base_image = $this->getPngImage($background_image);
//
//                //background
//                list($bg_w, $bg_h) = getimagesize($background_image);
//                $background = imagecreatefromjpeg($background_image);
//                imagecopy($base_image, $background, 0, 0, 0, 0, $bg_w, $bg_h);
//
//                //overlaped image
//                switch( $this->nom_taula ){
//                    case 'dish':
//                    case 'cocktail':
//                        $base_image = $this->resizeDishCocktail($base_image, $image);
//
//                        //text
//                        $y = 640;
//                        $font_size = 40;
//                        $font = ruta_upload . "AmaticSC-Bold.ttf";
//                        $white = imagecolorallocate($base_image, 255, 255, 255);
//                        for($i=1; $i<=5; $i++){
//                            $text = strtoupper( $_POST['name_'.$i] );
//                            if( $text ){
//                                $copy = $this->getPngImage($background_image);
//                                imagecopy($copy, $base_image, 0, 0, 0, 0, $bg_w, $bg_h);
//
//                                $text = wordwrap($text, 45, "\n");
//                                $text = explode("\n", $text);
//                                if( count($text) > 1 ){
//                                    $y = 623;
//                                    $font_size = 24;
//                                }else{
//                                    $y = 640;
//                                }
//                                foreach($text as $line){
//                                    $text_box = imagettfbbox($font_size, 0 , $font, $line);
//                                    $text_width = $text_box[2] - $text_box[0];
//                                    $x = ($bg_w/2) - ($text_width/2);
//
//                                    imagettftext($copy, $font_size, 0, $x, $y, $white, $font, $line);
//                                    $y += 30;
//                                }
//
//                                @unlink(ruta_upload.$this->id_carpeta.'/watermark-'.$i.'-'.$this->file_name);
//                                imagepng($copy, ruta_upload.$this->id_carpeta.'/watermark-'.$i.'-'.$this->file_name);
//                            }
//                        }
//                        break;
//                    case 'local':
//                        $base_image = $this->resizeLocal($base_image, $image);
//                        @unlink(ruta_upload.$this->id_carpeta.'/watermark-'.$this->file_name);
//                        imagepng($base_image, ruta_upload.$this->id_carpeta.'/watermark-'.$this->file_name);
//                        break;
//                }
//            }
//        }
//    }
//
//    private function resizeDishCocktail($base_image, $image){
//        $offset_x = 61;
//        $offset_y = 63;
//        $dish_max_w = 674;
//        $dish_max_h = 537;
//
//        $dish_resized = $this->resized($image, $dish_max_w, $dish_max_h);
//        $dish = $dish_resized[0];
//        $width = $dish_resized[1];
//        $height = $dish_resized[2];
//        $pos_x = ($dish_max_w - $width) / 2 + $offset_x;
//        $pos_y = ($dish_max_h - $height) / 2 + $offset_y;
//        imagecopy($base_image, $dish, $pos_x, $pos_y, 0, 0, $width, $height);
//        return $base_image;
//    }
//
//    private function resizeLocal($base_image, $image){
//        $offset_x = 46;
//        $offset_y = 48;
//        $hole_w = 704;
//        $hole_h = 626;
//
//        list($orig_width, $orig_height) = getimagesize($image);
//        $width = $orig_width;
//        $height = $orig_height;
//
//        $width = ($hole_w / $orig_height) * $orig_width;
//        $height = $hole_h;
//        if( $width < $hole_w ){
//            $height = ($hole_h / $orig_width) * $orig_height;
//            $width = $hole_w;
//        }
//
//        $pos_x = $pos_y = 0;
//        if( $width > $hole_w ) $pos_x = ($width-$hole_w) / 2;
//        if( $height > $hole_h ) $pos_y = ($height-$hole_h) / 2;
//
//        $newImage = imagecreatetruecolor($hole_w,$hole_h);
//        if( $this->getImageType( $image ) == 3 ){
//            imagealphablending( $newImage, false );
//            imagesavealpha( $newImage, true );
//        }
//
//        $source = $this->createEmptyImage( $image );
//        imagecopyresampled($newImage, $source, -$pos_x, -$pos_y, 0, 0, $width, $height, $orig_width, $orig_height);
//
//        imagecopy($base_image, $newImage, $offset_x, $offset_y, 0, 0, $hole_w, $hole_h);
//        return $base_image;
//    }
}