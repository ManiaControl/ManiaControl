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

		$this->getCallbacksList();
	}

	/**
	 * Disables XmlRpc Callbacks
	 */
	public function disableCallbacks() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('false'));
	}

	/**
	 * Request a list of all available callbacks. This method will trigger the "XmlRpc.CallbacksList" callback.
	 *
	 * @param string $responseId
	 */
	public function getCallbacksList($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList', array($responseId));
	}

	/**
	 * Request a list of all enabled callbacks. This method will trigger the "XmlRpc.CallbacksList_Enabled" callback.
	 *
	 * @param string $responseId
	 */
	public function getListOfEnabledCallbacks($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Enabled', array($responseId));
	}

	/**
	 * Request a list of all disabled callbacks. This method will trigger the "XmlRpc.CallbacksList_Enabled" callback.
	 *
	 * @param string $responseId
	 */
	public function getListOfDisabledCallbacks($responseId) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Disabled', array($responseId));
	}

	/**
	 * Description: Request help about a callback. This method will trigger the "XmlRpc.CallbackHelp" callback.
	 *
	 * @param        $callbackName
	 * @param string $responseId
	 */
	public function getCallbackHelp($callbackName, $responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbackHelp', array($callbackName, $responseId));
	}


	/**
	 * Request a list of all available methods. This method will trigger the "XmlRpc.MethodsList" callback.s
	 *
	 * @param string $responseId
	 */
	public function getMethodsList($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetMethodsList', array($responseId));
	}

	/**
	 * Sets the Api Version
	 *
	 * @param string $version
	 */
	public function setApiVersion($version = self::API_VERSION) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.SetApiVersion', array($version));
	}

	/**
	 * Gets the Api Version
	 *
	 * @param string $version
	 */
	public function getApiVersion($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetApiVersion', array($responseId));
	}

	/**
	 * Request help about a method. This method will trigger the "XmlRpc.MethodHelp" callback.
	 *
	 * @param        $callbackName
	 * @param string $responseId
	 */
	public function getMethodHelp($methodName, $responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetMethodHelp', array($methodName, $responseId));
	}

	/**
	 * Request the current game mode xmlrpc callbacks and methods documentation. This method will trigger the "XmlRpc.Documentation" callback.
	 *
	 * @param string $responseId
	 */
	public function getDocumentation($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetDocumentation', array($responseId));
	}

	/**
	 * Gets a List of All Api Version
	 *
	 * @param string $responseId
	 */
	public function getAllApiVersions($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetAllApiVersions', array($responseId));
	}

	/**
	 * Request the current scores. This method will trigger the "Shootmania.Scores" callback.
	 *
	 * @param string $responseId
	 */
	public function getShootmaniaScores($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.GetScores', array($responseId));
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @param string $responseId
	 */
	public function getShootmaniaUIProperties($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.GetUIProperties', array($responseId));
	}

	/**
	 * Update the ui properties.
	 *
	 * @param string Json-Encoded Xml UI Property String
	 */
	public function setShootmaniaUIProperties($properties) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.GetUIProperties', array($properties));
	}
}