<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 7/31/12
 * Time: 3:05 PM
 */
abstract class AFileSystem extends CApplicationComponent
{
	/**
	 * @var bool cache all used files or not
	 */
	public $useCache = true;

	/**
	 * @param AFile $model
	 * @return bool
	 */
	public function removeFile($model)
	{
		$model->delete();
	}

	/**
	 * @param $uid
	 * @param $options array
	 * @return AFile
	 */
	public abstract function getFile($uid, $options = array());

	/**
	 * @param $path
	 * @return AFile
	 */
	public abstract function publishFile($path, $options = array());

	/**
	 *
	 * @var array маппинг типов файлов на конкретный класс-наследник этого абстрактного класса,
	 * необходим для фабрики конкретных инстансов для файлов разных типов
	 */
	private static $_types2Class = array(
		'image/jpeg' => 'ImageFile',
		'image/gif' => 'ImageFile',
		'image/png' => 'ImageFile',
		'image/vnd.microsoft.icon' => 'ImageFile',

		'video/webm' => 'VideoFile',
		'video/x-matroska' => 'VideoFile',
		'video/x-msvideo' => 'VideoFile', // avi
		'video/x-flv' => 'VideoFile', // flv
		'video/x-fli' => 'VideoFile', // flv, fli
		'video/quicktime' => 'VideoFile', // mov, qt
		'video/mpeg' => 'VideoFile', // mpeg
		'video/mp4' => 'VideoFile', // mp4
		'video/x-ms-wmv' => 'VideoFile', //wmv
		'video/x-ms-asf' => 'VideoFile', //wmv
		'video/3gpp' => 'VideoFile', // 3gp
		/*'audio/mpeg' => 'AudioFile', //mp3
		'audio/x-wav' => 'AudioFile', //wav
		'audio/x-flac' => 'AudioFile', //flac
		'audio/x-ogg' => 'AudioFile', //ogg
		'audio/ogg' => 'AudioFile', //ogg
		'audio/x-mp4' => 'AudioFile', //mp4
		'audio/x-m4a' => 'AudioFile', //m4a
		'audio/m4a' => 'AudioFile', //m4a
		'audio/mp4' => 'AudioFile', //m4a

		'audio/x-aac' => 'AudioFile', //aac
		'audio/x-ms-wma' => 'AudioFile', //wma

		'application/pdf' => 'PdfFile',
		'application/msword' => 'DocFile', // doc*/
	);

	/**
	 * @param $mimeType string
	 * @return mixed
	 */
	public function getFileClass($mimeType)
	{
		if (!isset(self::$_types2Class[$mimeType])) return false;
		else return self::$_types2Class[$mimeType];
	}
}
