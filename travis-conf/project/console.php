<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Console Application',

	// preloading 'log' component
	'preload'=>array('log'),

	// application components
	'components'=>array(
		'fs' => array(
			'class' => 'ext.yii-LocalFS.LocalFS',
			'storagePath' => dirname(__FILE__) . '/../../storage/',
			'storageUrl' => 'http://localhost/storage/',
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
			),
		),
		'db' => array(
			'class' => 'CDbConnection',
			'connectionString' => 'mysql:host=localhost;dbname=localfs_test',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => '',
			'charset' => 'utf8',
			'enableProfiling' => false,
			'enableParamLogging' => false,
			'tablePrefix' => '',
			'schemaCachingDuration' => 0,
		),
	),
);