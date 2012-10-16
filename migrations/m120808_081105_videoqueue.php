<?php

class m120808_081105_videoqueue extends CDbMigration
{
	public function up()
	{
		$this->createTable('{{videoqueue}}',
			array(
			     'id' => 'pk',
			     'uid' => 'varchar(500) NOT NULL',
			     'status' => 'tinyint(4) NOT NULL',
			     'error' => 'TEXT NOT NULL',
			     'file' => 'varchar(500) NOT NULL',
			)
		);
	}

	public function down()
	{
		$this->dropTable('{{videoqueue}}');
	}
}