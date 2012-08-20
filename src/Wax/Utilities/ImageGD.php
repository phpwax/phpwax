<?php

/**
 * GD Library Functions
 * Creates, processes and outputs a GD Image Resource
 * @author Ross Riley <ross@oneblackbear.com>
 */
class ImageGD {
    
    /**
     * Mapping of image types to function name components.
     */
    protected static $type_map = array(
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG  => 'png',
    );

    /**
     * Get the load function for an image type.
     *
     * @param int $type
     * @return resource
     */
    public static function load($type, $path) {
      if (isset(self::$type_map[$type])) {
        $func = 'imagecreatefrom' . self::$type_map[$type];
      } else {
        throw new WaxException('Unsupported image type: ' . $type);
      }
    }

    /**
     * Get the save function for an image type.
     *
     * @param int $type
     * @return boolean
     */
    public static function save($type, $destination) {
      if (isset(self::$type_map[$type])) {
        $func = 'image' . self::$type_map[$type];
      } else {
        throw new WaxException('Unsupported image type: ' . $type);
      }
    }

    /**
     * Get the save quality for an image type.
     *
     * @param int $type
     * @param int $quality between 0 and 100 inclusive
     * @return int
     */
    public static function get_quality($type, $quality) {
      if($quality < 0 || $quality > 100) {
        throw new \InvalidArgumentException(sprintf('Save quality must be comprised between 0 and 100 inclusive, "%d" given'));
      }

      if(IMAGETYPE_PNG === $type) {
        // Transform quality to PNG compression level
        $quality = abs(($quality - 100) / 11.111111);
      }
      return round($quality);
    }

    /**
     * Checks whether the parameter is a valid GD image resource.
     *
     * @param resource $resource
     * @return boolean
     */
    public static function is_resource($resource) {
      return (is_resource($resource) && 'gd' === get_resource_type($resource));
    }

    /**
     * Creates a new GD image resource.
     *
     * If $type is given, it will be used to initialize type-specific settings
     * on the resource, such as PNG alpha channel support.
     *
     * @param int $width
     * @param int $height
     * @param int $type
     * @return resource
     */
    public static function create_resource($width, $height, $type = null) {
      if (false === ($resource = imagecreatetruecolor($width, $height))) {
        throw new WaxException('Could not create image resource');
      }

      if ($type == IMAGETYPE_PNG) {
        if (! imagealphablending($resource, false)) {
          throw new WaxException('Could not set alpha blending');
        }
        if (! imagesavealpha($resource, true)) {
          throw new WaxException('Could not toggle saving of alpha channel');
        }
      }
      return $resource;
    }
}