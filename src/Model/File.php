<?php

namespace Core\Model;

use Core\Model\Utils\ImageUtils;
use Core\Model\Utils\StringUtils;
use Core\Utils\Config;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use GdImage;
use PDO;

/**
 * Class File
 * saves images on disk, database
 */
class File extends Model
{

    const int MAX_SIZE = 9000000;

    /**
     * @var int $folderID . Folder number where the file is
     */
    private int $folderID = 0;

    private string $absoluteFolder;

    private string $relativeFolder;

    private string $fileName = '';

    private string $defaultExtension;

    public function __construct(int $id = 0, string $defaultExtension = 'jpg')
    {
        parent::__construct();

        $this->defaultExtension = $defaultExtension;

        // init directory
        $config = Config::getInstance();

        $webFolderPrefix = Config::getWebFolderPrefix();
        $configDomains   = $config->get('domains', 'upload');
        if (is_array($configDomains) && count($configDomains)) {
            $domains = $configDomains;
            $folder  = $domains[ array_rand($domains) ];
            $url     = parse_url($config->getBaseDomain() . $webFolderPrefix . 'upload/');
            $host    = $url['host'];
            if (str_starts_with($host, 'pre.')) {
                $host = str_replace('pre.', '', $host);
            }
            $this->absoluteFolder = $url['scheme'] . '://' . $folder . '.' . $host . $url['path'];
        } else {
            $this->absoluteFolder = $config->getBaseDomain() . $webFolderPrefix . 'upload/';
        }
        $this->relativeFolder = 'upload/';

        //load current image
        $this->id = $id;
        if ($this->id) {
            $this->load();
        }
    }

    public function getRelativePath($suffix = ''): string
    {
        return $this->relativeFolder . $this->folderID . '/' . $this->getFileNameWidthSuffix($suffix);
    }

    public function getAbsolutePath($suffix = ''): ?string
    {
        $relativePath = $this->getRelativePath($suffix);
        if (file_exists($relativePath)) {
            return $this->absoluteFolder . $this->folderID . '/' . $this->getFileNameWidthSuffix($suffix);
        }
        return null;
    }

    private function getFileNameWidthSuffix($suffix = ''): string
    {
        $fileBasename  = pathinfo($this->fileName, PATHINFO_FILENAME);
        $fileExtension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        if (empty($fileExtension)) {
            $fileExtension  = $this->defaultExtension;
            $this->fileName .= '.' . $fileExtension;
        }

        $fileName = $fileBasename . '.' . $fileExtension;
        if ($suffix != '') {
            $fileName = $fileBasename . '-' . $suffix . '.' . $fileExtension;
        }
        return $fileName;
    }

    private function load(): void
    {
        $this->initFolder();

        $fileName = $this->getFileName();
        if ($fileName != "") {
            $this->fileName = $fileName;
        }
    }

    /**
     * we calculate to which subfolder the file is based on its id
     * every 20 files go to a different folder
     */
    private function initFolder(): void
    {
        $this->folderID = ceil($this->id / 20);
    }

    /**
     * get the file name from the database
     * @return string
     */
    private function getFileName(): string
    {
        if ($this->id != '') {
            $sql    = '
				SELECT file_name
				FROM appacman_file
				WHERE id_appacman_file = :id_appacman_file
			';
            $params = array(
                'id_appacman_file' => array('value' => $this->id, 'type' => PDO::PARAM_INT)
            );
            $img    = $this->mysql->query($sql, $params);
            if (count($img)) {
                return $img[0]['file_name'];
            }
        }
        return '';
    }

    /**
     * delete image form database (1,2) and disc (3)
     *
     * @param string $table
     * @param string $field
     * @param int    $itemID
     *
     * @return bool
     */
    public function delete(string $table, string $field, int $itemID): bool
    {
        $tableDelete = false;
        if ($this->mysql->fieldExists($table, $field)) {
            $tableDelete = $table;
        } else {
            if ($this->mysql->fieldExists($table . '_lang', $field)) {
                $tableDelete = $table . '_lang';
            }
        }

        if ($tableDelete !== false) {
            // 1. delete from table
            $sql    = '
                UPDATE ' . $tableDelete . '
                SET ' . $field . ' = "0"
                WHERE id_' . $tableDelete . ' = :id
            ';
            $params = array(
                'id' => array('value' => $itemID, 'type' => PDO::PARAM_INT)
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

    public function deleteFromFileTable(): void
    {
        $sql    = '
            DELETE FROM appacman_file
            WHERE id_appacman_file = :id
        ';
        $params = array(
            'id' => array('value' => $this->id, 'type' => PDO::PARAM_INT)
        );
        $this->mysql->query($sql, $params);
    }

    public function deleteFromDisk(): void
    {
        $filePath = $this->getRelativePath();
        if (file_exists($filePath) && !is_dir($filePath)) {
            unlink($filePath);
        }

        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        if ($fileName) {
            $files = scandir($this->relativeFolder . $this->folderID . '/');
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    if (str_starts_with($file, $fileName)) {
                        $suffixFilePath = $this->relativeFolder . $this->folderID . '/' . $file;
                        if (file_exists($suffixFilePath)) {
                            unlink($suffixFilePath);
                        }
                    }
                }
            }
        }
    }

    /**
     * save
     * saves image on disk and database
     *
     * @param array $file
     * @param ?int  $fieldID
     *
     * @return false|int
     */
    public function save(array $file, ?int $fieldID = null): false|int
    {
        if ($file['error'] == 0) {
            $this->prepareSave($file['name']);
            $path = $this->getRelativePath();

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $this->checkImageOrientation($path);
                $id = $this->saveToDatabase();
                if ($id) {
                    if ($fieldID != null) {
                        $resize = $this->getResize($fieldID);
                        if ($resize) {
                            $this->resize($resize);
                        }
                    }
                    return $id;
                }
            }
        }
        return false;
    }

