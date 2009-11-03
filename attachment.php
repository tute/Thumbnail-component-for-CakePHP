<?php
/*
* File: /app/controllers/components/attachment.php
*/

class AttachmentComponent extends Object
{
	/* Configuration options */
	var $config = array(
		'photos_dir' => 'photos',
		'database'   => false,
		'allow_non_image_files' => true,
		'images_size' => array(
			'big'    => array(640, 480, false),
			'med'    => array(263, 263, true),
			'small'  => array( 90,  90, true)
		)
	);

	/*
	* Uploads file to either database or file system, according to $config.
	* Example usage:
	* 	$this->Attachment->upload($this->data['Model']['Attachment']);
	*
	* Parameters:
	*	data: the file input array
	*/
	function upload($data) {
		if ($data['size'] == 0) {
			return false;
		}
		if ($this->config['database'] == false) {
			return $this->upload_FS($data);
		} else {
			return $this->upload_DB($data);
		}
	}

	function upload_DB($data) {
		$fp = fopen($data['tmp_name'], 'r');
		$content = fread($fp, filesize($data['tmp_name']));
		fclose($fp);
		return addslashes($content);
	}

	function upload_FS($data) {
		$error = 0;
		$tmpuploaddir   = 'attachments/tmp'; // /tmp/ folder (should delete image after upload)
		$fileuploaddir  = 'attachments/files';

		// Make sure the required directories exist, and create them if necessary
		if (!is_dir($tmpuploaddir)) mkdir($tmpuploaddir, 0755, true);
		if (!is_dir($fileuploaddir)) mkdir($fileuploaddir, 0755, true);

		// Generate a unique name for the image
		$filetype = end(split('\.', $data['name']));
		$filename = String::uuid();
		settype($filename, 'string');
		$filename .= '.' . $filetype;
		$tmpfile   = $tmpuploaddir . "/$filename";
		$filefile  = $fileuploaddir . "/$filename";

		// Copy file in temporary directory
		if (is_uploaded_file($data['tmp_name'])) {
			// If it's image, get image size, make thumbnails.
			if ($this->is_image($filetype)) {
				if (!copy($data['tmp_name'], $tmpfile)) {
					// Error uploading file
					unset($filename);
					unlink($tmpfile);
					exit();
				}

				/* Create new image for each thumbnail_size */
				foreach ($this->config['images_size'] as $folder_size => $opts) {
					$this->thumbnail($tmpfile, $folder_size, $opts[0], $opts[1], $opts[2]);
				}
				/* Delete temporary image */
				unlink($tmpfile);
			} else {
				if ($this->config['allow_non_image_files'] != true) {
					echo 'File type not permitted.';
					exit();
				}
				if (!copy($data['tmp_name'], $filefile)) {
					// Error uploading file
					unset($filename);
					exit();
				}
			}
			/* File uploaded; return file name */
			return $filename;
		}
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
		$upload_dir = 'attachments/' . $this->config['photos_dir'] . '/' . $upload_dir;
		if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

		$file_name = split('/', $tmpfile);
		$file_name = $file_name[2];
		$resizedfile = $upload_dir . '/' . $file_name[2];

		if (!$crop)
			/* Only resize */
			$this->resizeImage('resize', $tmpfile, $upload_dir, $file_name, $maxw, $maxh, 85);
		else
			/* Resize and crop */
			$this->resizeImage('resizeCrop', $tmpfile, $upload_dir, $file_name, $maxw, $maxh, 75);
	}


