<?php
/**
 * SlyCropCenter
 *
 */
class SlyCropCenter extends SlyCrop {

	/**
	 * Plain old boring cropping from the center of the image
     *
	 * @param Imagick $image
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getCenterOffset(Imagick $image, $targetWidth, $targetHeight) {
		$size = $image->getImageGeometry();
		$originalWidth = $size['width'];
		$originalHeight = $size['height'];
		$goalX = (int)(($originalWidth-$targetWidth)/2);
		$goalY = (int)(($originalHeight-$targetHeight)/2);
		return array('x' => $goalX, 'y' => $goalY);
	}

	
	/**
	 *
	 * @param string $imagePath
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return boolean|\Imagick
	 */
	public function resizeAndCrop($targetWidth, $targetHeight) {
		// First get the size that we can use to safely trim down the image to
		// without cropping any sides
		$crop = $this->getSafeResizeOffset($this->origalImage, $targetWidth, $targetHeight);
		$this->origalImage->resizeImage($crop['width'], $crop['height'], Imagick::FILTER_CATROM, 0.5);
		$offset = $this->getCenterOffset($this->origalImage, $targetWidth, $targetHeight);
		$this->origalImage->cropImage($targetWidth, $targetHeight, $offset['x'], $offset['y']);
		#$image->setImageCompression(imagick::COMPRESSION_JPEG);
		#$image->setImageCompressionQuality(75);
		#$image->contrastImage( 1 );
		#$image->adaptiveBlurImage( 1, 1 );
		#$image->stripImage();
		return $this->origalImage;
	}
}
