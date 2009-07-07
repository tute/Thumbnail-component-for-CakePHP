<?php
/*
* File: /app/controllers/components/thumbnail.php
*/

class ThumbnailComponent extends Object
{
	/*
	* Creates resized copies of input image
	* Example usage:
	* 	$this->Thumbnail->thumbnail($this->data['Model']['Thumbnail'], 573, 380, 80, 80, $folderName);
	*
	* Parameters:
	*	data: the image data array from the form
	*	maxw/maxh: maximum width/height for resizing thumbnails
	*	thumbscaleh: maximum height that you want your thumbnail to be resized to
	*	folderName: the name of the parent folder of the images
	*/
	function thumbnail($data, $maxw, $maxh, $thumbscalew, $thumbscaleh, $folderName) {
		if (strlen($data['name']) > 4) {
			$error = 0;
			$tempuploaddir  = 'img/temp'; // /temp/ folder (should delete image after upload)
			$homeuploaddir  = 'img/'.$folderName.'/home';
			$biguploaddir   = 'img/'.$folderName.'/big';
			$smalluploaddir = 'img/'.$folderName.'/small';

			// Make sure the required directories exist, and create them if necessary
			if (!is_dir($tempuploaddir)) mkdir($tempuploaddir, 0755, true);
			if (!is_dir($homeuploaddir)) mkdir($homeuploaddir, 0755, true);
			if (!is_dir($biguploaddir))  mkdir($biguploaddir, 0755, true);
			if (!is_dir($smalluploaddir)) mkdir($smalluploaddir, 0755, true);

			$filetype = $this->get_file_extension($data['name']);
			$filetype = strtolower($filetype);

			// Verify file extension. Get image size.
			if (($filetype != 'jpeg')  && ($filetype != 'jpg') && ($filetype != 'gif') && ($filetype != 'png')) {
				return;
			} else {
				$imgsize = GetImageSize($data['tmp_name']);
			}

			// Generate a unique name for the image
			$id_unic = $uuid = String::uuid();
			$filename = $id_unic;

			settype($filename, 'string');
			$filename .= '.';
			$filename .= $filetype;
			$tempfile  = $tempuploaddir . "/$filename";
			$homefile  = $homeuploaddir . "/$filename";
			$resizedfile = $biguploaddir . "/$filename";
			$croppedfile = $smalluploaddir . "/$filename";

			if (is_uploaded_file($data['tmp_name'])) {
				// Copy the image into the temporary directory
				if (!copy($data['tmp_name'], $tempfile)) {
					// echo 'Error Uploading File!';
					unset($filename);
					unlink($tempfile);
					exit();
				} else {
					/*
					 *	Generate home page version (center cropped)
					 */
					$this->resizeImage('resizeCrop', $tempuploaddir, $filename, $homeuploaddir, $filename, 886, 473, 85);
					/*
					 *	Generate the big version of the image with max of $imgscale in either directions
					 */
					$this->resizeImage('resize', $tempuploaddir, $filename, $biguploaddir, $filename, $maxw, $maxh, 85);
					/*
					 *	Generate the small thumbnail version of the image with scale of $thumbscalew and $thumbscaleh
					 */
					$this->resizeImage('resize', $tempuploaddir, $filename, $smalluploaddir, $filename, $thumbscalew, $thumbscaleh, 75);

					// Delete temporary image
					unlink($tempfile);
				}
			}

			// Image uploaded, return the file name
			return $filename;
		}
	}


	/*
	* Deletes the image and its associated thumbnail
	* Example usage:
	*	this->Thumbnail->delete_image('1210632285.jpg', $folderName);
	*
	* Parameters:
	*	filename: The file name of the image
	*	folderName: the name of the parent folder of the images.
	*/
	function delete_image($filename,$folderName) {
		if(is_file('img/'.$folderName.'/home/'.$filename))
			unlink('img/'.$folderName.'/home/'.$filename);
		if(is_file('img/'.$folderName.'/big/'.$filename))
			unlink('img/'.$folderName.'/big/'.$filename);
		if(is_file('img/'.$folderName.'/small/'.$filename))
			unlink('img/'.$folderName.'/small/'.$filename);
	}

	function get_file_extension($str) {
		$i = strrpos($str, '.');
		if (!$i) return '';
		$l = strlen($str) - $i;
		return substr($str, $i+1, $l);
    }

	/*
	* Creates resized image copy
	*
	* Parameters:
	*	cType: Conversion type {resize (default) | resizeCrop (square) | crop (from center)}
	*	id: image filename
	*	imgFolder: the folder where image is
	*	newName: include extension (if desired)
	*	newWidth: the max width or crop width
	*	newHeight: the max height or crop height
	*	quality: the quality of the image
	*	bgcolor: required for backward compatibility (?)
	*/
	function resizeImage($cType = 'resize', $srcfolder, $srcname, $dstfolder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75)
	{
		$srcimg = $srcfolder.DS.$srcname;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg);
		$ext = $this->image_type_to_extension($type);

		//check to make sure that the file is writeable, if so, create destination image (temp image)
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
			/* If temp file exists, delete it */
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
