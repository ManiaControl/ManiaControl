<?php

namespace Tests\core\Update;

use ManiaControl\ManiaControl;
use ManiaControl\Utils\WebReader;

/**
 * PHP Unit Test for Plugin Update Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PluginUpdateManagerTest extends \PHPUnit_Framework_TestCase {
	public function testWebReaderAndPluginsWebservice() {
		$url      = ManiaControl::URL_WEBSERVICE . 'plugins';
		$response = WebReader::getUrl($url);
		$dataJson = $response->getContent();

		$this->assertJson($dataJson);

		$data = json_decode($dataJson);

		$this->assertEquals(8, $data[0]->id);
		$this->assertEquals("https://download.maniacontrol.com/plugins/8_Dedimania_Plugin_v0.1.zip", $data[0]->currentVersion->url);
	}

	public function testGetPluginUpdates() {
		$maniaControl        = new ManiaControl();
		$updateManager       = $maniaControl->getUpdateManager();
		$pluginUpdateManager = $updateManager->getPluginUpdateManager();

		//No Plugins Running so No new Updates
		$this->assertFalse($pluginUpdateManager->getPluginsUpdates());

		$maniaControl->run(10);

		//$this->assertNotFalse($pluginUpdateManager->getPluginsUpdates()); //failed manchmal
		//TODO load Plugin manually and then test (could happen that no update is existing)
	}


}
