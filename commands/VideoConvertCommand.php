<?php
Yii::import('localFS.models.*');
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 8/2/12
 * Time: 6:47 PM
 */
define('FFMPEG_BIN_PATH', Yii::app()->fs->ffmpegBinPath);
define('MP4BOX_BIN_PATH', Yii::app()->fs->MP4BoxBinPath);

spl_autoload_unregister(array('YiiBase', 'autoload'));
require_once(Yii::getPathOfAlias('localFS.components.ffmpeg-php') . '/FFmpegAutoloader.php');
spl_autoload_register(array('YiiBase', 'autoload'));

class VideoConvertCommand extends CConsoleCommand
{
	protected $note;

	public function actionIndex()
	{
		$criteria = new CDbCriteria();
		$criteria->limit = 1;
		$criteria->condition = 'status >= 0';

		while ($items = VideoQueue::model()->findAll($criteria)) {
			/** @var $item VideoQueue */
			while ($item = array_shift($items)) {

				/** @var $videoFile VideoFile */
				$videoFile = Yii::app()->fs->getFile($item->uid);
				$sourceName = $videoFile->getPath();
				if (!is_file($sourceName)) {
					if (!is_file($sourceName)) {
						$this->note = "Файл $sourceName не найден";
						echo $this->note . "\n";
					}
					$this->postponeFileConvert($item);
					continue;
				}
				$fName = pathinfo($sourceName);
				if (!isset($fName['extension'])) {
					$fName['extension'] = '';
				}

				if ($fName['extension'] == "mp4") {
					rename($sourceName, $fName['dirname'] . '/' . 'orig_' . $fName['filename'] . '.' . $fName['extension']);
					$sourceName = $fName['dirname'] . '/' . 'orig_' . $fName['filename'] . '.' . $fName['extension'];
				}

				$previewName = $fName['dirname'] . '/' . $fName['filename'] . '.jpg';

				$sourceMovie = new ffmpeg_movie($sourceName, false);

				$text = FFMPEG_BIN_PATH . ' -i ' . $sourceName . ' ';

				//Video options
				$text .= ' -vcodec libx264 -maxrate 4M -flags +loop -cmp +chroma -me_range 16 -g 300 -keyint_min 25 -sc_threshold 40 -i_qfactor 0.71 -rc_eq "blurCplx^(1-qComp)" -qcomp 0.6 -qmin 10 -qmax 51 -qdiff 4 -coder 0 -refs 1 -bufsize 4M -level 21 -partitions parti4x4+partp8x8+partb8x8 -subq 5 -f mp4 -b 512k -bt 1024k -threads 0 -acodec libfaac  -ac 2 -ar 44100 -ab 128k';

				$duration = $sourceMovie->getDuration();
				$width = $sourceMovie->getFrameWidth();
				$height = $sourceMovie->getFrameHeight();
				$maxWidth = Yii::app()->params['maxVideoWidth'];

				if ($width > $maxWidth) {
					$ratio = $width / $height;
					$height = intval(round($maxWidth / $ratio));
					$width = intval(round($height * $ratio));
				}
				if ($height % 2 == 1)
					$height++;

				if ($width % 2 == 1)
					$width++;

				$s = ' -s ' . $width . 'x' . $height;
				$text .= $s;

				$text .= ' -y ' . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . '.mp4';

				if ($this->runConvertCommand($text)) {
					$this->note = "Unable to convert file $sourceName to " . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4";
					echo $this->note . "\n";

					$this->postponeFileConvert($item);
					continue;
				}

				if ($this->runConvertCommand(MP4BOX_BIN_PATH . ' -inter 500 -tmp ' . $fName['dirname'] . ' ' . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . '.mp4')) {
					$this->note = "Error moving metadata in " . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4";
					echo $this->note . "\n";
					$this->postponeFileConvert($item);
					continue;
				}
				if (!is_file($fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4")) {
					$this->note = "Unable to convert file " . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4";
					echo $this->note;
					$this->postponeFileConvert($item);
					continue;
				}

				rename($fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4", $fName['dirname'] . '/' . $fName['filename'] . ".mp4");
				if (!is_file($fName['dirname'] . '/' . $fName['filename'] . ".mp4")) {
					$this->note = "Unable to move file " . $fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4";
					echo $this->note . "\n";
					$this->postponeFileConvert($item);
					continue;
				}

				$text = FFMPEG_BIN_PATH . ' -strict experimental -i ' . $fName['dirname'] . '/' . $fName['filename'] . '.mp4 ' . $s . '  -b 1500k -vcodec libtheora -acodec libvorbis -ab 160000 -g 30 -y ' . $fName['dirname'] . '/' . $fName['filename'] . ".ogv";

				if ($this->runConvertCommand($text)
				) {
					$this->note = "Unable to convert file to ogv " . $fName['dirname'] . '/' . $fName['filename'] . ".mp4";
					echo $this->note . "\n";
					$this->postponeFileConvert($item);
					continue;
				}

				if ($duration < 1 || !$duration) {
					$previewSec = 0;
				} else {
					$previewSec = min(floor($duration), 5);
				}

				$text = FFMPEG_BIN_PATH . '  -i ' . $fName['dirname'] . '/' . $fName['filename'] . '.mp4 -vcodec mjpeg -ss ' . $previewSec . ' -vframes 1 -y ' . $previewName;

				$this->runConvertCommand($text);

				if (!is_file($previewName) || filesize($previewName) <= 0) {
					$this->note = "Unable to make preview $previewName";
					echo $this->note . "\n";
					$this->postponeFileConvert($item);
					continue;
				}
				@unlink($fName['dirname'] . '/' . $fName['filename'] . '_temp' . ".mp4");
				$videoFile->setInfo('formats', array('mp4' => filesize($fName['dirname'] . '/' . $fName['filename'] . ".mp4"), 'jpg' => true, 'ogv' => filesize($fName['dirname'] . '/' . $fName['filename'] . ".ogv")));
				$videoFile->setInfo('media', array('width' => $width, 'height' => $height, 'duration' => $duration));
				$item->delete();
			}
		}
	}

	/**
	 * @param $item VideoQueue
	 * @return bool
	 */
	protected function postponeFileConvert($item)
	{
		$item->error = $this->note;
		$item->status = -10;

		return $item->save();
	}

	/**
	 * @param string $command
	 * @return int the termination status of the process that was run.
	 */
	protected function runConvertCommand($command)
	{
		$descriptors = array(
			array('pipe', 'r'),
			array('pipe', 'w'),
		);
		$command .= ' 2>&1';
		$process = proc_open($command, $descriptors, $pipes);
		// Close STDIN pipe
		fclose($pipes[0]);
		$out = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		// Wait for process to terminate and store return value
		$returnCode = proc_close($process);
		if ($returnCode)
			echo "*** STDOUT: ***\r\n" . $out . "\r\n";

		return $returnCode;
	}
}