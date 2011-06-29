<?php

// Class for handling data model objects associated with files
abstract class File extends Model {
	protected $doImageResizing = FALSE;
	protected $allowUpscaling = FALSE;
	var $resize_parameters = array();
	var $resized_images = array();

	function __construct($key=NULL) {
		parent::__construct($key);
		// If this model handles image resizing and no sizes are provided, get the default from $CFG
		if ($this->doImageResizing && (empty($this->resized_images) || !is_array($this->resized_images))) {
			global $CFG;
			$this->resize_parameters = $CFG['resized_images'];
		}
	}

	// Save the resized images as a serialized array (in case the $CFG values change later)
	function save() {
		if (isset($this->resized_images)) {
			$sizes = $this->resized_images;
			$this->resized_images = serialize($this->resized_images);
			$result = parent::save();
			$this->resized_images = $sizes;
			return $result;
		} else { return parent::save(); }
	}
	
	// Load and unserialize the resized images
	function load($key) {
		if ($result = parent::load($key)) {
			if (!$this->resized_images = unserialize($this->resized_images)) {
				unset($this->resized_images);
			}
		}
		return $result;
	}
	
	// Handles the saving of an uploaded file
	// Descendant classes should incorporate save() into this function as appropriate
	function saveUploadedFile($file=array()) {
		if (empty($file)) {
			return false;
		}
		$this->filename = $file['name'];
		$this->filesize = $file['size'];
		$this->mimetype = $file['type'];
		$this->getImageType($file['tmp_name']);
		if (!file_exists($this->getFilePath())) {
			mkdir($this->getFilePath(),0777,TRUE);
		}
		$result = copy($file['tmp_name'],$this->getFilePath().'/'.$this->getFilename());
		if ($result && $this->_imageType) {
			$this->saveResizedImages();	
		}
		return $result;
	}
	
	// Gets the image type (as mimetype), if any, of the provided file
	function getImageType($filepath=NULL) {
		$supported = array(IMAGETYPE_GIF => IMG_GIF,IMAGETYPE_JPEG => IMG_JPG,IMAGETYPE_PNG => IMG_PNG,IMAGETYPE_WBMP => IMG_WBMP);
		$mimetypes = array(IMAGETYPE_GIF => 'image/gif',IMAGETYPE_JPEG => 'image/jpeg',IMAGETYPE_PNG => 'image/png',IMAGETYPE_WBMP => 'image/wbmp');
		if (is_null($filepath)) {
			$filepath = $this->getFilePath().'/'.$this->getFilename();
		}
		if (isset($this->_imageType)) {
			return $this->_imageType;
		} elseif (function_exists('exif_imagetype')) {
			$imageType = exif_imagetype($filepath);
		} else {
			if ($imageType = (bool)@getimagesize($filepath)) {
				$imageType = $imageType[2];
			}
		}
		if (!($imageType && array_key_exists($imageType,$supported) && imagetypes() & $supported[$imageType])) {
			$imageType = FALSE;
		} elseif ($imageType && array_key_exists($imageType,$mimetypes)) {
			$this->mimetype = $imageType = $mimetypes[$imageType];
		} else { $imageType = FALSE; }
		$this->_imageType = $imageType;
		return $this->_imageType;
	}

	// Get the file-accessible path of the current file object
	function getFilePath() {
		global $CFG;
		return $CFG['fileroot'].'/'.$CFG['uploadpath'].'/uploads/'.$this->getPath();
	}
	
	// Get the web-accessible path of the current file object
	function getWWWPath() {
		global $CFG;
		return $CFG['wwwroot'].'/'.$CFG['uploadpath'].'/uploads/'.$this->getPath();
	}
	
	// Get the web-accessible filename (including path) of the current file
	function getWWWFilename($size='') {
		return $this->getWWWPath().'/'.$this->getFilename($size);
	}
	
	// Do image resizing and save the files
	protected function saveResizedImages() {
		$resized_images = array();
		$file = $this->getFilePath().'/'.$this->getFilename(1);
		if (!list($width,$height,$type) = getimagesize($file)) {
			return false;
		}
		$resized_images[] = array($width,$height,filesize($this->getFilePath().'/'.$this->getFilename(1)));
		$this->resized_images = $resized_images;
		if (!array($this->resize_parameters)) { return false; }
		switch ($type) {
			case IMAGETYPE_GIF: $orig = imagecreatefromgif($file); $savefunc = 'imagegif'; break;
			case IMAGETYPE_JPEG: $orig = imagecreatefromjpeg($file); $savefunc = 'imagejpeg'; break;
			case IMAGETYPE_PNG: $orig = imagecreatefrompng($file); $savefunc = 'imagepng'; break;
			case IMAGETYPE_WBMP: $orig = imagecreatefromwbmp($file); $savefunc = 'imagewbmp'; break;
			default: return false; break;
		}
		foreach($this->resize_parameters as $name => $size) {
			@list($resize_w,$resize_h,$resize_type) = $size;
			if ($resize_type=='crop') {
			// If we're cropping, we've got to figure out the crop start & length
				$ratio = $resize_w/$resize_h;
				if ($width >= $height*$ratio) {
					$x_length = round($height*$ratio);
					$x_start = round($width-$x_length)/2;
					$y_length = $height;
					$y_start = 0;
				} else {
					$y_length = round($width/$ratio);
					$y_start = round($height-$y_length)/2;
					$x_length = $width;
					$x_start = 0;					
				}
				$new_img = imagecreatetruecolor($resize_w,$resize_h);
				imagecopyresampled($new_img,$orig,0,0,$x_start,$y_start,$resize_w,$resize_h,$x_length,$y_length);
			} else {
			// If we're not cropping, constrain dimensions
				if ($resize_w >= $width && $resize_h >= $height && !$this->allowUpscaling) { continue; } // No sense in upsizing
				$ratio = $width/$height;
				if ($resize_w > $resize_h*$ratio) {
					$resize_w = round($resize_h*$ratio);
				} elseif ($resize_w < $resize_h*$ratio) {
					$resize_h = round($resize_w/$ratio);
				}
				$new_img = imagecreatetruecolor($resize_w,$resize_h);
				imagecopyresampled($new_img,$orig,0,0,0,0,$resize_w,$resize_h,$width,$height);
			}
			if ($savefunc=='imagejpeg') {
				imagejpeg($new_img,$this->getFilePath().'/'.$this->getFilename($name),80);
			} else {
				$savefunc($new_img,$this->getFilePath().'/'.$this->getFilename($name));
			}
			$resized_images[$name] = array($resize_w,$resize_h,filesize($this->getFilePath().'/'.$this->getFilename($name)));
			unset($new_img);
		}
		$this->resized_images = $resized_images;
		return true;
	}
	
	abstract function getPath();
	abstract function getFilename();
}