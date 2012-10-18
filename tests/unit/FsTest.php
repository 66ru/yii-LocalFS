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
		$this->assertEquals('BaseFile', get_class($file), 'matching file class');
		$fileUrl = $file->getUrl();
		$fileContent = @file_get_contents($fileUrl);
		$this->assertEquals('good', $fileContent, 'load file from url');
	}

	public function testInfo()
	{
		/**
		 * @var BaseFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'BaseFile.txt');

		$file->setInfo('string', 'string');
		$file->setInfo(0, 0);
		$file->setInfo(-1, -1);
		$file->setInfo(1, 1);
		$file->setInfo('1', '1');
		$file->setInfo('array', array('key' => 'value'));

		$file = Yii::app()->fs->getFile($file->getUid());

		$this->assertEquals('string', $file->getInfo('string'), 'Check getInfo basic string');
		$this->assertEquals(0, $file->getInfo(0), 'Check getInfo basic int');
		$this->assertEquals(-1, $file->getInfo(-1), 'Check getInfo int < 0');
		$this->assertEquals(1, $file->getInfo('1'), 'Check getInfo int proper key conversion');
		$this->assertEquals(array('key' => 'value'), $file->getInfo('array'), 'Check getInfo array');
	}

	public function testPublishImageFile()
	{
		/**
		 * @var ImageFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'ImageFile/ImageFile.png');
		$this->assertEquals('ImageFile', get_class($file), 'Matching file class');
		$fileUrl = $file->getUrl();
		$this->assertUrlExists($fileUrl, 'Check ImageFile by url');
		$this->assertFileExists($file->getPath());
		$this->assertImage($this->getFixturesPath() . 'ImageFile/ImageFile.png', $file->getPath());
	}

	public function testThumbnail()
	{
		$this->subTestThumbnail($this->getFixturesPath() . 'ImageFile/ImageFile.png');
		$this->subTestThumbnail($this->getFixturesPath() . 'ImageFile-vertical/ImageFile-vertical.png');
	}

	public function subTestThumbnail($path)
	{

		/** @var $file ImageFile */
		$file = Yii::app()->fs->publishFile($path);
		$fixture = pathinfo($path, PATHINFO_FILENAME);

		$this->checkThumbnail($file, array(200, 200), $fixture);

		$this->checkThumbnail($file, array(400, 200), $fixture);

		$this->checkThumbnail($file, array(200, 400), $fixture);

		$this->checkThumbnail($file, array(200, 200, 'cz' => true), $fixture);

		$this->checkThumbnail($file, array(200, 400, 'cz' => true), $fixture);

		$this->checkThumbnail($file, array(400, 200, 'cz' => true), $fixture);

		$this->assertEquals($file->getUrl(), $file->getThumbnail(array($file->width, $file->height)), 'Check if return url except thumbnail url if sizes matching');

		$this->assertEquals($file->getUrl(), $file->getThumbnail(array($file->width, $file->height, 'cz' => true)), 'Check if return url except thumbnail url if sizes matching (with cropZoom)');

		$this->assertEquals($file->getUrl(), $file->getThumbnail(array($file->width + 100, $file->height + 100)), 'Check if return url except thumbnail url if sizes are above');

		$this->assertEquals($file->getUrl(), $file->getThumbnail(array($file->width + 100, $file->height + 100, 'cz' => true)), 'Check if return url except thumbnail url if sizes are above (with cropZoom)');
	}

	public function testPublishVideoFile()
	{
		/**
		 * @var VideoFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'VideoFile.mp4');
		$this->assertEquals('VideoFile', get_class($file), 'Matching file class');
		$fileUrl = $file->getUrl();
		$this->assertUrlExists($fileUrl, 'Check VideoFile by url');
		$this->assertFileExists($file->getPath());
		$count = VideoQueue::model()->countByAttributes(array('uid' => $file->getUid()));
		$this->assertEquals(1, $count, 'Check VideoFile in VideoQueue');
	}

	/**
	 * @param ImageFile $imageFile
	 * @param array $size
	 * @param string $fixture
	 * @return void
	 */
	protected function checkThumbnail($imageFile, $size, $fixture)
	{
		$url = $imageFile->getThumbnail($size);
		$suffix = $imageFile->getSizeSuffix($size);
		$this->assertUrlExists($url, 'Check ' . $suffix . ' thumb by url');
		$path = str_replace(Yii::app()->fs->storageUrl, Yii::app()->fs->storagePath, $url);
		$this->assertImage($this->getFixturesPath() . $fixture . '/' . $fixture . $suffix . '.' . $imageFile->getExt(), $path, "Check $suffix correct resize");
	}
}