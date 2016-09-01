<?php
/*
* File: /app/controllers/components/AttachmentComponent.php
* A file uploader and image crop/thumbnailer component for CakePHP
*
* @link                https://github.com/tute/Thumbnail-component-for-CakePHP
* @author              TuteC (Eugenio Costa)
* @Modify              Alnazer (Hassan ali)
* @version             0.9
* @license             MIT
* @for cakephp version 2.x
*/

App::import('Core', 'Inflector');

class AttachmentComponent extends Component  {

	/**
	 * Configuration options
	 * @var $config array
	 */
	var $Model;
	var $config = array(
		'files_dir'   => 'files',
		'db_col'=>array('file_name'=>'name','file_path'=>'path','file_size'=>'size','content_type'=>'type','file_id'=>'id'),
		'rm_tmp_file' => false,
		'change_filename' => true,
		'allow_non_image_files' => true,
		'images_size' => array(
			/* You may define as many options as you like */
			'large'   => array(640, 480, 'resize'),
			'medium'   => array(263, 263, 'resizeCrop'),
			'thumbnail' => array(150, 150, 'resizeCrop'),
			'small' => array(90, 90, 'resizeCrop')
		)
	);
	function __construct(ComponentCollection $collection, $settings = array())
		{
			$this->config = array_merge(
			$this->config,
			$settings
			);
		
		}
	/**
	 * Initialization method. You may override configuration options from a controller
	 *
	 * @param $controller object
	 * @param $config array
	 */
	function initialize(Controller $controller) {
        $this->controller = $controller;
        $model_prefix = $controller->modelClass; // lower case, studley caps -> underscores
        $prefix = Inflector::singularize($model_prefix); // make singular. 'GalleryImage' becomes 'gallery_image'
        $this->config['default_col'] = $prefix;
        $this->Model = $controller->{$controller->modelClass};
        $this->modelAlias = $this->Model->alias;
        parent::initialize($controller);
	}

	/**
	* Uploads file to file system, according to $config.
	* Example usage:
	*	 $this->Attachment->upload($this->data['Model']['Attachment']);
	*
	* @return mixed boolean true on success, or error string
	* @param $data array the file input array
	* @param $column_prefix string The prefix of the fields used to store the uploaded file data
	*
	*/
	function upload($data, $column_prefix = null) {
		if ($column_prefix == null) {
			$column_prefix = $this->config['default_col'];
		} else {
			$this->config['default_col'] = $column_prefix;
		}

		
		if(array_key_exists($this->config['default_col'], $data)){
			$file = $data[$this->config['default_col']];
		}else{
			$file = $data;
		}
	 
		if ($file['error'] === UPLOAD_ERR_OK) {
			return $this->upload_FS($data);
		} else {
			return $this->log_proper_error($file['error']);
		}
	}

