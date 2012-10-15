<?php

class ETestCase extends CTestCase
{
	protected function assertUrlExists($url,$message = null) {
		$data = null;
		try {
			$data = CurlHelper::getUrl($url);
		} catch (Exception $e) {
		}
		$this->assertNotNull($data, $message);
	}
}
