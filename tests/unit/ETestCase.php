<?php
class CTestCase extends PHPUnit_Framework_TestCase
{
}
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

	/**
	 * @param string $expected path
	 * @param string $actual path
	 * @param string $message
	 */
	protected function assertImage($expected, $actual, $message = '')
	{
		$descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
			array('pipe', 'w'),
		);
		$command = 'compare -metric RMSE ' . escapeshellarg($expected) . ' ' . escapeshellarg($actual) . ' /dev/null';
		$proc = proc_open($command, $descriptors, $pipes);

		$diff = stream_get_contents($pipes[2]);
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$matched = preg_match('#\((.+)\)#', $diff, $match);
		$this->assertGreaterThan(0, $matched, $diff . ' @ ' . $actual);
		$threshold = floatval($match[1]);
		$this->assertLessThan(0.05, $threshold, $message);
	}
}
