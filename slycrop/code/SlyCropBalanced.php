<?php
/**
 * SlyCropBalanced
 *
 */
class SlyCropBalanced extends SlyCrop{

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
		$crop = $this->getSafeResizeOffset($this->originalImage, $targetWidth, $targetHeight);

		// Get the offset for cropping the image further
		$this->originalImage->resizeImage($crop['width'], $crop['height'], Imagick::FILTER_CATROM, 0.5);
		$offset = $this->getRandomEdgeOffset($this->originalImage, $targetWidth, $targetHeight);
		$this->originalImage->cropImage($targetWidth, $targetHeight, $offset['x'], $offset['y']);
		return $this->originalImage;
	}

	/**
	 *
	 * @param Imagick $original
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	protected function getRandomEdgeOffset(Imagick $original, $targetWidth, $targetHeight) {
		$measureImage = clone($original);
		// Enhance edges
		$measureImage->edgeimage($radius = 1);
		// Turn image into a grayscale
		$measureImage->modulateImage(100, 0, 100);
		// Turn everything darker than this to pitch black
		$measureImage->blackThresholdImage("#101010");
		// Get the calculated offset for cropping
		return $this->getOffsetBalanced($targetWidth, $targetHeight);
	}

	/**
	 *
	 * @param int $targetWidth
	 * @param int $targetHeight
	 * @return array
	 */
	public function getOffsetBalanced($targetWidth, $targetHeight) {

		$size = $this->originalImage->getImageGeometry();

		$points = array();

		$halfWidth = ceil($size['width']/2);
		$halfHeight = ceil($size['height']/2);

		// First quadrant
		$clone = clone($this->originalImage);
		$clone->cropimage($halfWidth, $halfHeight, 0, 0);
		$point = $this->getHighestEnergyPoint($clone);
		$points[] = array('x' => $point['x'], 'y' => $point['y'], 'sum' => $point['sum']);

		// Second quadrant
		$clone = clone($this->originalImage);
		$clone->cropimage($halfWidth, $halfHeight, $halfWidth, 0);
		$point = $this->getHighestEnergyPoint($clone);
		$points[] = array('x' => $point['x']+$halfWidth, 'y' => $point['y'], 'sum' => $point['sum']);

		// Third quadrant
		$clone = clone($this->originalImage);
		$clone->cropimage($halfWidth, $halfHeight, 0, $halfHeight);
		$point = $this->getHighestEnergyPoint($clone);
		$points[] = array('x' => $point['x'], 'y' => $point['y']+$halfHeight, 'sum' => $point['sum']);

		// Fourth quadrant
		$clone = clone($this->originalImage);
		$clone->cropimage($halfWidth, $halfHeight, $halfWidth, $halfHeight);
		$point = $point = $this->getHighestEnergyPoint($clone);
		$points[] = array('x' => $point['x']+$halfWidth, 'y' => $point['y']+$halfHeight, 'sum' => $point['sum']);

		// get the totalt sum value so we can find out a mean center point
		$totalWeight = array_reduce($points, function($result, $array){
			return $result + $array['sum'];
		});

		$centerX = 0; $centerY = 0;

		// Calulate the mean weighted center x and y
		for($idx=0; $idx < count($points); $idx++) {
			$centerX += $points[$idx]['x'] * ($points[$idx]['sum'] / $totalWeight);
			$centerY += $points[$idx]['y'] * ($points[$idx]['sum'] / $totalWeight);
		}

		// From the weighted center point to the topleft corner of the crop would be
		$topleftX = max(0, ($centerX - $targetWidth / 2));
		$topleftY = max(0, ($centerY - $targetHeight / 2));

		// If we don't have enough width for the crop, back up $topleftX until
		// we can make the image meet $targetWidth
		if($topleftX + $targetWidth > $size['width']){
			$topleftX -= ($topleftX+$targetWidth) - $size['width'];
		}
		// If we don't have enough height for the crop, back up $topleftY until
		// we can make the image meet $targetHeight
		if($topleftY+$targetHeight > $size['height']){
			$topleftY -= ($topleftY+$targetHeight) - $size['height'];
		}

		return array('x'=>$topleftX, 'y'=>$topleftY);
	}

	/**
	 *
	 * @param type $image
	 * @return type
	 */
	protected function getHighestEnergyPoint(Imagick $image) {
		$size = $image->getImageGeometry();
		$image->writeimage('/tmp/image');
		$im = imagecreatefromjpeg('/tmp/image');
		$xcenter = 0;
		$ycenter = 0;
		$sum = 0;
		$sampleSize = round($size['height']*$size['width'])/100;
		for ($k=0; $k<$sampleSize; $k++) {
			$i = mt_rand(0, $size['width']-1);
			$j = mt_rand(0, $size['height']-1);

			$rgb = imagecolorat($im, $i, $j);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;

			$val = $this->rgb2bw($r, $g, $b);
			$sum += $val;
			$xcenter += ($i+1)*$val;
			$ycenter += ($j+1)*$val;
		}

		if($sum) {
			$xcenter /= $sum;
			$ycenter /= $sum;
		}

		$point = array('x' => $xcenter, 'y' => $ycenter, 'sum' => $sum/round($size['height']*$size['width']));

		return $point;
	}

	/**
	 * Returns a YUV weighted greyscale value
	 *
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return int
	 */
	protected function rgb2bw($r, $g, $b) {
		return ($r*0.299)+($g*0.587)+($b*0.114);
	}
}