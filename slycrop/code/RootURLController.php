<?php

class RootURLController extends Controller {

	public function index() {
		$width = 100;
		$height = 200;

		$files = glob(ASSETS_PATH.'/originals/*');
		$images = array();

		$to = ASSETS_PATH.'/thumbs/';
		$thumbnailDir = ASSETS_DIR.'/thumbs/';

		
		SlyCrop::start();
		foreach($files as $filePath) {
			$fileInfo = pathinfo($filePath);

			$entropy = new SlyCropEntropy($filePath);
			$croppedImage = $entropy->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-entropy.jpg';
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'entropy', 'Width'=>$width));


			$center = new SlyCropCenter($filePath);
			$croppedImage = $center->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-center.jpg';
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'center', 'Width'=>$width));
			
			
			$center = new SlyCropBalanced($filePath);
			$croppedImage = $center->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-balanced.jpg';
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'balanced', 'Width'=>$width));

		}

		return $this->customise(new ArrayData(array(
			'Timer' => SlyCrop::mark(),
			'Images'=> new ArrayList($images)
		)))->renderWith('Page');
	}
}