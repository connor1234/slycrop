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
		$crop = $this->getSafeResizeOffset($this->origalImage, $targetWidth, $targetHeight);

		// Get the offset for cropping the image further
		$this->origalImage->resizeImage($crop['width'], $crop['height'], Imagick::FILTER_CATROM, 0.5);
		$offset = $this->getRandomEdgeOffset($this->origalImage, $targetWidth, $targetHeight);
		$this->spot($this->origalImage, $offset['x'], $offset['y'], 'green');
		return $this->origalImage;
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
		#$measureImage = $original;
		// Enhance edges
		$measureImage->edgeimage($radius = 1);
		// Turn image into a grayscale
		$measureImage->modulateImage(100, 0, 100);
		// Turn everything darker than this to pitch black
		$measureImage->blackThresholdImage("#101010");
		// Get the calculated offset for cropping
		return $this->getOffsetFromRandomEdge($measureImage, $targetWidth, $targetHeight);
	}

	public function getOffsetFromRandomEdge($targetWidth, $targetHeight) {

		$goalY = $goalX = 0;
		$size = $this->origalImage->getImageGeometry();

		$points = array();


		$halfWidth = ceil($size['width']/2);
		$halfHeight = ceil($size['height']/2);

		$clone = clone($this->origalImage);
		$clone->cropimage($halfWidth, $halfHeight, 0, 0);
		$point = $this->getHighestEnergyPoint($clone);

		$points[] = array('x' => $point['x'], 'y' => $point['y'], 'sum' => $point['sum']);

		$clone = clone($this->origalImage);
		$clone->cropimage($halfWidth, $halfHeight, $halfWidth, 0);
		$point = $this->getHighestEnergyPoint($clone);

		$points[] = array('x' => $point['x']+$halfWidth, 'y' => $point['y'], 'sum' => $point['sum']);

		$clone = clone($this->origalImage);
		$clone->cropimage($halfWidth, $halfHeight, 0, $halfHeight);
		$point = $this->getHighestEnergyPoint($clone);

		$points[] = array('x' => $point['x'], 'y' => $point['y']+$halfHeight, 'sum' => $point['sum']);

		$clone = clone($this->origalImage);
		$clone->cropimage($halfWidth, $halfHeight, $halfWidth, $halfHeight);
		$point = $point = $this->getHighestEnergyPoint($clone);

		$points[] = array('x' => $point['x']+$halfWidth, 'y' => $point['y']+$halfHeight, 'sum' => $point['sum']);

		$totalEnergy = array_reduce($points, function($result, $array){
			return $result + $array['sum'];
		});

		foreach($points as $point) {
			$circle= new ImagickDraw();$circle->setFillColor("red");
			$circle->circle($point['x'], $point['y'], $point['x'],$point['y']+2);
			$this->origalImage->drawImage($circle);
		}

		$sumX = 0; $sumY = 0;
		for($idx=0;$idx<count($points);$idx++) {
			$points[$idx]['w_sum'] = $points[$idx]['sum']/$totalEnergy;
			$sumX += $points[$idx]['x'] * $points[$idx]['w_sum'];
			$sumY += $points[$idx]['y'] * $points[$idx]['w_sum'];
		}
		$centerX = $sumX ;
		$centerY = $sumY ;

		return array('x'=>$centerX, 'y'=>$centerY);
	}

	protected function getHighestEnergyPoint($image) {
		$size = $image->getImageGeometry();
		$image->writeimage('/tmp/image');
		$im = imagecreatefromjpeg('/tmp/image');
		$xcenter = 0;
		$ycenter = 0;
		$sum = 0;
		$n = round($size['height']*$size['width'])/100;
		for ($k=0; $k<$n; $k++) {
			$i = mt_rand(0,$size['width']-1);
			$j = mt_rand(0,$size['height']-1);
			$val = imagecolorat($im, $i, $j) & 0xFF;
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
}