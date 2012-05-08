<?php

// Class for handling data model objects associated with files
abstract class File extends Model {
	protected $_doImageResizing = FALSE;
	protected $_allowUpscaling = FALSE;
	protected $_resizeParameters = array();

	function __construct($key=NULL) {
		parent::__construct($key);
		// If this model handles image resizing and no sizes are provided, get the default from $CFG
		if ($this->_doImageResizing && empty($this->_resizeParameters)) {
			$CFG = Load::Config();
			$this->_resizeParameters = $CFG['resized_images'];
		}
	}

	// Handles the saving of an uploaded file
	// Descendant classes should incorporate naming file and saving model
	function parseUpload($file=array()) {
		if (empty($file)) { return FALSE; }
		if (!$path = $this->getFilePath()) { return FALSE; }
		if (!file_exists(dirname($path))) {
			if (!mkdir(dirname($path),0777,TRUE)) { return FALSE; }
		}
		if (!copy($file['tmp_name'],$path)) { return FALSE; }
		$this->filesize = $file['size'];
		$this->mimetype = $file['type'];
		if ($this->getImageType($path)) {
			$this->saveResizedImages();
		}
		return TRUE;
	}
	
	// Gets the image type (as mimetype), if any, of the provided file
	function getImageType($file=NULL) {
		$supported = array(
			IMAGETYPE_GIF  => IMG_GIF,
			IMAGETYPE_JPEG => IMG_JPG,
			IMAGETYPE_PNG  => IMG_PNG,
			IMAGETYPE_WBMP => IMG_WBMP,
		);
		$mimetypes = array(
			IMAGETYPE_GIF  => 'image/gif',
			IMAGETYPE_JPEG => 'image/jpeg',
			IMAGETYPE_PNG  => 'image/png',
			IMAGETYPE_WBMP => 'image/wbmp',
		);
		// If no filename provided, use model
		if (empty($file)) {
			if (isset($this->_imageType)) {
				return $this->_imageType;
			}
			$filepath = $this->getFilePath();
		// If provided, use given filename
		} else {
			$filepath = $file;
		}

		// If the file doesn't exist, nothing to do here
		if (empty($filepath) && !file_exists($filepath)) {
			return FALSE;
		}

		// Try to get the image type, using exif or GD
		if (FALSE && function_exists('exif_imagetype')) {
			$imageType = exif_imagetype($filepath);
		} elseif ($imageType = getimagesize($filepath)) { // catch needed?
			$imageType = $imageType[2];
		} else {
			$imageType = FALSE;
		}

		// Make sure image is supported by GD library
		if (!($imageType && array_key_exists($imageType,$supported)
			&& imagetypes() & $supported[$imageType])
		) {
			$imageType = FALSE;
		// Also make sure image has/is of a valid mimetype
		} elseif ($imageType && array_key_exists($imageType,$mimetypes)) {
			$imageType = $mimetypes[$imageType];
		} else {
			$imageType = FALSE;
		}
		// If we're looking at this model, set model properties
		if (is_null($file)) {
			$this->mimetype = $imageType;
		}
		return $imageType;
	}

	// Do image resizing and save the files
	protected function saveResizedImages() {
		// Get image info of file (width, height, type)
		$file = $this->getFilePath();
		if (!list($width,$height,$type) = getimagesize($file)) { return FALSE; }

		// Begin compiling an array of image sizes
		$resized_images = array();
		$resized_images['original'] = array($width,$height,filesize($file));
		$this->resized_images = $resized_images;

		// If no resizing is to occur, just return
		if (!array($this->_resizeParameters)) { return false; }

		// Branch GD functionality based on image type
		switch ($type) {
			case IMAGETYPE_GIF:
				$orig = imagecreatefromgif($file);
				$savefunc = 'imagegif'; break;
			case IMAGETYPE_JPEG:
				$orig = imagecreatefromjpeg($file);
				$savefunc = 'imagejpeg'; break;
			case IMAGETYPE_PNG:
				$orig = imagecreatefrompng($file);
				$savefunc = 'imagepng'; break;
			case IMAGETYPE_WBMP:
				$orig = imagecreatefromwbmp($file);
				$savefunc = 'imagewbmp'; break;
			default:
				return false; break;
		}

		// Begin resizing
		foreach($this->_resizeParameters as $name => $size) {
			if ($name=='original') { continue; } // Don't overwrite original
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
				imagecopyresampled($new_img,$orig,0,0,$x_start,$y_start,
					$resize_w,$resize_h,$x_length,$y_length);
			} else {
			// If we're not cropping, constrain dimensions
				if ($resize_w >= $width && $resize_h >= $height
					&& !$this->_allowUpscaling
				) {
					continue; // No sense in upsizing
				}
				$ratio = $width/$height;
				if ($resize_w > $resize_h*$ratio) {
					$resize_w = round($resize_h*$ratio);
				} elseif ($resize_w < $resize_h*$ratio) {
					$resize_h = round($resize_w/$ratio);
				}
				$new_img = imagecreatetruecolor($resize_w,$resize_h);
				imagecopyresampled($new_img,$orig,0,0,0,0,
					$resize_w,$resize_h,$width,$height);
			}

			// Save the image size
			if ($savefunc=='imagejpeg') {
				imagejpeg($new_img,$this->getFilePath($name),80);
			} else {
				$savefunc($new_img,$this->getFilePath($name));
			}
			// Add this size to the array
			$resized_images[$name] = array(
				$resize_w,
				$resize_h,
				filesize($this->getFilePath($name))
			);
			unset($new_img); // Free up resources
		}
		// Set the resized images and continue
		$this->resized_images = $resized_images;
		return TRUE;
	}

	protected function getFilename($size=NULL) {
		if (empty($this->filename)) { return false; }
		if (!is_null($size)
			&& preg_match('#(.+?)(\.[a-z0-9]*)$#',$this->filename,$parts)
		) {
			list(,$name,$ext) = $parts;
			return $name.(is_string($size) ? "-$size" : '')."$ext";
		} else {
			return $this->filename;
		}		
	}

	abstract function getFilePath($size=NULL);
	abstract function getURL($size=NULL);
}