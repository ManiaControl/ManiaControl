<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 16. Apr. 2017
 * Time: 15:38
 */

use ManiaControl\Utils\SystemUtil;

class SystemUtilTest extends PHPUnit_Framework_TestCase {
	public function testCheckRequirements() {
		SystemUtil::checkRequirements();

		$this->assertContains("Checking for minimum required PHP-Version 5.4", $this->getActualOutput());
		$this->assertContains("Checking for installed MySQLi ... FOUND!", $this->getActualOutput());
		$this->assertContains("Checking for installed cURL ... FOUND!", $this->getActualOutput());
		$this->assertContains("Checking for installed PHP ZIP ... FOUND!", $this->getActualOutput());
		$this->assertContains("Checking for installed Zlib ... FOUND!", $this->getActualOutput());
	}
}
