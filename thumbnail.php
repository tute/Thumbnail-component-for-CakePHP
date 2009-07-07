<?php
/*
* File: /app/controllers/components/thumbnail.php
*
* Uploads an image, its thumbnail and a zoom cropped image to
* $folderName/big, $folderName/small and $folderName/home respectively.
*
*
* USAGE (CONTROLLER):
*
* Add the component to your components array:
*	var $components = array('Thumbnail');
*
* In your controller action (returns the file name of the result image):
*	$image_path = $this->Thumbnail->thumbnail($this->data,'name1', 573,380,80,80, 'sets');
*
*
* USAGE (VIEW):
*
*	<?= $html->image($folderName.'/big/'.$entries['Entry']['image_path']);
* where $entries['Entry']['image_path'] is the stored file name
*
*
* PARAMETERS:
*	$data: the image data array from the form
*	$maxw: the maximum width that you want your picture to be resized to
*	$maxh: the maximum width that you want your picture to be resized to
*	$thumbscalew: the maximum width hat you want your thumbnail to be resized to
*	$thumbscaleh: the maximum height that you want your thumbnail to be resized to
*	$folderName: the name of the parent folder of the images. The images will be stored to /webroot/img/$folderName/big/ and  /webroot/img/$folderName/small/
*
*/

class ThumbnailComponent extends Object
{
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

			$filetype = $this->getFileExtension($data['name']);
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
				if (!copy($data['tmp_name'], '$tempfile')) {
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
					$this->resizeImage('resizeCrop', $tempuploaddir, $filename, $smalluploaddir, $filename, $thumbscalew, $thumbscaleh, 75);

					// Delete temporary image
					unlink($tempfile);
				}
			}

			// Image uploaded, return the file name
			return $filename;   
		}
	}

	/*
	*	Deletes the image and its associated thumbnail
	*	Example in controller action:	$this->Image->delete_image("1210632285.jpg","sets");
	*
	*	Parameters:
	*	$filename: The file name of the image
	*	$folderName: the name of the parent folder of the images. The images will be stored to /webroot/img/$folderName/big/ and  /webroot/img/$folderName/small/
	*/
	function delete_image($filename,$folderName) {
		if(is_file("img/".$folderName."/home/".$filename))
			unlink("img/".$folderName."/home/".$filename);
		if(is_file("img/".$folderName."/big/".$filename))
			unlink("img/".$folderName."/big/".$filename);
		if(is_file("img/".$folderName."/small/".$filename))
			unlink("img/".$folderName."/small/".$filename);
	}

    function getFileExtension($str) {

        $i = strrpos($str,".");
        if (!$i) { return ""; }
        $l = strlen($str) - $i;
        $ext = substr($str,$i+1,$l);
        return $ext;
    }

	/*
	 * @param $cType - the conversion type: resize (default), resizeCrop (square), crop (from center) 
	 * @param $id - image filename
	 * @param $imgFolder  - the folder where the image is
	 * @param $newName - include extension (if desired)
	 * @param $newWidth - the  max width or crop width
	 * @param $newHeight - the max height or crop height
	 * @param $quality - the quality of the image
	 * @param $bgcolor - this was from a previous option that was removed, but required for backward compatibility
	 */
	function resizeImage($cType = 'resize', $srcfolder, $srcname, $dstfolder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75)
	{
		$srcimg = $srcfolder.DS.$srcname;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg); 
		$ext = $this->image_type_to_extension($type);

		//check to make sure that the file is writeable, if so, create destination image (temp image)
		if (is_writeable($dstfolder))
		{
			$dstimg = $dstfolder.DS.$dstname;
		}
		else
		{
			//if not let developer know
			debug("You must allow proper permissions for image processing. And the folder has to be writable.");
			debug("Run \"chmod 777 on '$dstfolder' folder\"");
			exit();
		}

		//check to make sure that something is requested, otherwise there is nothing to resize.
		//although, could create option for quality only
		if ($newWidth OR $newHeight)
		{
			/*
			 * check to make sure temp file doesn't exist from a mistake or system hang up.
			 * If so delete.
			 */
			if(file_exists($dstimg))
			{
				unlink($dstimg);
			}
			else
			{
				switch ($cType){
					default:
					case 'resize':
						# Maintains the aspect ration of the image and makes sure that it fits
						# within the maxW(newWidth) and maxH(newHeight) (thus some side will be smaller)
						$widthScale = 2;
						$heightScale = 2;

						// Check to see that we are not over resizing, otherwise, set the new scale
						if($newWidth) {
							if($newWidth > $oldWidth) $newWidth = $oldWidth;
							$widthScale = 	$newWidth / $oldWidth;
						}
						if($newHeight) {
							if($newHeight > $oldHeight) $newHeight = $oldHeight;
							$heightScale = $newHeight / $oldHeight;
						}
						//debug("W: $widthScale  H: $heightScale<br>");
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
						// -- a straight centered crop
						$startY = ($oldHeight - $newHeight)/2;
						$startX = ($oldWidth - $newWidth)/2;
						$oldHeight = $newHeight;
						$applyHeight = $newHeight;
						$oldWidth = $newWidth; 
						$applyWidth = $newWidth;
						break;
				}

				switch($ext)
				{
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

				//create new image
				$newImage = imagecreatetruecolor($applyWidth, $applyHeight);

				//put old image on top of new image
				imagecopyresampled($newImage, $oldImage, 0,0 , $startX, $startY, $applyWidth, $applyHeight, $oldWidth, $oldHeight);

					switch($ext)
					{
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

	function image_type_to_extension($imagetype)
	{
	if(empty($imagetype)) return false;
		switch($imagetype)
		{
			case IMAGETYPE_GIF    : return 'gif';
			case IMAGETYPE_JPEG    : return 'jpg';
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
			case IMAGETYPE_WBMP    : return 'wbmp';
			case IMAGETYPE_XBM    : return 'xbm';
			default                : return false;
		}
	}
	} 
?>
