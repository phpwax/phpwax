<?php
/**
  * File Class encapsulating common file functions
  *
  * @package wx.php.core
  */

abstract class File {
	
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
	
	static function safe_filename($file) {
		$file=preg_replace('/[^\w\.-_]+/', '', $file);
		return $file;
	}
	
	static function is_file($file) {
		if(is_file($file)) { return true; }
		return false;
	}
	
	static function is_image($file) {
		if(!self::is_file($file)) { return false; }
		if(getImageSize($file)) {
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
	static function resize_image($source, $destination, $width) {
		if(!self::is_image($source)) { return false;}
		$command="convert -size {$width}x{$width} $source -resize {$width}x{$width} $destination";
		system($command);
		if(!is_file($destination)) { return false; }
		return true;
	}
	
	static function display_image($image) {
		$info=getImageSize($image);
		$length=filesize($image);
		$imagecontent=substr(file_get_contents($image),0 ,-1);
		if($imagecontent) { 
			header("Content-Type: " . image_type_to_mime_type($info[2]).'\n');
			header("Content-Length: ".$length.'\n');
			header("Content-disposition: inline; filename=".basename($imagecontent).'\n');
			header("Connection: close".'\n');
			ob_end_clean();
			print trim($imagecontent); exit;
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
	
	static function remove_item($within_base, $item) {
		if(!strpos($item, $within_base)===0) { return false; }
		if(is_file($item)) { unlink($item); return true; }
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item), 2);
		foreach ( $iter as $file ) {
				if($iter->isDir()) { rmdir($iter->getPath().'/'.$file); }
				else { unlink($iter->getPath().'/'.$file); }
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
	
	
	
}
?>
