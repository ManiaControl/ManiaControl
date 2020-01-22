<?php

namespace Tests\core\Update;

use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Update\UpdateData;
use ManiaControl\Update\UpdateManager;

/**
 * PHP Unit Test for Update Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
final class UpdateManagerTest extends \PHPUnit_Framework_TestCase {

	private function getBuildDateFileName() {
		return MANIACONTROL_PATH . "core" . DIRECTORY_SEPARATOR . UpdateManager::BUILD_DATE_FILE_NAME;
	}

	public function testBuildDate() {
		$maniaControl  = new ManiaControl();
		$updateManager = new UpdateManager($maniaControl);

		$fileName = $this->getBuildDateFileName();

		if (!file_exists($fileName)) {
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

	public function testCheckCoreUpdateAsync() {
		$maniaControl = new ManiaControl();

		$updateManager = $maniaControl->getUpdateManager();

		$called   = false;
		$function = function ($updateData) use (&$called) {
			$called = true;
			$this->assertNotNull($updateData);
			$this->assertObjectHasAttribute("version", $updateData);
		};

		$updateManager->checkCoreUpdateAsync($function);

		$maniaControl->run(6);

		$this->assertTrue($called);
	}

	public function testPerformCoreUpdate() {
		$maniaControl  = new ManiaControl();
		$updateManager = $maniaControl->getUpdateManager();

		//No Update Data Available -> so Fail
		$this->assertFalse($updateManager->performCoreUpdate());

		//Should Also Fail with a Player
		$player = new Player($maniaControl, true);
		$this->assertFalse($updateManager->performCoreUpdate($player));

		$message = '[ERROR] Update failed: No update Data available!';
		$message = '[' . date('d-M-Y H:i:s e') . '] ' . $message . PHP_EOL;

		//Check message
		$this->assertContains($message, $this->getActualOutput());

		$dataJson = '[{"id":"260","version":"0.166","channel":"nightly","min_dedicated_build":"2014-04-02_18_00","release_date":"2017-03-16 21:57:40","url":"https:\/\/download.maniacontrol.com\/nightly\/ManiaControl_nightly_0-166.zip"}]';

		//Create and Test Core Update Data
		$updateData = new UpdateData(json_decode($dataJson)[0]);

		$this->assertEquals("0.166", $updateData->version);
		$this->assertEquals("nightly", $updateData->channel);
		$this->assertEquals("2014-04-02_18_00", $updateData->minDedicatedBuild);
		$this->assertEquals("2017-03-16 21:57:40", $updateData->releaseDate);
		$this->assertEquals("https://download.maniacontrol.com/nightly/ManiaControl_nightly_0-166.zip", $updateData->url);

		$updateManager->setCoreUpdateData($updateData);

		//Methods should return its non closure success
		$this->assertTrue($updateManager->performCoreUpdate($player));

		$maniaControl->run(5);

		//Check Backup
		$backupFolder = MANIACONTROL_PATH . 'backup' . DIRECTORY_SEPARATOR;
		$backupFileName = $backupFolder . 'backup_' . ManiaControl::VERSION . '_' . date('y-m-d_H-i') . '.zip';
		$this->assertFileExists($backupFileName);

		//Remove Backup Again
		unlink($backupFileName);

		//Check if Tempfolder got Deleted
		$tempFolder = MANIACONTROL_PATH . 'temp' . DIRECTORY_SEPARATOR;
		$this->assertFileNotExists($tempFolder);

		//Check if UpdateFileName got Deleted
		$updateFileName = $tempFolder . basename($updateData->url);
		$this->assertFileNotExists($updateFileName);

		$fileName = $this->getBuildDateFileName();
		$this->assertStringEqualsFile($fileName, $updateData->releaseDate);
		$this->assertEquals($updateData->releaseDate, $updateManager->getBuildDate());
		$this->assertContains("Update finished!", $this->getActualOutput());
	}

	public function testPerformCoreUpdateFailUrl() {
		$maniaControl  = new ManiaControl();
		$updateManager = $maniaControl->getUpdateManager();

		$dataJson = '[{"id":"260","version":"0.166","channel":"nightly","min_dedicated_build":"2014-04-02_18_00","release_date":"2017-03-16 21:57:40","url":"https:\/\/download.maniacontrol.com\/nightly\/ManiaControl_nightly_0-166.zip"}]';

		//Create and Test Core Update Data
		$updateData      = new UpdateData(json_decode($dataJson)[0]);
		$updateData->url = "Invalid_URL";
		$updateManager->setCoreUpdateData($updateData);

		$updateManager->performCoreUpdate();

		$maniaControl->run(5);

		$player = new Player($maniaControl, true);
		$this->assertTrue($updateManager->performCoreUpdate($player));
		$this->assertContains("[ERROR] Update failed: Couldn't load Update zip! Could not resolve host: Invalid_URL", $this->getActualOutput());
	}

	//TODO real test with download and unpack in a certain dir
}
