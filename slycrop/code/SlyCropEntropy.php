<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SlyCropEntropy
 *
 * @author stig
 */
class SlyCropEntropy extends SlyCrop {

	/**
	 *
	 * @param string $imagePath
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return Imagick
	 */
	public function resizeAndCrop($targetWidth, $targetHeight ) {

		// First get the size that we can use to safely trim down the image to
		// without cropping any sides
		$crop = $this->getSafeResizeOffset($this->origalImage, $targetWidth, $targetHeight);
		// Get the offset for cropping the image further
		$this->origalImage->resizeImage($crop['width'], $crop['height'], Imagick::FILTER_CATROM, 0.5);
		$offset = $this->getEntropyOffsets($this->origalImage, $targetWidth, $targetHeight);
		$this->origalImage->cropImage($targetWidth, $targetHeight, $offset['x'], $offset['y']);
		return $this->origalImage;
	}

	
	/**
	 *
	 * @param Imagick $original
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getEntropyOffsets(Imagick $original, $targetWidth, $targetHeight) {
		$measureImage = clone($original);
		#$measureImage = $original;
		// Enhance edges
		$measureImage->edgeimage(1);
		// Turn image into a grayscale
		$measureImage->modulateImage(100, 0, 100);
		// Turn everything darker than this to pitch black
		$measureImage->blackThresholdImage("#070707");
		// Get the calculated offset for cropping
		return $this->getOffsetFromEntropy($measureImage, $targetWidth, $targetHeight);
	}

	/**
	 *
	 * @param Imagick $image
	 * @param int $targetHeight
	 * @param int $targetHeight
	 * @param int $sliceSize
	 * @return array
	 */
	protected function getOffsetFromEntropy(Imagick $image, $targetWidth, $targetHeight) {
		$size = $image->getImageGeometry();
		$originalWidth = $scanWidth = $size['width'];
		$originalHeight = $scanHeight = $size['height'];
		$goalY = $goalX = 0;

		$sliceSize = ceil($this->area($image) / (1024 * 2));

		$firstImage = null;
		$otherImage = null;

		while($scanHeight-$goalY > $targetHeight) {
			$slizeSize = min(array($scanHeight - $goalY - $targetHeight, $sliceSize));
			if(!$firstImage) {
				$firstImage = clone($image);
				$firstImage->cropImage($originalWidth, $slizeSize, 0, $goalY);
			}
			if(!$otherImage) {
				$otherImage = clone($image);
				$otherImage->cropImage($originalWidth, $slizeSize, 0, $scanHeight - $slizeSize);
			}
			// Remove the slice with the least entropy
			if($this->grayscaleEntropy($firstImage) < $this->grayscaleEntropy($otherImage)) {
				$goalY += $slizeSize; $firstImage = null;
			} else {
				$scanHeight -= $slizeSize; $otherImage = null;
			}
		}

		$firstImage = $otherImage = null;

		while($scanWidth-$goalX > $targetWidth) {
			$sliceSize = min(array(($scanWidth-$goalX-$targetWidth), $sliceSize));
			if(!$firstImage) {
				$firstImage = clone($image);
				$firstImage->cropImage($sliceSize, $originalHeight, $goalX, 0);
			}
			if(!$otherImage) {
				$otherImage = clone($image);
				$otherImage->cropImage($sliceSize, $originalHeight, $scanWidth - $sliceSize, 0);
			}
			// Remove the slice with the least entropy
			if($this->grayscaleEntropy($firstImage) < $this->grayscaleEntropy($otherImage)) {
				$goalX += $sliceSize; $firstImage = null;
			} else {
				$scanWidth -= $sliceSize; $otherImage = null;
			}
		}

		return array('x' => $goalX, 'y' => $goalY);
	}

	/**
	 * Calculate the entropy for this image
	 *
	 * @param Imagick $image
	 * @return float
	 */
	protected function grayscaleEntropy(Imagick $image) {
		$area = $this->area($image);
		$histogram = $image->getImageHistogram();
		$value = 0.0;

		for($idx = 0; $idx < count($histogram); $idx++) {
			$p = $histogram[$idx]->getColorCount() / $area;
			$value = $value + $p * log($p, 2);
		}

		return -$value;
	}

		/**
	 * Find out the entropy for a color image by taking into account the YUV color
	 * model: http://en.wikipedia.org/wiki/YUV
	 *
	 * @param Imagick $image
	 * @return float
	 */
	protected function colorEntropy(Imagick $image) {
		$area = $this->area($image);
		$histogram = $image->getImageHistogram();
		$value = 0.0;

		$newHistogram = array();

		for($idx = 0; $idx < count($histogram); $idx++) {
			$colors = $histogram[$idx]->getColor();
			$grey = (($colors['r']*0.299)+($colors['g']*0.587)+($colors['b']*0.114));

			if(!isset($result[$grey])) {
				$newHistogram[$grey] = $histogram[$idx]->getColorCount();
			} else {
				$newHistogram[$grey] += $histogram[$idx]->getColorCount();
			}
		}

		foreach($newHistogram as $colorCount) {
			$p = $colorCount / $area;
			$value = $value + $p * log($p, 2);
		}

		return -$value;

	}
}