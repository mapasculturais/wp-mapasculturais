<?php 
class Image
{
	private $name;
	private $width;
	private $height;
	
	function __construct($name, $size)
	{
		$this->name = $name;
		$this->width = $size;
		$this->height = $size;

		if(gettype($size) == 'array'){
			$this->width = $size[0];
			$this->height = $size[1];
		}
	}

	public function addThumb(){
		$this->name = 'thumb_'.$this->name;
		$this->addImage();
	}

	public function addImage(){
		add_image_size($this->name, $this->width, $this->height, true);
	}
}