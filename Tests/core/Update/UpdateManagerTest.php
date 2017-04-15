<?php

namespace Tests\core\Update;


use ManiaControl\ManiaControl;
use ManiaControl\Update\UpdateManager;

final class UpdateManagerTest extends \PHPUnit_Framework_TestCase {

	public function testBuildDate() {
		$maniaControl  = new ManiaControl();
		$updateManager = new UpdateManager($maniaControl);

		$fileName = MANIACONTROL_PATH . "core" . DIRECTORY_SEPARATOR . UpdateManager::BUILD_DATE_FILE_NAME;

		if(!file_exists($fileName)){
			$this->assertTrue($updateManager->setBuildDate("BuildDateTest-6543210"));
		}

		$this->assertFileExists($fileName);

		$buildDate = $updateManager->getBuildDate();
		$this->assertStringEqualsFile($fileName, $buildDate);

		$this->assertTrue($updateManager->setBuildDate("BuildDateTest-0123456"));
		$this->assertEquals($updateManager->getBuildDate(), "BuildDateTest-0123456");

		$this->assertStringEqualsFile($fileName, $updateManager->getBuildDate());
	}

	public function testGetPluginUpdateManagerTest() {
		$maniaControl  = new ManiaControl();
		$updateManager = new UpdateManager($maniaControl);

		$pluginUpdateManager = $updateManager->getPluginUpdateManager();

		$this->assertInstanceOf("ManiaControl\\Update\\PluginUpdateManager", $pluginUpdateManager);
	}

	public function testIsNightlyUpdateChannel() {
		$maniaControl  = new ManiaControl();
		$updateManager = new UpdateManager($maniaControl);

		$this->assertTrue($updateManager->isNightlyUpdateChannel(UpdateManager::CHANNEL_NIGHTLY));

		$isNightly = $updateManager->isNightlyUpdateChannel(null);

		$this->assertEquals($updateManager->isNightlyUpdateChannel($updateManager->getCurrentUpdateChannelSetting()), $isNightly);
	}

	public function testCoreUpdateAsync() {
		$maniaControl  = new ManiaControl();

		$updateManager = $maniaControl->getUpdateManager();

		$called = false;
		$function = function ($updateData) use (&$called){
			$called = true;
			$this->assertNotNull($updateData);
			$this->assertObjectHasAttribute("version", $updateData);
		};

		$updateManager->checkCoreUpdateAsync($function);

		$maniaControl->run(6);

		$this->assertTrue($called);
	}
}
