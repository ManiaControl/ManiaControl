<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 15. Apr. 2017
 * Time: 22:44
 */

namespace Tests\core\Update;

use ManiaControl\ManiaControl;
use ManiaControl\Update\PluginUpdateManager;
use ManiaControl\Utils\WebReader;

class PluginUpdateManagerTest extends \PHPUnit_Framework_TestCase {
	public function testGetPluginUpdates(){
		$maniaControl  = new ManiaControl();
		$updateManager = $maniaControl->getUpdateManager();
		$pluginUpdateManager = $updateManager->getPluginUpdateManager();

		var_dump($pluginUpdateManager->getPluginsUpdates());

		$url        = ManiaControl::URL_WEBSERVICE . 'plugins';
		$response   = WebReader::getUrl($url);
		$dataJson   = $response->getContent();
		var_dump($dataJson);
	}
}
