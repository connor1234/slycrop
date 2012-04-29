Sly Crop
=============

A couple of PHP classes to crop images to a fixed widht and height using different variants of cropping techniques without changing the aspect ratio.

Just for ease of testing and development it's currently implemented as a SilverStripe Framework module, but the main classes can be used independently.

### Croppers implemented

- [SlyCropCenter](https://github.com/stojg/slycrop/blob/master/slycrop/code/SlyCropCenter.php) Crop the image around the center of the image
- [SlyCropEntropy](https://github.com/stojg/slycrop/blob/master/slycrop/code/SlyCropEntropy.php) Center the crop on the most edgiest location in the image 
- [SlyCropBalanced](https://github.com/stojg/slycrop/blob/master/slycrop/code/SlyCropBalanced.php) Center the crop based on a weighted edgiest point in the image

### Todo and ideas

- Facedetection cropping
- Using more sample points for the SlyCropBalanced cropper
- Refactor to be more DRY
- Find the most interesting square in the image and tightly crop around it

### Kudos

Kudos goes to these people for example code and explanations:

- Jue Wang - [Opticrop](http://jueseph.com/2010/06/opticrop-content-aware-cropping-with-php-and-imagemagick/)
- Peter Sobot - [A Use for Smartphone Photos](http://petersobot.com/blog/a-use-for-smartphone-photos/)
- zaeleus - [Content Aware Kropping by Entropy](https://gist.github.com/a54cd41137b678935c91)