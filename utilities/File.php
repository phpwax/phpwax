<?php
/**
  * File Class encapsulating common file functions
  *
  * @package PHP-Wax
  */

class File {
  
  static $compression_quality = "85";
  static $resize_library = "gd"; // Optionally set to 'gd'
	
	static function is_older_than($file, $time) {
		if(file_exists($file)) {
			$modtime=filemtime($file);
			if($modtime>=(time() - $time ) ) { 
				return false;
			}
			else {
				return true;
			}
		}
	}
	
	static function safe_file_save($dir, $file) {
		$file=preg_replace('/[^\w\.\-_]/', '', $file);
		while(is_file($dir.$file)) {
		  $i = 1;
			$file = substr($file,0,strpos($file, "."))."_$i.".substr(strrchr($file, "."),1);
			$i++;
		}
		return $file;
	}
	
	static function is_image($file) {
		if(!is_file($file)) { return false; }
		if(getimagesize($file)) {
			return true;
		}
		return false;
	}
	
	static function clear_image_cache($image_id){
		$look_for = CACHE_DIR."images/". $image_id."_*";
		foreach(glob($look_for) as $filename){
			@unlink($filename);
		}
	}
	/**
	  * @param $source The Original Image File
	  * @param $destination The New File to write to
	  * @param $width The width of the new image
	  * @return bool
	  */
	static function resize_image($source, $destination, $width, $overwrite=false, $force_width=false) {
	  if(self::$resize_library == "gd" && function_exists("imagecreatefromjpeg")) return self::gd_resize_image($source, $destination, $width, $overwrite=false, $force_width=false);
		if(!self::is_image($source)) return false;
		$dimensions = getimagesize($source);
		$x = $dimensions[0]; $y=$dimensions[1];
		if($y > $x && !$force_width) {
		  $height = $width;
		  $ratio = $y / $width;
		  $width = floor($x / $ratio);
		} else {
		  $ratio = $x / $width;
		  $height = floor($y / $ratio);
	  }
	  if($ratio == 1) $command = "cp ".escapeshellarg($source)." ".escapeshellarg($destination);
		elseif($overwrite) {
			$command="mogrify ".escapeshellarg($source)." -limit area 30 -render -flatten -coalesce -colorspace RGB -resize {$width}x{$height} -quality ".self::$compression_quality;
		} else {
			$command="convert ".escapeshellarg($source)." -limit area 30 -coalesce -thumbnail {$width}x{$height} -density 72x72 -quality ".self::$compression_quality."  $destination";
		}
		system($command);
		if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
	static public function gd_resize_image($source, $destination, $r_width, $overwrite=false, $force_width=false) {
	  list($width, $height, $image_type) = getimagesize($source);
	  if(!$width) return false;
    $r = $width / $height;
    $r_height = $r_width;
    if ($r_width/$r_height > $r && !$force_width) {
      $newwidth = $r_height*$r;
      $newheight = $r_height;
    } else {
      $newheight = $r_width/$r;
      $newwidth = $r_width;
    }

    switch($image_type) {
      case 1: $src = imagecreatefromgif($source); break;
      case 2: $src = imagecreatefromjpeg($source);  break;
      case 3: $src = imagecreatefrompng($source); break;
      default: return '';  break;
    }
    
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagesavealpha($dst, true);
    $trans_colour = imagecolorallocatealpha($dst, 255, 255, 255, 127);
    imagefill($dst, 0, 0, $trans_colour);
    $img = imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
    
		return self::output_image_gd($image_type, $dst, $destination);
	}
	
  /**
   * modes:
   *   crop (default)   - keeps aspect ratio, fills target size, crops the rest
   *   nocrop           - as above, but skips the cropping. 1 dimension will be longer than asked for.
   *   small            - keeps aspect ratio. fits image in specified size. will produce blank pixels.
   *   stretch          - ignores aspect ratio.
   */
  static public function smart_resize_image($source, $destination, $width, $height, $mode = "crop"){
    list($source_width, $source_height, $image_type) = getimagesize($source);
    
    if(!$width && !$height || !function_exists("imagecopyresampled")) return false;
    
    if(!$height || !$width) $mode = "nocrop"; //force nocrop when specifying only 1 dimension
    
    $r_h = $height / $source_height;
    $r_w = $width / $source_width;
    
    if($r_h == $r_h && $r_h == 1){
      copy($source, $destination);
      return true;
    }
    
    //mode calculations, the clever stuff
    if($mode == "small"){
      if($r_h < $r_w) $width = $r_h * $source_width; //ignore target width and use the aspect ratio to work it out
      else $height = $r_w * $source_height; //ignore target height and use the aspect ratio to work it out
    }elseif($mode != "stretch"){ //skip messing with anything for stretch mode, this block runs for crop and aspect modes
      if($r_h > $r_w){
        if($mode == "nocrop") $width = $r_h * $source_width; //ignore target width and use the aspect ratio to work it out
        else{
          $new_source_width = $source_height * $width / $height;
          $source_x = ($source_width - $new_source_width) / 2;
          $source_width = $new_source_width;
        }
      }else{
        if($mode == "nocrop") $height = $r_w * $source_height; //ignore target height and use the aspect ratio to work it out
        else{
          $new_source_height = $source_width * $height / $width;
          $source_y = ($source_height - $new_source_height) / 2;
          $source_height = $new_source_height;
        }
      }
    }
    
    switch($image_type){
      case 1: $src = imagecreatefromgif($source); break;
      case 2: $src = imagecreatefromjpeg($source); break;
      case 3: $src = imagecreatefrompng($source); break;
      default: return false; break;
    }
    
    $dst = imagecreatetruecolor($width, $height);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
    if(!imagecopyresampled($dst, $src, 0, 0, $source_x, $source_y, $width, $height, $source_width, $source_height)) return false;
    
    return self::output_image_gd($image_type, $dst, $destination);
  }
  
  static private function output_image_gd($image_type, $dst, $destination){
    switch($image_type) {
      case 1: $src = imagegif($dst,$destination); break;
      case 2: $src = imagejpeg($dst,$destination, self::$compression_quality);  break;
      case 3: $src = imagepng($dst,$destination); break;
    }
    
    imagedestroy($dst);
    if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
	static function rotate_image($source, $destination, $angle){
	  if(self::$resize_library == "gd" && function_exists("imagerotate")) return self::gd_rotate_image($source, $destination, $angle);
		if(!self::is_image($source)) return false;
		system("cp $source $destination");
		$command="mogrify $source -colorspace RGB -rotate {$angle} $destination";		
		system($command);
		if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
  static function gd_rotate_image($source, $destination, $angle){
	  list($width, $height, $image_type) = getimagesize($source);
	  
    switch($image_type) {
      case 1: $src = imagecreatefromgif($source); break;
      case 2: $src = imagecreatefromjpeg($source);  break;
      case 3: $src = imagecreatefrompng($source); break;
      default: return '';  break;
    }
    
    $dst = imagerotate($src, $angle, -1);
    
		return self::output_image_gd($image_type, $dst, $destination);
  }
  
	static function resize_image_extra($source, $destination, $percent=false, $x=false, $y=false, $ignore_ratio=false){
		if(!self::is_image($source)) return false;
		system("cp {$source} {$destination}");
		$command = "convert {$source} -coalesce -colorspace RGB -resize";
		if($percent) $command.=" {$percent}%";
		elseif($x && $y){
			$command.= " {$x}x{$y}";
			if($ignore_ratio) $command.="\!";
		}
		$command .= " {$destination}";
		system($command);
		if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
	
	static function crop_image($source, $destination, $x, $y, $width, $height){
		if(!self::is_image($source)) return false;
		system("cp $source $destination");
		$command="convert {$source} -crop ".$width."x".$height."+".$x."+".$y." +repage $destination";
		system($command);
		if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
	static function display_image($image) {
		if(!self::is_image($image)) return false;
		$info=getimagesize($image);
		$mime = image_type_to_mime_type($info[2]);
		self::display_asset($image, $mime);
	}
	
	static function display_asset($path, $mime) {
	  if(!is_readable($path)) return false;
	  if($res = self::detect_mime($path)) $mime=$res;
		$length=filesize($path);
		header("Content-Type: " . $mime."\n");
		header("Content-Length: ".$length."\n");
		header("Content-disposition: inline; filename=".basename($path)."\n");
		header('Expires: ' . date('D, d M Y H:i:s',time()+200000) . ' GMT');
    header("Cache-Control: max-age=200000");
    header('Pragma:');
		ob_end_clean();
		$handle = fopen($path, "r");
		  while (!feof($handle)) {
		    echo fread($handle, 8192);
		  }
		fclose($handle);
		exit;
	}
	
	static function detect_mime($file) {
	  $type=false;

	  if($res = self::mime_map($file)) $type=$res;
	  elseif(function_exists('mime_content_type') ){
  		$type = mime_content_type($file);
  	}else{
  		$type = exec("file --mime -b ".escapeshellarg($file));
  	}
  	return $type;
	}
	
	static function stream_file($file, $stream_as = false, $autoexit=true) {
	  $length=filesize($file);
		$filename = preg_replace("/[^a-zA-Z0-9-_\.]/", "_", basename($file));
		if(is_readable($file)) { 
			header("Content-Type: application/force-download"."\n");
			header("Content-Length: ".$length.'\n');
			if($stream_as) $filename = $stream_as;
			header("Content-disposition: inline; filename=".$filename."\n");
			header("Connection: close"."\n");
			ob_end_clean();
			readfile($file); 
			if(!$autoexit) return true;
			exit;
		}
		return false;
	}
	
	static function get_extension($file) {
		return substr($file, strrpos($file, '.')+1);
	}
	
	static function get_folders($directory) {
	  if(!is_dir($directory)) return array();
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST);
		foreach ( $iter as $file ) {
			if(($iter->hasChildren(true)) && !strstr($iter->getPath()."/".$file, "/.")) {
			  if($iter->isLink()) $row['path'] = readlink($iter->getPath().'/'.$file->getFilename());
			  else $row['path']= $iter->getPath().'/'.$file->getFilename();
				$row['name']=str_repeat('&nbsp;&nbsp;', $iter->getDepth()+2).ucfirst($file->getFilename());				
				$rows[]=$row; unset($row);
				if($iter->isLink()) $rows = array_merge($rows, self::get_folders(readlink($iter->getPath().'/'.$file->getFilename())));
			} 
		}
		return $rows;
	}
	
	static function recursively_delete($item) {
	  if(!is_file($item) && !is_dir($item)) return true;
		if(is_file($item) && is_readable($item)) { unlink($item); return true; }
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item), 2);
		foreach ( $iter as $file ) {
				if($iter->isDir() && is_readable($file) && !$iter->isDot()) { rmdir($file); }
				elseif($iter->isFile() && is_readable($file)) unlink($file);
		}
		if(is_dir($item) && is_readable($item) && substr($item, -1) != "." ) { rmdir($item); }
		return true;
	}
		
	static function scandir_recursive($directory) {
	  $folderContents = array();
		foreach (scandir($directory) as $folderItem) {
	    if ($folderItem != "." && $folderItem != ".." && substr($folderItem, 0,1)!='.') {
        if (is_dir($directory.'/'.$folderItem)) {
          $folderContents[$folderItem] = self::scandir_recursive( $directory.'/'.$folderItem);
        } else {
            $folderContents[] = $folderItem;
        }
      }
	  }
    return $folderContents;
	}
	
	static function list_images_recursive($directory) {
		$dir = new RecursiveIteratorIterator(
		           new RecursiveDirectoryIterator($directory), true);
		foreach ( $dir as $file ) {
			if(!strstr($dir->getPath()."/".$file, "/.") ) {
				if(self::is_image($file)) {					
					$imagearray[]=array("filename"=>$dir->getFilename(), "path"=>base64_encode($file));
				}
			}			
		}
		return $imagearray;
	}
	
	static public function scandir($directory, $include_pattern=false) {
	  $list = array();
	  foreach(scandir($directory) as $item) {
	    if(preg_match("/".$include_pattern."/", $item)) $list[]=$item;
	    elseif($item != "." && $item != ".." && substr($item, 0,1)!='.') {
	      $list[]=$item;
	    }
	  }
	  return $list;
	}
	
	static public function render_temp_image($original, $size) {
	  if(self::is_image($original)) {
	    $destination = tempnam(CACHE_DIR, "file_image_");
	    self::resize_image($original, $destination, $size, $overwrite=false);
	    self::display_image($destination);
	    return true;
	  }
	  return false;
	}
	
	static function write_to_file($filename, $filecontents, $mode = 0777) {
		if(! $res = file_put_contents($filename, $filecontents) ) {
		  chmod($filename, $mode);
			return false;
		} else {
			return true;
		}
	}
	
  
  static function read_from_file($filename) {
  	if(!is_readable($filename)) {
      return false;	
  	} else {
      return file_get_contents($filename);	 	
    }
  }
  
  static function recursive_directory_copy($source, $destination, $verbose=true) {
      if(!is_dir($destination)) mkdir($destination);
      foreach(File::scandir($source, ".htaccess") as $file) {
        if(is_file($source. DIRECTORY_SEPARATOR .$file)) {
          copy($source. DIRECTORY_SEPARATOR .$file , $destination. DIRECTORY_SEPARATOR.$file);
          if($verbose) echo "..created ".$destination. DIRECTORY_SEPARATOR.$file."\n";
        } else {
          File::recursive_directory_copy($source. DIRECTORY_SEPARATOR .$file, $destination.DIRECTORY_SEPARATOR.$file);
        }
      }
    }
	
	static function mime_map($filename) {
    $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',

        // audio/video
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',

        // ms office
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    $ext = strtolower(array_pop(explode('.',$filename)));
    if (array_key_exists($ext, $mime_types)) {
      return $mime_types[$ext];
    } 
	}

	
}
?>
