<?php

Yii::import('ext.localFS.components.image.drivers.Image_GD_Driver');

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
