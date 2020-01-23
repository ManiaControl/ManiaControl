<?php

namespace Tests\core\Script;

use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Structures\XmlRpc\AllApiVersionsStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\ApiVersionStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbackHelpStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbackListStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\DocumentationStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\MethodHelpStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\MethodListStructure;
use ManiaControl\ManiaControl;
use ManiaControl\Script\ModeScriptEventManager;

/**
 * PHP Unit Test for Mode Script Event Manager Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ModeScriptEventManagerTest extends \PHPUnit_Framework_TestCase {
	public function testEnableCallbacks() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getApiVersion()->setCallable(function (ApiVersionStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			$this->assertEquals(ModeScriptEventManager::API_VERSION, $structure->getVersion());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);

		//Disable Callbacks and Test Again
		$maniaControl->connect();
		$modeScriptEventManager->disableCallbacks();

		$called = false;
		$modeScriptEventManager->getApiVersion()->setCallable(function (ApiVersionStructure $structure) use (&$called) {
			$called = true;
		});

		$maniaControl->run(3);

		$this->assertFalse($called);
	}

	public function testGetCallbacksList() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getCallbacksList()->setCallable(function (CallbackListStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			//At least 5 Callbacks existing
			$this->assertArrayHasKey(5, $structure->getCallbacks());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}

	public function testBlockUnBlockCallbacks() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called1 = false;
		$modeScriptEventManager->getListOfEnabledCallbacks()->setCallable(function (CallbackListStructure $structure) use (&$called1) {
			$called1 = true;
			$this->assertJson($structure->toJson());
			//At least 5 Callbacks existing
			$this->assertArrayHasKey(5, $structure->getCallbacks());
		});

		$called2 = false;
		$modeScriptEventManager->getListOfDisabledCallbacks()->setCallable(function (CallbackListStructure $structure) use (&$called2) {
			$called2 = true;
			$this->assertJson($structure->toJson());
			//Has No Callback
			$this->assertEmpty($structure->getCallbacks());
		});

		//Block a Callback
		$modeScriptEventManager->blockCallback(Callbacks::MP_ENDPLAYLOOP);

		$called3 = false;
		$modeScriptEventManager->getListOfDisabledCallbacks()->setCallable(function (CallbackListStructure $structure) use (&$called3) {
			$called3 = true;
			$this->assertJson($structure->toJson());
			$structure->getCallbacks();
			//Has No Callback
			$this->assertArrayHasKey(0, $structure->getCallbacks());
			$this->assertEquals(Callbacks::MP_ENDPLAYLOOP, $structure->getCallbacks()[0]);
		});

		$modeScriptEventManager->unBlockCallback(Callbacks::MP_ENDPLAYLOOP);

		$called4 = false;
		$modeScriptEventManager->getListOfDisabledCallbacks()->setCallable(function (CallbackListStructure $structure) use (&$called4) {
			$called4 = true;
			$this->assertJson($structure->toJson());
			//Has No Callback
			$this->assertEmpty($structure->getCallbacks());
		});

		$maniaControl->run(5);

		$this->assertTrue($called1);
		$this->assertTrue($called2);
		$this->assertTrue($called3);
		$this->assertTrue($called4);
	}

	public function testGetCallbackHelp() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getCallbackHelp(Callbacks::SM_ONCAPTURE)->setCallable(function (CallbackHelpStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			$this->assertEquals(Callbacks::SM_ONCAPTURE, $structure->getCallbackName());
			$this->assertContains($structure->getCallbackName(), $structure->getDocumentation());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}

	public function testGetMethodsList() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getMethodsList()->setCallable(function (MethodListStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			//Has at Least 10 Methods
			$this->assertArrayHasKey(10, $structure->getMethods());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}

	public function testGetMethodHelp() {
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getMethodHelp('XmlRpc.GetMethodHelp')->setCallable(function (MethodHelpStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			$this->assertEquals('XmlRpc.GetMethodHelp', $structure->getMethodName());
			$this->assertContains("Name", $structure->getDocumentation());
			$this->assertContains("XmlRpc.GetMethodHelp", $structure->getDocumentation());
			$this->assertContains("Version", $structure->getDocumentation());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}

	public function testGetDocumentation(){
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getDocumentation()->setCallable(function (DocumentationStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());
			$this->assertContains("Name", $structure->getDocumentation());
			$this->assertContains("XmlRpc.GetMethodHelp", $structure->getDocumentation());
			$this->assertContains("Version", $structure->getDocumentation());
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}

	public function testGetAllApiVersions(){
		$maniaControl = new ManiaControl();
		$maniaControl->connect();

		$modeScriptEventManager = $maniaControl->getModeScriptEventManager();
		$modeScriptEventManager->enableCallbacks();

		$called = false;
		$modeScriptEventManager->getAllApiVersions()->setCallable(function (AllApiVersionsStructure $structure) use (&$called) {
			$called = true;
			$this->assertJson($structure->toJson());

			$this->assertArrayHasKey(0, $structure->getVersions());
			$this->assertContains(".", $structure->getLatest()); // like 2.0.0 (just check if a dot is in)
		});

		$maniaControl->run(3);

		$this->assertTrue($called);
	}
}
