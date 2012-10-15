<?php

Yii::import('localFS.components.image.drivers.*');
Yii::import('localFS.components.image.Image');

class CImageComponent extends CComponent
{
	public function load($image)
	{
		$config = array(
			'driver' => 'GD',
			'params' => array(),
		);

		return new Image($image, $config);
	}
}

?>
