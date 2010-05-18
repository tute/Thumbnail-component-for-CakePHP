<?php
/*
* File: /app/controllers/components/attachment.php
*   A file uploader and thumbnailer component for CakePHP
*/
class AttachmentComponent extends Object
{
	/* Configuration options */
	var $config = array(
		'files_dir' => 'photos',
		'save_in_db' => false,
		'allow_non_image_files' => true,
		'images_size' => array(
			/* You may define as many options as you like */
			'big'    => array(640, 480, false),
			'med'    => array(263, 263, true),
			'small'  => array( 90,  90, true)
		)
	);

	/*
	* Initialization method. You may override configuration options while
	* including it:
	*/
	function initialize(&$controller, $config) {
		$this->controller = $controller;
		$this->config = array_merge(
			array('default_col' => strtolower($controller->modelClass)), /* columns prefix */
			$this->config, /* default general configuration */
			$config        /* overriden configurations */
		);
	}

	/*
	* Uploads file to either database or file system, according to $config.
	* Example usage:
	* 	$this->Attachment->upload($this->data['Model']['Attachment']);
	*
	* Parameters:
	*	data: the file input array
	*/
	function upload(&$data) {
		if ($data[$this->config['default_col']]['size'] == 0) {
			return false;
		}
		if ($this->config['save_in_db']) {
			return $this->upload_DB($data);
		} else {
			return $this->upload_FS($data);
		}
	}

	function upload_DB(&$data) {
		/*
		$fp = fopen($data['tmp_name'], 'r');
		$content = fread($fp, filesize($data['tmp_name']));
		fclose($fp);
		return addslashes($content);
		*/
		return false;
	}

	function upload_FS(&$data) {
		$def_col = $this->config['default_col'];
		$error = 0;
		$tmpuploaddir  = WWW_ROOT.'attachments'.DS.'tmp'; // /tmp/ folder (should delete image after upload)
		$fileuploaddir = WWW_ROOT.'attachments'.DS.'files';

		// Make sure the required directories exist, and create them if necessary
		if (!is_dir($tmpuploaddir)) mkdir($tmpuploaddir, 0755, true);
		if (!is_dir($fileuploaddir)) mkdir($fileuploaddir, 0755, true);

		/* Generate a unique name for the file */
		$filetype = end(split('\.', $data[$def_col]['name']));
		$filename = String::uuid();
		settype($filename, 'string');
		$filename .= '.' . $filetype;
		$tmpfile  = $tmpuploaddir.DS.$filename;
		$filefile = $fileuploaddir.DS.$filename;

		/* Security check */
		if (!is_uploaded_file($data[$def_col]['tmp_name'])) {
			exit('Error uploading file (sure it was a POST request?).');
		}

		/* If it's image get image size and make thumbnail copies. */
		if ($this->is_image($filetype)) {
			$this->copy_or_raise_error($data[$def_col]['tmp_name'], $tmpfile);
			/* Create each thumbnail_size */
			foreach ($this->config['images_size'] as $dir => $opts) {
				$this->thumbnail($tmpfile,$dir,$opts[0],$opts[1],$opts[2]);
			}
			unlink($tmpfile);
		} else {
			if (!$this->config['allow_non_image_files']) {
				exit('File type not allowed.');
			}
			$this->copy_or_raise_error($data[$def_col]['tmp_name'], $filefile);
		}

		/* File uploaded, return modified data array */
		$res[$def_col.'_file_path'] = $filename;
		$res[$def_col.'_file_name'] = $data[$def_col]['name'];
		$res[$def_col.'_file_size'] = $data[$def_col]['size'];
		$res[$def_col.'_content_type'] = $data[$def_col]['type'];
		unset($data[$def_col]); /* delete $_FILES indirection */
		$data = array_merge($data, $res); /* add default fields */

		return true;
	}

