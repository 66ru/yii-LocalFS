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

	public function __construct($uid,$ext,$url,$size,$mimeType) {
		$this->uid = $uid;
		$this->ext = $ext;
		$this->url = $url;
		$this->size = $size;
		$this->mimeType = $mimeType;
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getSize()
	{
		if(!$this->size)
			$this->size = filesize($this->getPath());
		return $this->size;
	}

	public function getUid()
	{
		return base64_encode(json_encode($this->getMetaData()));
	}
	public function getExt()
	{
		return $this->ext;
	}

	public function getOriginalUid() {
		return $this->uid;
	}

	public function getMimeType()
	{
		return $this->mimeType;
	}

	public function delete()
	{
		Yii::app()->fs->removeFile($this);
	}

	public function getPath()
	{
		return Yii::app()->fs->getFilePath($this->uid, $this->ext);
	}

	public function getMetaData() {
		return array(
			'uid' => $this->uid,
			'ext' => $this->ext,
			'url' => $this->url,
			'size' => $this->size,
			'mimeType' => $this->mimeType,
		);
	}

	public function loadMetaData($metaData) {
		foreach($metaData as $k => $v) {
			if(isset($this->$k)) $this->$k = $v;
		}
	}

	public static function loadFromMetaData($metaData) {
		$r = new ReflectionClass(get_called_class());
		if (is_array($metaData))
			return $r->newInstanceArgs($metaData);
		else
			return null;
	}

	public function validate() {
		return true;
	}

	public function afterPublish() {}
}
