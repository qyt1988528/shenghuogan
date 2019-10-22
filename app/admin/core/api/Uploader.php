<?php
namespace Admin\Core\Api;

use MDK\Api;

class Uploader extends Api
{
	const MAX_FILE_SIZE = 2097152;
	private $path;
	private $file;
	private $mimeArray = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp',];
	private $fileInfo;
	private $error;
	public function __construct() {
		$this->_di = $this->getDI();
		$this->path = $this->_di->getDir()->public().'/upload/';
	}
	public function upload($file)
	{
		$this->file = $file;
		if (empty($this->file)) {
			$this->error = '文件上传失败，请再试一次';
			return $this;
		}
		if (!$this->checkFileSize() || !$this->checkFileMime()){
			return $this;
		}
		$this->moveFile();
		return $this;
	}

	private function checkFileSize()
	{
		if ($this->file->getSize() > self::MAX_FILE_SIZE) {
			$this->error = '文件超出大小限制，最大2M';
			return false;
		}
		return true;
	}

	private function checkFileMime()
	{
		if (!in_array($this->file->getType(), $this->mimeArray)) {
			$this->error = '请上传一个图片类型的文件';
			return false;
		}
		return true;
	}

	private function moveFile()
	{
		$fileId = time() + microtime(true);
		$fileName = 'ios_' . $fileId;
		$fullName = $fileName . '.' . $this->file->getExtension();
		$filePath = $this->path . $fullName;
		if (!is_dir($this->path))
			mkdir($this->path);
		if (file_exists($filePath)){
			unlink($filePath);
		}
		try {
			$this->file->moveTo($filePath);
			$this->error = $this->file->getError();
		} catch (\Exception $e) {
			return false;
		}
		$url =  '/image/' . $fullName;
		$this->fileInfo = ['name' => $this->file->getName(), 'url' => $url];
		if (empty($this->error)){
            return true;
        }
		return false;
	}

	public function deleteImage($name)
	{
		$request = $this->_di->get('request');
		if (empty($name)){
			return false;
		}
		$filePath =  $name;
		$success = false;
		if (file_exists($filePath)){
			$success = unlink($filePath);
		}
		return $success;
	}

	public function getThumb($path, $width, $height, $thumbPath = null){
		$image = getimagesize($path);
		switch ($image[2]){
			case 1:
				$imageObject = imagecreatefromgif($path);
				break;
			case 2:
				$imageObject = imagecreatefromjpeg($path);
				break;
			case 3:
				$imageObject = imagecreatefrompng($path);
				break;
			default:
				$imageObject = false;
				break;
		}
		if(!$imageObject){
			return false;
		}
		$img_Width = $image[0];
		$img_Height = $image[1];
		$thumb = imagecreatetruecolor($width,$height);
		$imageMaxWidth = 0;
		$startX = 0;
		$startY = 0;
		if($img_Width>$img_Height){
			$imageMaxWidth = $img_Height;
			$startX = ($img_Width - $img_Height)/2;
		}else{
			$imageMaxWidth = $imageMaxWidth;
			$startY = -($img_Width - $img_Height)/2;
		}
		imagecopyresampled($thumb, $imageObject, 0,0,$startX,$startY,$width,$height,$imageMaxWidth,$imageMaxWidth);
		if(empty($thumbPath)) {
			$thumbPath = str_replace('image_ios_','thumb_ios_',$thumbPath);
		}
		$filePath =  $thumbPath;
		imagejpeg($thumb, $filePath);
		return $thumbPath;
	}

	public function __get($name)
	{
		if (!empty($this->$name)){
			return $this->$name;
		}
		return empty($this->fileInfo[$name]) ? false : $this->fileInfo[$name];
	}
}