	/*
	* Creates resized copies of input image
	* Example usage:
	*	$this->Attachment->thumbnail($this->data['Model']['Attachment'], $upload_dir, 640, 480, false);
	*
	* Parameters:
	*	tmpfile: the image data array from the form
	*	upload_dir: the name of the parent folder of the images
	*	maxw/maxh: maximum width/height for resizing thumbnails
	*	crop: indicates if image must be cropped or not
	*/
	function thumbnail($tmpfile, $upload_dir, $maxw, $maxh, $crop = false) {
		// Make sure the required directory exist; create it if necessary
		$upload_dir = WWW_ROOT.'attachments'.DS.$this->config['files_dir'].DS.$upload_dir;
		if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

		/* Directory Separator for windows users */
		$ds = (strcmp('\\', DS) == 0) ? '\\\\' : DS;
		$file_name = end(split($ds, $tmpfile));
		$action = ($crop ? 'resizeCrop' : 'resize');
		$this->resize_image($action, $tmpfile, $upload_dir, $file_name, $maxw, $maxh, 85);
	}


	/*
	* Deletes file, or image and associated thumbnail
	* Example usage:
	*	$this->Attachment->delete_files('file_name.jpg');
	*
	* Parameters:
	*	filename: The file name of the image
	*/
	function delete_files($filename) {
		if (is_file(WWW_ROOT.'attachments'.DS.'files'.DS.$filename)) {
			unlink(WWW_ROOT.'attachments'.DS.'files'.DS.$filename);
		}
		foreach ($this->config['images_size'] as $size => $opts) {
			$photo = WWW_ROOT.'attachments'.DS.$this->config['files_dir'].DS.$size.DS.$filename;
			if (is_file($photo)) unlink($photo);
		}
	}

	/*
	* Creates resized image copy
	*
	* Parameters:
	*	cType: Conversion type {resize (default) | resizeCrop (square) | crop (from center)}
	*	tmpfile: original (tmp) file name
	*	newName: include extension (if desired)
	*	newWidth: the max width or crop width
	*	newHeight: the max height or crop height
	*	quality: the quality of the image
	*/
	function resize_image($cType = 'resize', $tmpfile, $dstfolder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75) {
		$srcimg = $tmpfile;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg);
		$ext = $this->image_type_to_extension($type);

		// If file is writeable, create destination (tmp) image
		if (is_writeable($dstfolder)) {
			$dstimg = $dstfolder.DS.$dstname;
		} else {
			// if dirFolder not writeable, let developer know
			debug('You must allow proper permissions for image processing. And the folder has to be writable.');
			debug("Run 'chmod 755 $dstfolder', and make sure the web server is it's owner.");
			exit();
		}

