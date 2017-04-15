<?php

use ManiaControl\ManiaControl;

class ManiaControlTest extends PHPUnit_Framework_TestCase {
	public function testRun(){
		$maniaControl = new ManiaControl();
		$maniaControl->run(10);

		sleep(15);

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
