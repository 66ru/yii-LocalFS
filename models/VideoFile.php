<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 7/31/12
 * Time: 5:01 PM
 */
class VideoFile extends BaseFile
{
	/**
	 * @param bool $ext
	 * @return bool|string
	 */
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

	/**
	 * Send video to convertation queue
	 */
	public function afterPublish() {
		$queue = new VideoQueue();
		$queue->uid = $this->getUid();
		$queue->file = $this->getOriginalUid();
		$queue->save();
	}

	public function delete()
	{
		VideoQueue::model()->deleteAllByAttributes(array('uid' => $this->getUid()));
		parent::delete();
	}


}