		/* Check if something is requested, otherwise do not resize */
		if ($newWidth or $newHeight) {
			/* Delete tmp file if it exists */
			if (file_exists($dstimg)) {
				unlink($dstimg);
			} else {
				switch ($cType) {
				default:
				case 'resize':
					// Maintains the aspect ratio of the image and makes sure
					// that it fits within the maxW and maxH
					$widthScale  = 2;
					$heightScale = 2;

					/* Check if we're overresizing (or set new scale) */
					if ($newWidth) {
						if ($newWidth > $oldWidth) $newWidth = $oldWidth;
						$widthScale = $newWidth / $oldWidth;
					}
					if ($newHeight) {
						if ($newHeight > $oldHeight) $newHeight = $oldHeight;
						$heightScale = $newHeight / $oldHeight;
					}
					if ($widthScale < $heightScale) {
						$maxWidth  = $newWidth;
						$maxHeight = false;
					} elseif ($widthScale > $heightScale ) {
						$maxHeight = $newHeight;
						$maxWidth  = false;
					} else {
						$maxHeight = $newHeight;
						$maxWidth  = $newWidth;
					}

					if ($maxWidth > $maxHeight){
						$applyWidth  = $maxWidth;
						$applyHeight = ($oldHeight*$applyWidth)/$oldWidth;
					} elseif ($maxHeight > $maxWidth) {
						$applyHeight = $maxHeight;
						$applyWidth  = ($applyHeight*$oldWidth)/$oldHeight;
					} else {
						$applyWidth  = $maxWidth;
						$applyHeight = $maxHeight;
					}
					$startX = 0;
					$startY = 0;
					break;

				case 'resizeCrop':
					/* Check if we're overresizing (or set new scale) */
					/* resize to max, then crop to center */
					if ($newWidth > $oldWidth) $newWidth = $oldWidth;
						$ratioX = $newWidth / $oldWidth;

					if ($newHeight > $oldHeight) $newHeight = $oldHeight;
						$ratioY = $newHeight / $oldHeight;

					if ($ratioX < $ratioY) {
						$startX = round(($oldWidth - ($newWidth / $ratioY))/2);
						$startY = 0;
						$oldWidth  = round($newWidth / $ratioY);
						$oldHeight = $oldHeight;
					} else {
						$startX = 0;
						$startY = round(($oldHeight - ($newHeight / $ratioX))/2);
						$oldWidth  = $oldWidth;
						$oldHeight = round($newHeight / $ratioX);
					}
					$applyWidth  = $newWidth;
					$applyHeight = $newHeight;
					break;

				case 'crop':
					// straight centered crop
					$startY = ($oldHeight - $newHeight)/2;
					$startX = ($oldWidth - $newWidth)/2;
					$oldHeight   = $newHeight;
					$applyHeight = $newHeight;
					$oldWidth    = $newWidth;
					$applyWidth  = $newWidth;
					break;
				}

				switch($ext) {
				case 'gif' :
					$oldImage = imagecreatefromgif($srcimg);
					break;
				case 'png' :
					$oldImage = imagecreatefrompng($srcimg);
					break;
				case 'jpg' :
				case 'jpeg' :
					$oldImage = imagecreatefromjpeg($srcimg);
					break;
				default :
					// image type is not a possible option
					return false;
					break;
				}

				// Create new image
				$newImage = imagecreatetruecolor($applyWidth, $applyHeight);
				// Put old image on top of new image
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
				imagecopyresampled($newImage, $oldImage, 0, 0, $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);

				switch($ext) {
				case 'gif' :
					imagegif($newImage, $dstimg, $quality);
					break;
				case 'png' :
					imagepng($newImage, $dstimg, round($quality/10));
					break;
				case 'jpg' :
				case 'jpeg' :
					imagejpeg($newImage, $dstimg, $quality);
					break;
				default :
					return false;
					break;
				}

				imagedestroy($newImage);
				imagedestroy($oldImage);
				return true;
			}
		} else { /* Nothing requested */
			return false;
		}
	}

	function copy_or_raise_error($tmp_name, $filefile) {
		if (!copy($tmp_name, $filefile)) {
			unset($filename);
			exit('Error uploading file.'); /* Returns false */
		}
	}

	function is_image($file_type) {
		$image_types = array('jpeg', 'jpg', 'gif', 'png');
		return in_array(strtolower($file_type), $image_types);
	}

	function image_type_to_extension($imagetype) {
		if (empty($imagetype)) return false;
		switch($imagetype) {
			case IMAGETYPE_GIF    : return 'gif';
			case IMAGETYPE_JPEG   : return 'jpg';
			case IMAGETYPE_PNG    : return 'png';
			case IMAGETYPE_SWF    : return 'swf';
			case IMAGETYPE_PSD    : return 'psd';
			case IMAGETYPE_BMP    : return 'bmp';
			case IMAGETYPE_TIFF_II : return 'tiff';
			case IMAGETYPE_TIFF_MM : return 'tiff';
			case IMAGETYPE_JPC    : return 'jpc';
			case IMAGETYPE_JP2    : return 'jp2';
			case IMAGETYPE_JPX    : return 'jpf';
			case IMAGETYPE_JB2    : return 'jb2';
			case IMAGETYPE_SWC    : return 'swc';
			case IMAGETYPE_IFF    : return 'aiff';
			case IMAGETYPE_WBMP   : return 'wbmp';
			case IMAGETYPE_XBM    : return 'xbm';
			default               : return false;
		}
	}
}
?>
