<?php
Yii::setPathOfAlias('localFS',__DIR__);
Yii::import('localFS.models.*');
Yii::import('localFS.components.*');
Yii::import('localFS.components.image.CImageComponent');
/**
 * File system extension for Yii Framework
 * <hr>
 * Requirements:
 * <ul>
 *  <li>GD</li>
 *  <li>ImageMagick</li>
 * </ul>
 * Note: currently planing to migrate to ImageMagick
 * Works only under *nix systems
 *
 * @link https://github.com/mediasite/yii-LocalFS
 */
class LocalFS extends AFileSystem
{
	/**
	 * @var null|string path to storage dir. Default = www/storage/
	 */
	public $storagePath = null;
	/**
	 * @var null|string url to storage dir. Default = /storage/
	 */
	public $storageUrl = null;

	/**
	 * @var int how much intermediate folders must be created in storage folder for publishing file. <br />
	 * <b>WARNING!</b> Do not change if storage dir is not empty!
	 */
	public $nestedFolders = 5;

	/**
	 * @var bool Use or not fs caching during app run (useful when you've got lot of same images on one page)
	 * Not recommended to use with cron tasks (due memory issues)
	 */
	public $useCache = true;

	/**
	 * @var null|string path to watermark file. Leave empty to disable watermarks
	 */
	public $watermarkPath = null;

	/**
	 * @var array allowed image types
	 */
	public $allowedImageExtensions = array("jpg", "jpeg", "gif", "png");

	/**
	 * @var array Stores loaded objects meta if useCache is enabled
	 */
	private $_loaded = array();

	/**
	 * @var CImageComponent
	 */
	public $image;

	/**
	 * @param $path
	 * @param array $options
	 * @throws CException
	 * @return AFile | null
	 */
	public function publishFile($path, $options = array())
	{
		if (!file_exists($path))
			throw new CException("file '$path' doesn't exists");

		if (!empty($options['originalName']))
			$originalName = $options['originalName'];
		else
			$originalName = $path;
		$ext = strtolower(CFileHelper::getExtension($originalName));
		if (empty($ext)) { // we have empty extension. Trying determine using mime type
			$ext = self::getExtensionByMimeType($path);
		}

		$uid = $this->getUniqId();
		$publishedFileName = $this->getFilePath($uid, $ext);
		$newDirName = $this->getFileDir($uid);
		if (!file_exists($newDirName))
			mkdir($newDirName, 0777, true);


		copy($path, $publishedFileName);
		chmod($publishedFileName, 0666);

		$mimeType = CFileHelper::getMimeType($path);
		$size = filesize($path);
		var_dump($mimeType,$size);
		$class = $this->getFileClass($mimeType);

		$url = $this->storageUrl . $this->getIntermediatePath($uid) . "$uid.$ext";
		if (!$class || !class_exists($class)) {
			$class = 'BaseFile';
		}

		$file = new $class($uid, $ext, $url, $size, $mimeType);


		/**
		 * @var $file BaseFile
		 */
		if ($file->validate()) {
			$file->afterPublish();
		} else {
			$file->delete();

			return false;
		}

		if ($this->useCache)
			$this->_loaded[$file->getUid()] = $file;

		return $file;
	}

	/**
	 * @param $uid64
	 * @param array $options
	 * @return AFile|bool
	 */
	public function getFile($uid64, $options = array())
	{
		if (empty($uid64))
			return false;
		if (!empty($this->_loaded[$uid64]))
			return $this->_loaded[$uid64];
		$metaData = json_decode(base64_decode($uid64), true);
		$class = $this->getFileClass($metaData['mimeType']);
		if (!$class || !class_exists($class)) {
			$class = 'BaseFile';
		}

		$file = $class::loadFromMetaData($metaData);
		if ($this->useCache) $this->_loaded[$uid64] = $file;

		return $file;
	}

