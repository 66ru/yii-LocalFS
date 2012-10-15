<?php
Yii::import('ext.image.*');
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 7/31/12
 * Time: 5:01 PM
 */
class VideoFile extends BaseFile
{
	/** @var null|array  */
	public $_info = null;

	private $infoPath = false;

	public function getUrl($ext = false)
	{
		if (!$ext)
			$ext = $this->ext;

		if (empty($this->info['formats']) || empty($this->info['formats'][$ext])) {
			return false;
		} else {
			$preUrl = parent::getUrl();
			return str_replace($this->uid . ".{$this->ext}", pathinfo($this->uid, PATHINFO_FILENAME) . ".$ext", $preUrl);
		}
	}

	/**
	 * @return mixed
	 */
	public function getInfo()
	{
		if (is_null($this->_info)) {
			if (is_file($this->getInfoPath())) {
				$infoContent = file_get_contents($this->getInfoPath());
				$this->_info = unserialize($infoContent);
			} else {
				$this->_info = array();
			}
		}

		return $this->_info;
	}

	public function setInfo($value){
		$this->_info = $value;
	}

	/**
	 * @return void
	 */
	public function saveInfo()
	{
		file_put_contents($this->getInfoPath(), serialize($this->info));
	}

	/**
	 * @return string
	 */
	private function getInfoPath() {
		if(!$this->infoPath) {
			$this->infoPath = Yii::app()->fs->getInfoFilePath($this->uid);
		}
		return $this->infoPath;
	}

	public function delete()
	{
		VideoQueue::model()->deleteAllByAttributes(array('uid' => $this->getUid()));
		parent::delete();
	}

	public function afterPublish() {
		$queue = new VideoQueue();
		$queue->uid = $this->getUid();
		$queue->file = $this->getOriginalUid();
		$queue->save();
	}
}
