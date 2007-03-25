<?php
/**
  * File Class encapsulating common file functions
  *
  * @package wx.php.core
  */

class File {
	
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
			$file = substr($file,0,strpos($file, "."))."_1.".substr(strrchr($file, "."),1);
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
	
	/**
	  * @param $source The Original Image File
	  * @param $destination The New File to write to
	  * @param $width The width of the new image
	  @ @return bool
	  */
	static function resize_image($source, $destination, $width, $overwrite=false) {
		if(!self::is_image($source)) { return false;}
		if($overwrite) {
			$command="mogrify -size {$width}x{$width} $source -resize {$width}x{$width}";
		} else {
			$command="convert -size {$width}x{$width} $source -resize {$width}x{$width} $destination";
		}
		system($command);
		if(!is_file($destination)) { return false; }
		chmod($destination, 0777);
		return true;
	}
	
	static function display_image($image) {
		if(!self::is_image($image)) return false;
		$info=getimagesize($image);
		$length=filesize($image);
		$imagecontent=substr(file_get_contents($image),0 ,-1);
		if($imagecontent) { 
			header("Content-Type: " . image_type_to_mime_type($info[2])."\n");
			header("Content-Length: ".$length.'\n');
			header("Content-disposition: inline; filename=".basename($image)."\n");
			header("Connection: close"."\n");
			ob_end_clean();
			print trim($imagecontent); exit;
		}
		return false;
	}
	
	static function stream_file($file) {
	  $length=filesize($file);
		$file_content=substr(file_get_contents($file),0 ,-1);
		$filename = preg_replace("/[^a-zA-Z0-9-_]/", "_", basename($file));
		if($file_content) { 
			header("Content-Type: application/force-download"."\n");
			header("Content-Length: ".$length.'\n');
			header("Content-disposition: inline; filename=".basename($file)."\n");
			header("Connection: close"."\n");
			ob_end_clean();
			print trim($file_content); exit;
		}
		return false;
	}
	
	static function get_extension($file) {
		return substr($file, strrpos($file, '.')+1);
	}
	
	static function get_folders($directory) {
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), true);
		foreach ( $iter as $file ) {
			if($iter->hasChildren() && !strstr($iter->getPath()."/".$file, "/.")) {
				$row['name']=str_repeat('&nbsp;&nbsp;', $iter->getDepth()+2).ucfirst($file);
				$row['path']=$iter->getPath().'/'.$file;
				$rows[]=$row; unset($row);
			} 
		}
		return $rows;
	}
	
	static function recursively_delete($item) {
	  if(!is_file($item) && !is_dir($item)) return true;
		if(is_file($item)) { unlink($item); return true; }
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item), 2);
		foreach ( $iter as $file ) {
				if($iter->isDir()) { rmdir($file); }
				else { unlink($file); }
		}
		if(is_dir($item)) { rmdir($item); }
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
				if(self::is_image($dir->getPath()."/".$file)) {
					$imagearray[]=array("filename"=>$dir->getFilename(), "path"=>base64_encode($dir->getPath()."/".$file));
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
	
	static function write_to_file($filename, $filecontents) {
		if(! $res = file_put_contents($filename, $filecontents) ) {
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
	

	
}
?>
