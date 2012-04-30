<?php
/**
 * SlyCrop
 *
 * Base class for all Croppers
 *
 */
class SlyCrop {

	/**
	 *
	 * @var float
	 */
	protected static $start_time = 0.0;

	/**
	 *
	 * @var string
	 */
	protected $imagePath = '';

	/**
	 *
	 * @var Imagick
	 */
	protected $originalImage = null;


	/**
	 * Profiling method
	 */
	public static function start() {
		self::$start_time = microtime(true);
	}

	/**
	 * Profiling method
	 *
	 * @return string
	 */
	public static function mark() {
		$end_time = (microtime(true) - self::$start_time) * 1000;
		return sprintf("%.1fms" ,$end_time);
	}

	/**
	 *
	 * @param string $imagePath
	 */
	public function __construct($imagePath) {
		$this->imagePath = $imagePath;
		$this->originalImage = new Imagick($imagePath);
	}
	
	/**
	 * Get the area in pixels for this image
	 *
	 * @param Imagick $image
	 * @return int
	 */
	protected function area(Imagick $image) {
		$size = $image->getImageGeometry();
		return $size['height'] * $size['width'];
	}

	/**
	 * Returns width and height for resizing the image, keeping the aspect ratio
	 * and allow the image to be larger than either the width or height
	 *
	 * @param Imagick $image
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getSafeResizeOffset(Imagick $image, $targetWidth, $targetHeight) {
		$source = $image->getImageGeometry();
		if(($source['width'] / $source['height']) < ($targetWidth / $targetHeight)) {
			$scale = $source['width'] / $targetWidth;
		} else {
			$scale = $source['height'] / $targetHeight;
		}
		return array('width' => (int) ($source['width'] / $scale), 'height' => (int) ($source['height'] / $scale));
	}

	/**
	 * Put a dot on the image, used for debugging.
	 *
	 * @param Imagick $image
	 * @param int $x
	 * @param int $y
	 * @param string $color
	 */
	protected function dot(Imagick$image, $x, $y, $color="red") {
		$circle= new ImagickDraw();$circle->setFillColor($color);
		$circle->circle($x, $y, $x, $y+6);
		$image->drawImage($circle);
	}

	/**
	 * Returns a YUV weighted greyscale value
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return int
	 * @see http://en.wikipedia.org/wiki/YUV
	 */
	protected function rgb2bw($r, $g, $b) {
		return ($r*0.299)+($g*0.587)+($b*0.114);
	}

	/**
	 *
	 * @param array $histogram - a value[count] array
	 * @param int $area
	 * @return float
	 */
	protected function getEntropy($histogram, $area) {
		$value = 0.0;

		for($idx = 0; $idx < count($histogram); $idx++) {
			// calculates the percentage of pixels having this color value
			$p = $histogram[$idx]->getColorCount() / $area;
			// A common way of representing entropy in scalar
			$value = $value + $p * log($p, 2);
		}
		// $value is always 0.0 or negative, so transform into positive scalar value
		return -$value;
	}
}