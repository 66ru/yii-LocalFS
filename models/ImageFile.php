<?php
/**
 * @property-read int $width
 * @property-read int $height
 */
class ImageFile extends BaseFile
{
	/**
	 * @var int jpeg compress quality (0-100)
	 */
	private $jpegQuality = 90;

	public $width = null;
	public $height = null;

	public function __construct($uid, $ext, $url, $size, $mimeType, $width = null, $height = null)
	{
		$this->uid = $uid;
		$this->ext = $ext;
		$this->url = $url;
		$this->size = $size;
		$this->mimeType = $mimeType;
		$this->width = $width;
		$this->height = $height;
	}

	public function getMetaData()
	{
		return array_merge(parent::getMetaData(), array(
			'width' => $this->width,
			'height' => $this->height,
		));
	}

	/**
	 * @param array $size
	 * @return string
	 */
	private function generateThumbnail($size = array())
	{
		if (!is_array($size)) $size = array($size);

		/** @var $cImage CImageComponent */
		$cImage = Yii::app()->fs->image;
		$imageFile = $this->getPath();
		$pathInfo = pathinfo($imageFile);

		$dst = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . $this->getSizeSuffix($size) . "." . $this->ext;
		if (is_file($dst)) return $dst;

		$originalImage = $cImage->load($imageFile);
		$image = $originalImage;
		if (isset($size['cz']) && $size['cz'] === true)
			$image->cropZoom($size[0], $size[1])->quality($this->jpegQuality);
		else
			$image->resize($size[0], $size[1], !empty($size[2]) ? $size[2] : Image::AUTO)->quality($this->jpegQuality);

		$image->save($dst);

		return $dst;
	}

	/**
	 * @param array $size
	 * @return string
	 */
	public function getThumbnail($size)
	{
		if ($size[0] >= $this->width && $size[1] >= $this->height)
			return $this->getUrl();

		$path = $this->getPath();
		$fileName = pathinfo($path, PATHINFO_FILENAME);

		$suffix = $this->getSizeSuffix($size);

		$this->loadInfo();

		$thumbPath = Yii::app()->fs->getIntermediatePath($this->uid) . $fileName . $suffix . "." . $this->ext;

		if (!isset($this->info['thumbs']))
			$this->info['thumbs'] = array();

		if (!isset($this->info['thumbs'][$suffix])) {
			$this->generateThumbnail($size);
			array_push($this->info['thumbs'], $suffix);
		}
		$this->saveInfo();

		return Yii::app()->fs->storageUrl . $thumbPath;
	}


	/**
	 * @param array $size
	 * @return string
	 */
	public function getSizeSuffix($size)
	{

		$suffix = '';
		if (!is_array($size)) $size = array($size);
		if (empty($size[2]))
			$size[2] = Image::AUTO;

		if ($size[2] == Image::AUTO)
			$suffix = "-{$size[0]}x{$size[1]}";
		elseif ($size[2] == Image::WIDTH)
			$suffix = "-w{$size[0]}"; elseif ($size[2] == Image::HEIGHT)
			$suffix = "-h{$size[1]}";

		if (isset($size['cz']) && $size['cz'] === true)
			$suffix .= '-cz';

		return $suffix;
	}

	/**
	 * @throws CException
	 * @return ImageFile
	 */
	public function watermark()
	{
		if (!Yii::app()->fs->watermarkPath)
			return $this;

		if (!is_file(Yii::app()->fs->watermarkPath))
			throw new CException('Can\'t open watermark file');

		if ((int)Yii::app()->fs->watermarkMinWidth > $this->width)
			return $this;

		$watermark_options = array(
			'watermark' => Yii::app()->params['WATERMARK'],
			'halign' => Watermark::ALIGN_RIGHT,
			'valign' => Watermark::ALIGN_BOTTOM,
			'hshift' => -10,
			'vshift' => -10,
			'type' => IMAGETYPE_JPEG, // Save result in JPEG to minimize file size
			'jpeg-quality' => $this->jpegQuality,
		);
		Watermark::output($this->getPath(), $this->getPath(), $watermark_options);

		return $this;
	}

	/**
	 * @param int $jpegQuality
	 */
	public function setJpegQuality($jpegQuality)
	{
		$jpegQuality = (int)$jpegQuality;
		if ($jpegQuality <= 100 && $jpegQuality >= 0)
			$this->jpegQuality = $jpegQuality;
	}

	/**
	 * Checks if file correct, allowed, fills width & height
	 * @return bool
	 * @throws CException
	 */
	public function validate()
	{
		$cmd = "identify -format \"%w|%h|%k\" " . escapeshellarg($this->getPath()) . " 2>&1";
		$returnVal = 0;
		$output = array();
		exec($cmd, $output, $returnVal);
		if ($returnVal == 0 && count($output) == 1) {
			$imageSizes = explode('|', $output[0]);
			array_pop($imageSizes);
			if (!in_array($this->ext, Yii::app()->fs->allowedImageExtensions))
				return false;

			$this->width = $imageSizes[0];
			$this->height = $imageSizes[1];

			return true;
		} elseif ($returnVal == 127) {
			throw new CException('Can\'t find identify');
		} else {
			return false;
		}
	}

	/**
	 * Saves image with defined quality (if jpg) and watermarks it (if not animated gif)
	 */
	public function afterPublish()
	{
		$image = Yii::app()->fs->image->load($this->getPath());
		$image->quality($this->jpegQuality)->save($this->getPath());
		if (!$this->isAnimated())
			$this->watermark();
	}

	/**
	 * Detects animated gif
	 * @return bool
	 */
	private function isAnimated()
	{
		$filecontents = file_get_contents($this->getPath());
		$str_loc = 0;
		$count = 0;
		while ($count < 2) # There is no point in continuing after we find a 2nd frame
		{

			$where1 = strpos($filecontents, "\x00\x21\xF9\x04", $str_loc);
			if ($where1 === false) {
				break;
			} else {
				$str_loc = $where1 + 1;
				$where2 = strpos($filecontents, "\x00\x2C", $str_loc);
				if ($where2 === false) {
					break;
				} else {
					if ($where1 + 8 == $where2) {
						$count++;
					}
					$str_loc = $where2 + 1;
				}
			}
		}
		if ($count > 1) {
			return true;
		}

		return (false);
	}
}