    /**
     * Resize description of an image
     *
     * @param int $fieldID
     *
     * @return false|array
     */
    private function getResize(int $fieldID): false|array
    {
        $sql    = '
            SELECT afr.width, afr.height, afr.suffix
            FROM appacman_file_resize AS afr
            WHERE afr.id_appacman_field = :field_id
        ';
        $params = array(
            'field_id' => array('value' => $fieldID, 'type' => PDO::PARAM_INT)
        );
        $resize = $this->mysql->query($sql, $params);

        if (count($resize)) {
            return $resize;
        }
        return false;
    }

    /**
     * copy file to uploads
     *
     * @param string $fileName
     * @param string $origin
     *
     * @return false|int
     */
    public function copy(string $fileName, string $origin): false|int
    {
        $this->prepareSave($fileName);
        $path = $this->getRelativePath();

        if (copy($origin, $path)) {
            $this->checkImageOrientation($path);
            return $this->saveToDatabase();
        }
        return false;
    }

    public function download(string $url, string $fileName): int
    {
        $this->prepareSave($fileName);
        $this->defaultExtension = $this->getImageExtension($url . $fileName);
        $path                   = $this->getRelativePath();

        $data = get_headers($url . $fileName, true);
        $size = isset($data['Content-Length']) ? (int) $data['Content-Length'] : 0;
        if ($size > self::MAX_SIZE) {
            $percent = floor(100 * self::MAX_SIZE / $size) / 100;
            $error   = !$this->downloadResized($url . $fileName, $path, $percent);
        } else {
            $error = !copy($url . $fileName, $path);
        }
        if (!$error) {
            $this->checkImageOrientation($path);
            return $this->saveToDatabase();
        }
        return 0;
    }

