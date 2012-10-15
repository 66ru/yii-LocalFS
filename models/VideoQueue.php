<?php
/**
 * Created by JetBrains PhpStorm.
 * User: bazilio
 * Date: 8/2/12
 * Time: 6:51 PM
 */
class VideoQueue extends CActiveRecord
{
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function tableName()
	{
		return 'videoqueue';
	}
}
