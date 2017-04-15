<?php

use ManiaControl\Logger;

class LoggerTest extends PHPUnit_Framework_TestCase {
	public function testGetLogsFolder(){
		$this->assertEquals(Logger::getLogsFolder(), MANIACONTROL_PATH . 'logs' . DIRECTORY_SEPARATOR);
	}
}