    private function downloadResized($fileName, $path, $percent = 0.75): false|GdImage
    {
        list($width, $height) = getimagesize($fileName);
        $newWidth  = intval($width * $percent);
        $newHeight = intval($height * $percent);

        if ($newWidth > 2000) {
            $percent   = (2000 * 100 / $width) / 100;
            $newWidth  = intval($width * $percent);
            $newHeight = intval($height * $percent);
        }

        $image      = imagecreatetruecolor($newWidth, $newHeight);
        $emptyImage = $this->createEmptyImage($fileName, $fileName);
        imagecopyresampled($image, $emptyImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        return match ($this->getImageExtension($fileName)) {
            ImageUtils::IMG_JPG => imagejpeg($emptyImage, $path),
            ImageUtils::IMG_GIF => imagegif($emptyImage, $path),
            ImageUtils::IMG_PNG => imagepng($emptyImage, $path),
            ImageUtils::IMG_WEBP => imagewebp($emptyImage, $path),
            default => false,
        };
    }

    public function saveQr(string $text, string $qrName, int $size = 1000): int
    {
        $this->prepareSave($qrName);
        $path = $this->getRelativePath();

        $renderer = new ImageRenderer(
            new RendererStyle($size), new ImagickImageBackEnd()
        );
        $writer   = new Writer($renderer);
        $writer->writeFile($text, $path);
        return $this->saveToDatabase();
    }

    public function prepareSave($fileName, $withID = true): void
    {
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

    public function saveToDatabase(): int
    {
        $folder    = $this->relativeFolder . $this->folderID . '/';
        $extension = $this->getImageExtension($folder . $this->fileName);
        $converter = new ImageUtils();
        $fileName  = $converter->generateWebpImage($folder, $this->fileName);
        if ($extension != ImageUtils::IMG_WEBP && $fileName) {
            unlink($folder . $this->fileName);
            $this->fileName = $fileName;
        }
        $sql    = '
            INSERT INTO appacman_file
            SET id_appacman_file = :id, file_name = :file_name
        ';
        $params = array(
            'id'        => array('value' => $this->id, 'type' => PDO::PARAM_INT),
            'file_name' => array('value' => $this->fileName, 'type' => PDO::PARAM_STR)
        );
        $this->mysql->query($sql, $params);
        if ($this->mysql->rowCount() > 0) {
            return $this->id;
        } else {
            $this->deleteFromDisk();
        }
        return 0;
    }

    /**
     * createFolder
     *
     * @param string $folder
     */
    private function createFolder(string $folder): void
    {
        if (!(file_exists($folder) && is_dir($folder))) {
            mkdir($folder);
        }
    }

    /**
     * rotates the image if needed (only jpg images)
     *
     * @param string $path
     *
     * @return bool
     */
    public function checkImageOrientation(string $path): bool
    {
        $ext = $this->getImageExtension($this->getRelativePath());
        if ($ext == ImageUtils::IMG_JPG) {
            $image = imagecreatefromjpeg($path);
            $exif  = @exif_read_data($path);
            if (empty($exif['Orientation'])) {
                return false;
            }

            $image = match ($exif['Orientation']) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
            imagejpeg($image, $path);
            return true;
        }
        return false;
    }

    public function resize(array $dimensions): void
    {
        foreach ($dimensions as $dimension) {
            $originPath      = $this->getRelativePath();
            $destinationPath = $this->getRelativePath($dimension['suffix']);
            $emptyImage      = $this->resizedImage($originPath, $dimension['width'], $dimension['height']);

            if ($emptyImage) {
                $error = match ($this->getImageExtension($originPath)) {
                    ImageUtils::IMG_JPG => imagejpeg($emptyImage, $destinationPath),
                    ImageUtils::IMG_GIF => imagegif($emptyImage, $destinationPath),
                    ImageUtils::IMG_PNG => imagepng($emptyImage, $destinationPath),
                    ImageUtils::IMG_WEBP => imagewebp($emptyImage, $destinationPath),
                    default => true,
                };
            } else {
                $error = true;
            }

            if (!$error) {
                chmod($destinationPath, 0777);
            }
        }
    }

    /**
     * create image with new dimensions keeping aspect ratio
     *
     * @param string $image
     * @param int    $max_width
     * @param int    $max_height
     *
     * @return resource
     */
    private function resizedImage(string $image, int $max_width, int $max_height): GdImage|false
    {
        $size = $this->getSize();
        if ($size) {
            $orig_width  = $size['width'];
            $orig_height = $size['height'];
            $width       = $orig_width;
            $height      = $orig_height;

            # height
            if ($height > $max_height) {
                $width  = ($max_height / $height) * $width;
                $height = $max_height;
            }

            # width
            if ($width > $max_width) {
                $height = ($max_width / $width) * $height;
                $width  = $max_width;
            }

            $source = $this->createEmptyImage($image);
            if ($source) {
                return imagescale($source, intval($width), intval($height));
            }
        }
        return false;
    }

    public function getSize(string $suffix = ''): array
    {
        $relativePath = $this->getRelativePath($suffix);
        if (file_exists($relativePath)) {
            $size = getimagesize($relativePath);
            if ($size !== false) {
                return array(
                    'width'  => $size[0],
                    'height' => $size[1]
                );
            }
        }
        return array();
    }

    private function getImageExtension(string $path): int
    {
        $extension = exif_imagetype($path);
        return match ($extension) {
            IMAGETYPE_JPEG => ImageUtils::IMG_JPG,
            IMAGETYPE_PNG => ImageUtils::IMG_PNG,
            IMAGETYPE_GIF => ImageUtils::IMG_GIF,
            IMAGETYPE_WEBP => ImageUtils::IMG_WEBP,
            default => false,
        };
    }

    /**
     * createEmptyImage
     * creates empty image depending on image extension
     *
     * @param string      $image
     * @param string|null $path
     *
     * @return resource
     */
    private function createEmptyImage(string $image, ?string $path = null): string
    {
        if ($path == null) {
            $path = $this->getRelativePath();
        }
        $image_type = $this->getImageExtension($path);

        $src = null;
        switch ($image_type) {
            case ImageUtils::IMG_JPG:
                $src = imagecreatefromjpeg($image);
                break;
            case ImageUtils::IMG_GIF:
                $src = imagecreatefromgif($image);
                break;
            case ImageUtils::IMG_PNG:
                $src = imagecreatefrompng($image);
                break;
            case ImageUtils::IMG_WEBP:
                $src = imagecreatefromwebp($image);
                break;
        }
        return $src;
    }

}
