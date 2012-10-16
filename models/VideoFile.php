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

	public function getUrl($ext = false)
	{
		if (!$ext)
			$ext = $this->ext;

		$this->loadInfo();
		if (empty($this->info['formats']) || empty($this->info['formats'][$ext])) {
			return false;
		} else {
			$preUrl = parent::getUrl();
			return str_replace($this->uid . ".{$this->ext}", pathinfo($this->uid, PATHINFO_FILENAME) . ".$ext", $preUrl);
		}
	}
}
