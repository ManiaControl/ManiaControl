<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 14. Apr. 2017
 * Time: 22:41
 */

use ManiaControl\Logger;

class LoggerTest extends PHPUnit_Framework_TestCase {
	public function testGetLogsFolder(){
		$this->assertEquals(Logger::getLogsFolder(), MANIACONTROL_PATH . 'logs' . DIRECTORY_SEPARATOR);
	}
}
