<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 7/31/12
 * Time: 3:11 PM
 */
abstract class AFile extends CComponent
{
	private $uid;
	private $url;
	private $size;
	private $mimeType;

	public function __construct($uid,$url,$size,$mimeType) {
		$this->uid = $uid;
		$this->url = $url;
		$this->size = $size;
		$this->mimeType = $mimeType;
	}

	abstract public function getUrl();
	abstract public function getSize();
	abstract public function getUid();
	abstract public function getMimeType();

	abstract public function delete();

	public function afterPublish() {}

	public function validate() {
		return true;
	}
}