	/**
	 * Creates the relevant dir's and processes the file
	 *
	 * @return mixed boolean true on success, or error string
	 * @param $data array The array of data from the controlle
	 */
	function upload_FS($data=array()) {
		$filename = null;
		$filesize = null;
		$filetype = null;
		$original_name = null;
		$column_prefix = $this->config['default_col'];
		$error = 0;
		$originaluploaddir  = WWW_ROOT.'attachments'.DS.'original'; // /original/ folder (should delete image after upload)
		$fileuploaddir = WWW_ROOT.'attachments'.DS.'files';
		
	 
		// Make sure the required directories exist, and create them if necessary
		if (!is_dir($originaluploaddir)) mkdir($originaluploaddir, 0755, true);
		if (!is_dir($fileuploaddir)) mkdir($fileuploaddir, 0755, true);

		if(array_key_exists($column_prefix, $data)){
			$filename = $data[$column_prefix]['name'];
			$tmp_name = $data[$column_prefix]['tmp_name'];
			$filesize = $data[$column_prefix]['size'];
			$filetype = $data[$column_prefix]['type'];
		}else{
			$filename = $data['name'];
			$tmp_name = $data['tmp_name'];
			$filesize = $data['size'];
			$filetype = $data['type'];
			
		}

		$filename = str_replace(' ', '_', $filename);
		if($this->change_filename){
			$split = split('\.', $filename);
			/* Generate a unique name for the file */
			$_filetype = end($split);
			App::uses('CakeText', 'Utility');
			$filename = CakeText::uuid();
			settype($filename, 'string');
			$filename .= '.' . $_filetype;
		}
 
		/* Security check */
		if (!is_uploaded_file($tmp_name)) {
			return $this->log_cakephp_error_and_return('Error uploading file (sure it was a POST request?).');
		}

		/* If it's image get image size and make thumbnail copies. */
		if ($this->is_image($filetype)) {
			$this->copy_or_log_error($tmp_name, $originaluploaddir, $filename);
			/* Create each thumbnail_size */
			 
			foreach ($this->config['images_size'] as $dir => $opts) {
				$this->thumbnail($originaluploaddir.DS.$filename, $dir, $opts[0], $opts[1], $opts[2]);
			}
			if ($this->config['rm_tmp_file'] == true)
			{
				unlink($originaluploaddir.DS.$filename);
			}
				
		} else {
			if (!$this->config['allow_non_image_files']) {
				return $this->log_cakephp_error_and_return('File type not allowed (only images files).');
			} else {
				$this->copy_or_log_error($tmp_name, $fileuploaddir, $filename);
			}
		}
		 
 	    $db_col = $this->config['db_col'];
		 
		/* File uploaded, return modified data array */
		if(array_key_exists('file_path', $db_col) && !empty( $db_col['file_path'])){
			$res[$db_col['file_path']] = $fileuploaddir.DS.$filename;
		}else{
			$res[$column_prefix.'file_path'] = $fileuploaddir.DS.$filename;
		}
		 
		if(array_key_exists('file_name', $db_col) && !empty( $db_col['file_name'])){
			$res[$db_col['file_name']] = $filename;
		}else{
			$res[$column_prefix.'file_name'] = $filename;
		}
				
		if(array_key_exists('file_size', $db_col) && !empty( $db_col['file_size'])){
			$res[$db_col['file_size']] = $filesize;
		}else{
			$res[$column_prefix.'file_size'] = $filesize;
		}		 
		if(array_key_exists('content_type', $db_col) && !empty( $db_col['content_type'])){
			$res[$db_col['content_type']] = $filetype;
		}else{
			$res[$column_prefix.'content_type'] = $filetype;
		}		 
		 
		$res['id'] = $this->insertFile($res);
		if(array_key_exists($column_prefix, $data)){
			unset($data[$column_prefix]); /* delete $_FILES indirection */
			$data = array_merge(array(), $res);
		}else{
			unset($data); /* delete $_FILES indirection */
			$data = array_merge(array(), $res);
		}

		return $data;
	}
	
	/**
	 * Creates resized copies of input image
	 * E.g;
	 *	 $this->Attachment->thumbnail($this->data['Model']['Attachment'], $upload_dir, 640, 480, false);
	 *
	 * @param $originalfile array The image data array from the form
	 * @param upload_dir string The name of the parent folder of the images
	 * @param $maxw int Maximum width for resizing thumbnails
	 * @param $maxh int Maximum height for resizing thumbnails
	 * @param $crop string either 'resize', 'resizeCrop' or 'crop'
	 */
	function thumbnail($tmpfile, $upload_dir, $maxw, $maxh, $crop = 'resize') {
		// Make sure the required directory exist; create it if necessary
		$upload_dir = WWW_ROOT.'attachments'.DS.$this->config['files_dir'].DS.$upload_dir;
		if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

		/* Directory Separator for windows users */
		$ds = (strcmp('\\', DS) == 0) ? '\\\\' : DS;
		$split_ds = split($ds, $tmpfile);
		$file_name = end($split_ds);
		$this->resize_image($crop, $tmpfile, $upload_dir, $file_name, $maxw, $maxh, 85);
	}

	/**
	 * Deletes file, or image and associated thumbnail
	 * e.g;
	 *	$this->Attachment->delete_files('file_name.jpg');
	 *
	 * @param $filename string The file to delete
	 */
	function delete_files($filename) {
		/* Non image files */
		if (is_file(WWW_ROOT.'attachments'.DS.'files'.DS.$filename)) {
			unlink(WWW_ROOT.'attachments'.DS.'files'.DS.$filename);
		}
		/* tmp files (if not pruned while uploading) */
		if (is_file(WWW_ROOT.'attachments'.DS.'original'.DS.$filename)) {
			unlink(WWW_ROOT.'attachments'.DS.'original'.DS.$filename);
		}
		/* Thumbnail copies */
		foreach ($this->config['images_size'] as $size => $opts) {
			$photo = WWW_ROOT.'attachments'.DS.$this->config['files_dir'].DS.$size.DS.$filename;
			if (is_file($photo)) unlink($photo);
		}
		$this->deleteFile($filename);
		return TRUE;
	}

