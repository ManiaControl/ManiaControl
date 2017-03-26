<?php
/**
 * Manager for Mode Script Events
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */

namespace ManiaControl\Script;

use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

class ModeScriptEventManager implements UsageInformationAble {
	use UsageInformationTrait;

	const API_VERSION = "2.0.0";

	/** @var ManiaControl $maniaControl */
	private $maniaControl;

	/**
	 * Construct a new ranking manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Enables XmlRpc Callbacks
	 */
	public function enableCallbacks() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('true'));

		$this->setApiVersion(self::API_VERSION);

		$this->getAllApiVersions();

		$this->getCallbacksList(); //TODO verify why this does not work
	}

	/**
	 * Disables XmlRpc Callbacks
	 */
	public function disableCallbacks() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('false'));
	}

	/**
	 * @param string $responseId
	 * Triggers a Callback List Callback
	 */
	public function getCallbacksList($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList', array($responseId));
	}

	/**
	 * Sets the Api Version
	 *
	 * @param string $version
	 */
	public function setApiVersion($version = self::API_VERSION) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.SetApiVersion', array($version));
	}

	public function getAllApiVersions($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetAllApiVersions', array($responseId));
	}
}