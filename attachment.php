<?php
/*
* File: /app/controllers/components/attachment.php
*/

class AttachmentComponent extends Object
{
	/* Configuration options */
	var $config = array(
		'photos_dir' => 'photos',
		'allow_non_image_files' => true,
	);

	/*
	* Uploads file
	* Example usage:
	* 	$this->Attachment->upload($this->data['Model']['Attachment']);
	*
	* Parameters:
	*	data: the attachment data array from the form
	*/
	function upload($data) {
		if (strlen($data['name']) > 4) {
			$error = 0;
			$tmpuploaddir   = 'attachments/tmp'; // /tmp/ folder (should delete image after upload)
			$fileuploaddir  = 'attachments/files';

			// Make sure the required directories exist, and create them if necessary
			if (!is_dir($tmpuploaddir)) mkdir($tmpuploaddir, 0755, true);
			if (!is_dir($fileuploaddir)) mkdir($fileuploaddir, 0755, true);

			// Generate a unique name for the image
			$filetype = split('/', $data['type']);
			$filetype = $filetype[1];
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
					$this->thumbnail($tmpfile, 573, 380, 195, 195, $this->config['photos_dir']);
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
				return $filename;  // Image uploaded; return file name
			}
		}
	}

	/*
	* Creates resized copies of input image
	* Example usage:
	*	$this->Attachment->thumbnail($this->data['Model']['Attachment'], 573, 380, 80, 80, $folderName);
	*
	* Parameters:
	*	tmpfile: tmp image file name
	*	maxw/maxh: maximum width/height for resizing thumbnails
	*	thumbscaleh: maximum height that you want your thumbnail to be resized to
	*	folderName: the name of the parent folder of the images
	*/
	function thumbnail($tmpfile, $maxw, $maxh, $thumbscalew, $thumbscaleh, $folderName) {
		$biguploaddir   = 'attachments/'.$folderName.'/big';
		$smalluploaddir = 'attachments/'.$folderName.'/small';

		// Make sure the required directories exist, and create them if necessary
		if (!is_dir($biguploaddir))  mkdir($biguploaddir, 0755, true);
		if (!is_dir($smalluploaddir)) mkdir($smalluploaddir, 0755, true);

		$file_name = split('/', $tmpfile);
		$file_name = $file_name[2];

		$resizedfile = $biguploaddir . "/$file_name";
		$croppedfile = $smalluploaddir . "/$file_name";

		$imgsize = GetImageSize($tmpfile);
		/*
		 *	Generate the big version of the image with max of $imgscale in either directions
		 */
		$this->resizeImage('resize', $tmpfile, $biguploaddir, $file_name, $maxw, $maxh, 85);
		/*
		 *	Generate the small thumbnail version of the image with scale of $thumbscalew and $thumbscaleh
		 */
		$this->resizeImage('resizeCrop', $tmpfile, $smalluploaddir, $file_name, $thumbscalew, $thumbscaleh, 75);

		// Delete temporary image
		unlink($tmpfile);

		// Image thumbnailed
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
		if(is_file('attachments/'.$this->config['photos_dir'].'/big/'.$filename))
			unlink('attachments/'.$this->config['photos_dir'].'/big/'.$filename);
		if(is_file('attachments/'.$this->config['photos_dir'].'/small/'.$filename))
			unlink('attachments/'.$this->config['photos_dir'].'/small/'.$filename);
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
	function resizeImage($cType = 'resize', $tmpfile, $dstfolder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75)
	{
		$srcimg = $tmpfile;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg);
		$ext = $this->image_type_to_extension($type);

		// If file is writeable, create destination (tmp) image
		if (is_writeable($dstfolder)) {
			$dstimg = $dstfolder.DS.$dstname;
		} else {
			// if dirFolder not writeable, let developer know
			debug("You must allow proper permissions for image processing. And the folder has to be writable.");
			debug("Run \"chmod 777 on '$dstfolder' folder\"");
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
					imagepng($newImage, $dstimg, $quality);
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
