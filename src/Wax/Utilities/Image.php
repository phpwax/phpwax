<?php

/**
 * Image
 *
 * @author Ross Riley <ross@oneblackbear.com>
 */
class Image extends File {

    /**
     * Image path
     * @var string
     */
    protected $path;

    /**
     * Image width
     * @var int
     */
    protected $width;

    /**
     * Image height
     * @var int
     */
    protected $height;

    /**
     * Image type
     * @var int
     */
    protected $type;

    /**
     * MIME type
     * @var string
     */
    protected $mime_type;


    /**
     * Image resource
     * @var resource
     */
    protected $resource;

    /**
     * Constructs an image from a file path.
     * @param string $path
     */
    public function __construct($path) {
        if (false === ($size = @getimagesize($path))) {
            throw new WaxException('Could not determine image info for: ' . $path);
        }

        $this->path = $path;
        $this->width = $size[0];
        $this->height = $size[1];
        $this->type = $size[2];
        $this->mime_type = $size['mime'];
    }



    /**
     * Get image resource
     *
     * @return resource
     */
    public function get_resource() {
      if (! isset($this->resource)) {
        if (false === ($this->resource = call_user_func($this->loadFunction, $this->path))) {
          throw new WaxException('Could not load image: ' . $this->path);
        }
      }
      return $this->resource;
    }

    /**
     * Set image resource
     *
     * @param resource $resource
     */
    public function setResource($resource)
    {
        if (! GDUtls::isResource($resource)) {
            throw new \InvalidArgumentException('Invalid resource');
        }

        if (GDUtls::isResource($this->resource)) {
            imagedestroy($this->resource);
        }

        $this->resource = $resource;
        $this->width = imagesx($this->resource);
        $this->height = imagesy($this->resource);
    }

    /**
     * Free the image resource if possible.
     */
    public function __destruct()
    {
        if (GDUtls::isResource($this->resource)) {
            imagedestroy($this->resource);
        }
    }

}