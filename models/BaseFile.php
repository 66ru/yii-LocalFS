<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 7/31/12
 * Time: 5:23 PM
 */
class BaseFile extends AFile
{
	protected $uid;
	protected $url;
	protected $size;
	protected $ext;
	protected $mimeType;

	protected $info = false;
	protected $infoPath = false;

	public function __construct($uid,$ext,$url,$size,$mimeType) {
		$this->uid = $uid;
		$this->ext = $ext;
		$this->url = $url;
		$this->size = $size;
		$this->mimeType = $mimeType;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return int size in bytes
	 */
	public function getSize()
	{
		if(!$this->size)
			$this->size = filesize($this->getPath());
		return $this->size;
	}

	/**
	 * @return string file id (base64 encoded model's params)
	 */
	public function getUid()
	{
		return base64_encode(json_encode($this->getMetaData()));
	}

	/**
	 * @return string
	 */
	public function getExt()
	{
		return $this->ext;
	}

	/**
	 * @return string unique file id (like in file name)
	 */
	public function getOriginalUid() {
		return $this->uid;
	}

	/**
	 * @return string mimetype
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * Removes file
	 * @return void
	 */
	public function delete()
	{
		Yii::app()->fs->removeFile($this);
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return Yii::app()->fs->getFilePath($this->uid, $this->ext);
	}

	/**
	 * @return array Model params
	 */
	public function getMetaData() {
		return array(
			'uid' => $this->uid,
			'ext' => $this->ext,
			'url' => $this->url,
			'size' => $this->size,
			'mimeType' => $this->mimeType,
		);
	}

	/**
	 * Fills model's params
	 * @param array $metaData
	 */
	public function loadMetaData($metaData) {
		foreach($metaData as $k => $v) {
			if(isset($this->$k)) $this->$k = $v;
		}
	}

	/**
	 * @param $metaData
	 * @return null|BaseFile
	 */
	public static function loadFromMetaData($metaData) {
		$r = new ReflectionClass(get_called_class());
		if (is_array($metaData))
			return $r->newInstanceArgs($metaData);
		else
			return null;
	}


	/**
	 * Returns path to metadata file
	 * @return string
	 */
	private function getInfoPath() {
		if(!$this->infoPath) {
			$this->infoPath = Yii::app()->fs->getInfoFilePath($this->uid);
		}
		return $this->infoPath;
	}

	/**
	 * Loads metadata from file
	 * @return void
	 */
	protected function loadInfo() {
		if($this->info === false){

			if(is_file($this->getInfoPath())) {
				$infoContent = file_get_contents($this->getInfoPath());
				$this->info = unserialize($infoContent);
			} else {
				$this->info = array();
			}
		}
	}

	/**
	 * Saves metadata to file
	 * @return void
	 */
	protected function saveInfo() {
		file_put_contents($this->getInfoPath(),serialize($this->info), LOCK_EX);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setInfo($key,$value) {
		$this->info[(string)$key] = $value;
		$this->saveInfo();
	}

	/**
	 * @param string $key
	 * @return null|mixed
	 */
	public function getInfo($key) {
		$this->loadInfo();
		return isset($this->info[(string)$key]) ? $this->info[(string)$key] : null;
	}
}
