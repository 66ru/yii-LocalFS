<?php
require_once "ETestCase.php";
class FsTest extends ETestCase
{
	private $fixturesPath;

	private function getFixturesPath()
	{
		if (!$this->fixturesPath) {
			$this->fixturesPath = __DIR__ . '/../fixtures/';
		}

		return $this->fixturesPath;
	}

	public function testPublishBaseFile()
	{
		/**
		 * @var BaseFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'BaseFile.txt');
		$this->assertEquals(get_class($file), 'BaseFile', 'matching file class');
		$fileUrl = $file->getUrl();
		$fileContent = @file_get_contents($fileUrl);
		$this->assertEquals($fileContent, 'good', 'load file from url');
	}

	public function testPublishImageFile()
	{
		/**
		 * @var ImageFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'ImageFile.jpg');
		$this->assertEquals(get_class($file), 'ImageFile', 'Matching file class');
		$fileUrl = $file->getUrl();
		$this->assertUrlExists($fileUrl,'Check ImageFile by url');
		$this->assertFileExists($file->getPath());
	}

	public function testThumbnail()
	{

		/** @var $file ImageFile */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'ImageFile.jpg');
		$url = $file->getThumbnail(array(200,200));
		$this->assertUrlExists($url,'Check 200x200 thumb by url');

		$url = $file->getThumbnail(array(400,200));
		$this->assertUrlExists($url,'Check 400x200 thumb by url');

		$url = $file->getThumbnail(array(200,400));
		$this->assertUrlExists($url,'Check 200x400 thumb by url');

		$url = $file->getThumbnail(array(200,200, 'cz' => true));
		$this->assertUrlExists($url,'Check 200x200 cropZoom thumb by url');

		$url = $file->getThumbnail(array(400,200, 'cz' => true));
		$this->assertUrlExists($url,'Check 400x200 cropZoom thumb by url');

		$url = $file->getThumbnail(array(200,400, 'cz' => true));
		$this->assertUrlExists($url,'Check 200x400 cropZoom thumb by url');
	}
}