	/**
	 * @param BaseFile $model
	 * @return bool
	 */
	public function removeFile($model)
	{
		if (isset($this->_loaded[$model->getUid()]))
			unset($this->_loaded[$model->getUid()]);

		$dirName = $this->getFileDir($model->getOriginalUid());
		$fileName = $this->getFileName($model->getOriginalUid(), $model->getExt());
		foreach (glob($dirName . '/' . $fileName . '*') as $file)
			unlink($file);
	}

	public function getInfoFilePath($uid)
	{
		return $this->getFilePath($uid, 'txt');
	}

	public function getFilePath($uid, $ext)
	{
		return $this->getFileDir($uid) . $this->getFileName($uid, $ext);
	}

	public function getFileDir($uid)
	{
		return $this->storagePath . $this->getIntermediatePath($uid);
	}

	public function getFileName($uid, $ext)
	{
		return $uid . '.' . $ext;
	}

	public function init()
	{
		if (is_null($this->storagePath)) {
			$this->storagePath = Yii::app()->basePath . '/../www/storage';
		}
		if (is_null($this->storageUrl)) {
			$this->storageUrl = '/storage/';
		}
		if (!file_exists($this->storagePath))
			mkdir($this->storagePath, 0777, true);
		$this->storagePath = realpath($this->storagePath) . '/';
		if (!is_dir($this->storagePath))
			throw new CException('FileSystem->storagePath is not dir (' . $this->storagePath . ')');

		$this->image = new CImageComponent();
	}

	private function getUniqId()
	{
		return md5(microtime(true) . mt_rand());
	}

	public function getIntermediatePath($uid)
	{
		$path = '';
		$fileName = pathinfo($uid, PATHINFO_FILENAME);
		for ($i = 0; $i < $this->nestedFolders; $i++) {
			$path .= substr($fileName, $i * 2, 2) . '/';
		}

		return $path;
	}

	/**
	 * Download and publish file from specified url
	 * @param $url
	 * @param int $timeout
	 * @throws CException
	 * @return bool | aFile
	 */
	public function publishFileFromUrl($url, $timeout = 12)
	{
		$tempFile = tempnam(sys_get_temp_dir(), Yii::app()->id);
		try {
			CurlHelper::downloadToFile($url, $tempFile, array(
				CURLOPT_CONNECTTIMEOUT => $timeout,
				CURLOPT_FOLLOWLOCATION => true,
			));
		} catch (Exception $e) {
			return false;
		}

		$ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
		if (empty($ext) && file_exists($tempFile)) { // we have empty extension. Trying determine using mime type
			$ext = self::getExtensionByMimeType($tempFile);
			if (!$ext) {
				@unlink($tempFile);

				return false;
			}
		} elseif (strlen($ext) > 4) {
			return false;
		}

		$uid = $this->getUniqId();
		$publishedFileName = $this->getFilePath($uid, $ext);
		$newDirName = $this->getFileDir($uid);
		if (!file_exists($newDirName))
			@mkdir($newDirName, 0777, true);
		if (!file_exists($newDirName))
			throw new CException('Can\'t create ' . $newDirName);
		rename($tempFile, $publishedFileName);
		chmod($publishedFileName, 0666);

		$mimeType = CFileHelper::getMimeType($publishedFileName);
		$size = filesize($publishedFileName);
		$class = $this->getFileClass($mimeType);
		$url = $this->storageUrl . $this->getIntermediatePath($uid) . "$uid.$ext";
		if (!$class || !class_exists($class)) {
			$class = 'BaseFile';
		}

		$file = new $class($uid, $ext, $url, $size, $mimeType);
		/**
		 * @var $file BaseFile
		 */
		if ($file->validate()) {
			$file->afterPublish();
		} else {
			$file->delete();

			return false;
		}

		if ($this->useCache) $this->_loaded[$file->getUid()] = $file;

		return $file;
	}

	public static function getExtensionByMimeType($fileName)
	{
		$mimeTypes = require(Yii::getPathOfAlias('system.utils.mimeTypes') . '.php');
		$unsetArray = array('jpe', 'jpeg');
		foreach ($unsetArray as $key)
			unset($mimeTypes[$key]);

		$mimeType = CFileHelper::getMimeType($fileName);

		return (string)array_search($mimeType, $mimeTypes);
	}
}