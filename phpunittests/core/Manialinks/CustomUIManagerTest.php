<?php

use ManiaControl\ManiaControl;

/**
 * PHP Unit Test for Custom UI Manager
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomUIManagerTest extends PHPUnit_Framework_TestCase {
	public function testUiProperties() { //Not Working Yet
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$customUiManager = $maniaControl->getManialinkManager()->getCustomUIManager();
		$maniaControl->run(3);

		//Connect Again and Disable Notices
		$maniaControl->connect();
		$customUiManager->disableNotices();

		$maniaControl->run(3);

		$this->assertFalse($customUiManager->getShootManiaUIProperties()->getUiPropertiesObject()->notices->visible);

		//Connect Again and Disable Notices
		$maniaControl->connect();
		$customUiManager->enableNotices();

		$maniaControl->run(3);

		var_dump($customUiManager->getShootManiaUIProperties()->getUiPropertiesObject()->notices);
		$this->assertTrue($customUiManager->getShootManiaUIProperties()->getUiPropertiesObject()->notices->visible);

	}
}
