<?php

use ManiaControl\Logger;

/**
 * PHP Unit Test for Logger Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LoggerTest extends PHPUnit_Framework_TestCase {
	public function testGetLogsFolder(){
		$this->assertEquals(Logger::getLogsFolder(), MANIACONTROL_PATH . 'logs' . DIRECTORY_SEPARATOR);
	}
}
