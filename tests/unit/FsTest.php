<?php

class FsTest extends CTestCase
{
	private $fixturesPath;

	private function getFixturesPath()
	{
		if(!$this->fixturesPath) {
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
		$this->assertEquals(get_class($file), 'BaseFile','matching file class');
		$fileUrl = $file->getUrl();
		$fileContent = @file_get_contents($fileUrl);
		$this->assertEquals($fileContent, 'good','load file from url');
	}

	public function testPublishImageFile()
	{
		/**
		 * @var ImageFile $file
		 */
		$file = Yii::app()->fs->publishFile($this->getFixturesPath() . 'ImageFile.jpg');
		$this->assertEquals(get_class($file), 'ImageFile', 'Matching file class');
		$fileUrl = $file->getUrl();
		$this->assertFileExists($file->getPath());
		$data = null;
		try {
			$data = CurlHelper::getUrl($file->getUrl());
		} catch (Exception $e) {
		}
		$this->assertNotNull($data,'Check ImageFile by url');
	}
}
