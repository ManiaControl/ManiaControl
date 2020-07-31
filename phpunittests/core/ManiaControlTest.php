<?php

use ManiaControl\ManiaControl;

/**
 * PHP Unit Test for Mania Control Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaControlTest extends PHPUnit_Framework_TestCase {
	public function testRun(){
		$maniaControl = new ManiaControl();
		$maniaControl->run(10);

		sleep(15);

		$this->assertNotNull($maniaControl->getBillManager());
		//$this->l
		//$this->assertNull($maniaControl);
	}

/*	public function testGetClient(){
		$maniaControl = new ManiaControl();
		$mcClient = $maniaControl->getClient();

		//$maniaControl->connect();
		//$mpClient = new Maniaplanet\DedicatedServer\Connection();
	}*/
}
