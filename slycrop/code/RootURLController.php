<?php
/**
 * A SilverStripe root url controller
 */
class RootURLController extends Controller {

	/**
	 * Thumbnail all images in assets/originals and save them in assets/thumbs/
	 *
	 */
	public function index() {
		$width = 200;
		$height = 100;

		$files = glob(ASSETS_PATH.'/originals/*');
		$images = array();

		$to = ASSETS_PATH.'/thumbs/';
		$thumbnailDir = ASSETS_DIR.'/thumbs/';

		// Start profiling timer
		SlyCrop::start();
		foreach($files as $filePath) {
			$fileInfo = pathinfo($filePath);

			// Run the SlyCropEntropy cropper
			$entropy = new SlyCropEntropy($filePath);
			$croppedImage = $entropy->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-entropy.jpg';
			$this->enhance($croppedImage);
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'entropy', 'Width'=>$width));

			// Run the SlyCropCenter cropper
			$center = new SlyCropCenter($filePath);
			$croppedImage = $center->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-center.jpg';
			$this->enhance($croppedImage);
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'center', 'Width'=>$width));
			
			// Run the SlyCropBalanced cropper
			$center = new SlyCropBalanced($filePath);
			$croppedImage = $center->resizeAndCrop($width, $height);
			$thumbnailPath = '/thumbs/'.$fileInfo['filename'].'-balanced.jpg';
			$this->enhance($croppedImage);
			$croppedImage->writeimage(ASSETS_PATH.$thumbnailPath);
			$images[] = new ArrayData(array('FilePath'=>ASSETS_DIR.$thumbnailPath, 'Method'=>'balanced', 'Width'=>$width));

		}
		
		return $this->customise(new ArrayData(array(
			'Timer' => SlyCrop::mark(),
			'Images'=> new ArrayList($images)
		)))->renderWith('Page');
	}

	/**
	 * Do some tricks to cleanup and minimize the thumbnails size
	 *
	 * @param Imagick $image
	 */
	protected function enhance($image) {
		$image->setImageCompression(imagick::COMPRESSION_JPEG);
		$image->setImageCompressionQuality(75);
		$image->contrastImage( 1 );
		$image->adaptiveBlurImage( 1, 1 );
		$image->stripImage();
	}
}