	/*
	* Deletes file, or image and associated thumbnail
	* Example usage:
	*	this->Attachment->delete_files('1210632285.jpg');
	*
	* Parameters:
	*	filename: The file name of the image
	*/
	function delete_files($filename) {
		if(is_file('attachments/files/'.$filename))
			unlink('attachments/files/'.$filename);

		foreach ($this->config['images_size'] as $size => $opts) {
			$photo = 'attachments/' . $this->config['photos_dir'] . "/$size/$filename";
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
	function resizeImage($cType = 'resize', $tmpfile, $dstfolder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75) {
		$srcimg = $tmpfile;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg);
		$ext = $this->image_type_to_extension($type);

		// If file is writeable, create destination (tmp) image
		if (is_writeable($dstfolder)) {
			$dstimg = $dstfolder.DS.$dstname;
		} else {
			// if dirFolder not writeable, let developer know
			debug("You must allow proper permissions for image processing. And the folder has to be writable.");
			debug("Run 'chmod 755 $dstfolder', and make sure the web server is it's owner.");
			exit();
		}

		// Check if something is requested, otherwise do not resize
		if ($newWidth or $newHeight) {
			/* If tmp file exists, delete it */
			if(file_exists($dstimg)) {
				unlink($dstimg);
			} else {
				switch ($cType) {
				default:
				case 'resize':
					// Maintains the aspect ratio of the image and makes sure
					// that it fits within the maxW and maxH
					$widthScale = 2;
					$heightScale = 2;

					// Check to see over-resizing, or set new scale
					if($newWidth) {
						if($newWidth > $oldWidth) $newWidth = $oldWidth;
						$widthScale = 	$newWidth / $oldWidth;
					}
					if($newHeight) {
						if($newHeight > $oldHeight) $newHeight = $oldHeight;
						$heightScale = $newHeight / $oldHeight;
					}
					if($widthScale < $heightScale) {
						$maxWidth = $newWidth;
						$maxHeight = false;
					} elseif ($widthScale > $heightScale ) {
						$maxHeight = $newHeight;
						$maxWidth = false;
					} else {
						$maxHeight = $newHeight;
						$maxWidth = $newWidth;
					}

					if($maxWidth > $maxHeight){
						$applyWidth = $maxWidth;
						$applyHeight = ($oldHeight*$applyWidth)/$oldWidth;
					} elseif ($maxHeight > $maxWidth) {
						$applyHeight = $maxHeight;
						$applyWidth = ($applyHeight*$oldWidth)/$oldHeight;
					} else {
						$applyWidth = $maxWidth;
						$applyHeight = $maxHeight;
					}
					$startX = 0;
					$startY = 0;
					break;

				case 'resizeCrop':
					// Check to see that we are not over resizing, otherwise, set the new scale
					// -- resize to max, then crop to center
					if($newWidth > $oldWidth) $newWidth = $oldWidth;
						$ratioX = $newWidth / $oldWidth;

					if($newHeight > $oldHeight) $newHeight = $oldHeight;
						$ratioY = $newHeight / $oldHeight;

					if ($ratioX < $ratioY) {
						$startX = round(($oldWidth - ($newWidth / $ratioY))/2);
						$startY = 0;
						$oldWidth = round($newWidth / $ratioY);
						$oldHeight = $oldHeight;
					} else {
						$startX = 0;
						$startY = round(($oldHeight - ($newHeight / $ratioX))/2);
						$oldWidth = $oldWidth;
						$oldHeight = round($newHeight / $ratioX);
					}
					$applyWidth = $newWidth;
					$applyHeight = $newHeight;
					break;

				case 'crop':
					// straight centered crop
					$startY = ($oldHeight - $newHeight)/2;
					$startX = ($oldWidth - $newWidth)/2;
					$oldHeight = $newHeight;
					$applyHeight = $newHeight;
					$oldWidth = $newWidth;
					$applyWidth = $newWidth;
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
					//image type is not a possible option
					return false;
					break;
				}

				// Create new image
				$newImage = imagecreatetruecolor($applyWidth, $applyHeight);
				// Put old image on top of new image
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

		} else {
			return false;
		}
	}


	function is_image($filetype) {
		$filetype = strtolower($filetype);
		return (($filetype == 'jpeg')  or ($filetype == 'jpg') or ($filetype == 'gif') or ($filetype == 'png'));
	}


	function image_type_to_extension($imagetype) {
		if(empty($imagetype)) return false;
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