	/*
	* Creates resized image copy
	*
	* Parameters:
	* cType: Conversion type {resize (default) | resizeCrop (square) | crop (from center)}
	* originalfile: original (tmp) file name
	* newName: include extension (if desired)
	* newWidth: the max width or crop width
	* newHeight: the max height or crop height
	* quality: the quality of the image
	*/
	function resize_image($cType = 'resize', $tmpfile, $dst_folder, $dstname = false, $newWidth=false, $newHeight=false, $quality = 75) {
		$srcimg = $tmpfile;
		list($oldWidth, $oldHeight, $type) = getimagesize($srcimg);
		$ext = $this->image_type_to_extension($type);

		// If file is writeable, create destination (original) image
		if (is_writeable($dst_folder)) {
			$dstimg = $dst_folder.DS.$dstname;
		} else {
			// if dst_folder not writeable, let developer know
			debug('You must allow proper permissions for image processing. And the folder has to be writable.');
			debug("Run 'chmod 755 $dst_folder', and make sure the web server is it's owner.");
			return $this->log_cakephp_error_and_return('No write permissions on attachments folder.');
		}

		/* Check if something is requested, otherwise do not resize */
		if ($newWidth or $newHeight) {
			/* Delete original file if it exists */
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


	/* Many helper functions */

	function copy_or_log_error($tmp_name, $dst_folder, $dst_filename) {
		if (is_writeable($dst_folder)) {
			if (!copy($tmp_name, $dst_folder.DS.$dst_filename)) {
				unset($dst_filename);
				return $this->log_cakephp_error_and_return('Error uploading file.', 'publicaciones');
			}
		 
		} else {
			// if dst_folder not writeable, let developer know
			debug('You must allow proper permissions for image processing. And the folder has to be writable.');
			debug("Run 'chmod 755 $dst_folder', and make sure the web server is it's owner.");
			return $this->log_cakephp_error_and_return('No write permissions on attachments folder.');
		}
	}

	function is_image($file_type) {
		$image_types = array('image/jpeg', 'image/jpg', 'image/gif', 'image/png');
		return in_array(strtolower($file_type), $image_types);
	}

	function log_proper_error($err_code) {
		switch ($err_code) {
			case UPLOAD_ERR_NO_FILE:
				return 0;
			case UPLOAD_ERR_INI_SIZE:
				$e = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$e = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
				break;
			case UPLOAD_ERR_PARTIAL:
				$e = 'The uploaded file was only partially uploaded.';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$e = 'Missing a temporary folder.';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$e = 'Failed to write file to disk.';
				break;
			case UPLOAD_ERR_EXTENSION:
				$e = 'File upload stopped by extension.';
				break;
			default:
				$e = 'Unknown upload error. Did you add array(\'type\' => \'file\') to your form?';
		}
		return $this->log_cakephp_error_and_return($e);
	}

	function log_cakephp_error_and_return($msg) {
		$_error["{$this->config['default_col']}_file_name"] = $msg;
		$this->controller->{$this->controller->modelClass}->validationErrors = array_merge($_error, $this->controller->{$this->controller->modelClass}->validationErrors);
		$this->log($msg, 'attachment-component');
		return false;
	}

	function image_type_to_extension($imagetype) {
		if (empty($imagetype)) return false;
		switch($imagetype) {
			case IMAGETYPE_TIFF_II : return 'tiff';
			case IMAGETYPE_TIFF_MM : return 'tiff';
			case IMAGETYPE_GIF  : return 'gif';
			case IMAGETYPE_JPEG : return 'jpg';
			case IMAGETYPE_PNG  : return 'png';
			case IMAGETYPE_SWF  : return 'swf';
			case IMAGETYPE_PSD  : return 'psd';
			case IMAGETYPE_BMP  : return 'bmp';
			case IMAGETYPE_JPC  : return 'jpc';
			case IMAGETYPE_JP2  : return 'jp2';
			case IMAGETYPE_JPX  : return 'jpf';
			case IMAGETYPE_JB2  : return 'jb2';
			case IMAGETYPE_SWC  : return 'swc';
			case IMAGETYPE_IFF  : return 'aiff';
			case IMAGETYPE_WBMP : return 'wbmp';
			case IMAGETYPE_XBM  : return 'xbm';
			default             : return false;
		}
	}
	
	public function insertFile($fileData='')
	{
		if(!empty($fileData) && is_array($fileData)){
			$this->Model->create();
			if($this->Model->save($fileData)){
				return $this->Model->getLastInsertId();
			}
			
		}
		
		return FALSE;
	}
	
	public function deleteFile($filename='')
	{
		if(!empty($filename)){
			$file_coulm = $this->modelAlias.'.'.$this->config['db_col']['file_name'];
			if($this->Model->deleteAll(array($file_coulm=>$filename))){
				return true;
			}
		}
		return FALSE;
	}

	public function deleteFileByid($id='')
	{
		 if($id){
		 	$data = $this->Model->read(array($this->modelAlias.'.'.$this->config['db_col']['file_name']),$id);
			 if($data){
				$filename = $data[$this->modelAlias][$this->config['db_col']['file_name']];
				return $this->delete_files($filename);
			 }
			 return FALSE;
		 }
		return FALSE;
	}
}
