<?php
/**
 * User: bazilio
 * Date: 8/16/12
 * Time: 2:36 PM
 */
class UploadController extends Controller
{

	/**
	 * @var
	 */
	protected $file;
	private $allowedClass = 'BaseFile';

	public function filters()
	{
		return array_merge(parent::filters(), array(
			'accessControl',
		));
	}

	public function accessRules()
	{
		return array(
			array('allow',
				'users'=>array('@')
			),
			array('deny',
				'users'=>array('*')
			),
		);
	}

	protected function handleUpload(){
		Yii::import("ext.EAjaxUpload.qqFileUploader");

		$uploader = new qqFileUploader();
		try {
			$file = $uploader->handleUpload();

			if(get_class($file) === $this->allowedClass) {
				$result = array();
				if (isset($_GET['useMosaic']) && $_GET['useMosaic'] == 'true')
					$result['useMosaic'] = 1;

				if (isset($file)){
					$result['filename'] = $file->getUid();
					$result['success'] = 'ok';
				}

				echo CJSON::encode($result);
			} else {
				echo CJSON::encode(array('error' => 'Неподдерживаемый тип файла'));
				$file->delete();
			}
		} catch (Exception $e) {
			echo CJSON::encode(array('error'=>"Произошла ошибка при публикации файла:\n".$e->getMessage()));
		}
	}

	public function actionUploadImage() {
		$this->allowedClass = 'ImageFile';
		$this->handleUpload();
	}

	public function actionUploadVideo() {
		$this->allowedClass = 'VideoFile';
		$this->handleUpload();
	}

	public function actionUpload() {
		$this->handleUpload();
	}
}
