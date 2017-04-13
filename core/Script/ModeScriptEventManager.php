<?php

namespace ManiaControl\Script;

use ManiaControl\Callbacks\Callbacks;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;

/**
 * Manager for Mode Script Events
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
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
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getCallbacksList() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_CALLBACKSLIST, $responseId);
	}

	/**
	 * Provide a Array of Callbacks you want to Block
	 *
	 * @api
	 * @param array $callbackNames
	 */
	public function blockCallbacks($callbackNames) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.BlockCallbacks', $callbackNames);
	}

	/**
	 * Block a Single Callback
	 *
	 * @api
	 * @param $callbackName
	 */
	public function blockCallback($callbackName) {
		$this->blockCallbacks(array($callbackName));
	}

	/**
	 * Provide a Array of Callbacks you want to Block
	 *
	 * @api
	 * @param array $callbackNames
	 */
	public function unBlockCallbacks($callbackNames) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.UnblockCallbacks', $callbackNames);
	}

	/**
	 * Block a Single Callback
	 *
	 * @api
	 * @param $callbackName
	 */
	public function unBlockCallback($callbackName) {
		$this->unBlockCallbacks(array($callbackName));
	}

	/**
	 * Request a list of all enabled callbacks. This method will trigger the "XmlRpc.CallbacksList_Enabled" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getListOfEnabledCallbacks() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Enabled', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_ENABLEDCALLBACKS, $responseId);
	}

	/**
	 * Request a list of all disabled callbacks. This method will trigger the "XmlRpc.CallbacksList_Enabled" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getListOfDisabledCallbacks() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Disabled', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_DISABLEDCALLBACKS, $responseId);
	}

	/**
	 * Description: Request help about a callback. This method will trigger the "XmlRpc.CallbackHelp" callback.
	 *
	 * @api
	 * @param        $callbackName
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getCallbackHelp($callbackName) {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbackHelp', array($callbackName, $responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_CALLBACKHELP, $responseId);
	}


	/**
	 * Request a list of all available methods. This method will trigger the "XmlRpc.MethodsList" callback.s
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getMethodsList() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetMethodsList', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_METHODSLIST, $responseId);
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
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getApiVersion() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetApiVersion', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_APIVERSION, $responseId);
	}

	/**
	 * Request help about a method. This method will trigger the "XmlRpc.MethodHelp" callback.
	 *
	 * @api
	 * @param        $methodName
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getMethodHelp($methodName) {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetMethodHelp', array($methodName, $responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_METHODHELP, $responseId);
	}

	/**
	 * Request the current game mode xmlrpc callbacks and methods documentation. This method will trigger the "XmlRpc.Documentation" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getDocumentation() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetDocumentation', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_DOCUMENTATION, $responseId);
	}

	/**
	 * Gets a List of All Api Version
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getAllApiVersions() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetAllApiVersions', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_ALLAPIVERSIONS, $responseId);
	}


	/**
	 * Extend the duration of any ongoing warmup.
	 *
	 * @api
	 * @param $milisec < the duration of the extension in milliseconds.
	 */
	public function extendManiaPlanetWarmup($milisec) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.Extend', array($milisec));
	}

	/**
	 *  Stop any ongoing warmup.
	 *
	 * @api
	 */
	public function stopManiaPlanetWarmup() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.Stop');
	}

	/**
	 * Blocks the End of the Warmup,
	 *
	 * @param int $time Timer before the end of the warmup when all players are ready. Use a negative value to prevent the warmup from ending even if all players are ready.
	 */
	public function blockEndWarmUp($time = -1) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.BlockEndWarmUp', array(true, $time));
	}

	/**
	 * Blocks the End of the Warmup,
	 *
	 * @param int $time Timer before the end of the warmup when all players are ready. Use a negative value to prevent the warmup from ending even if all players are ready.
	 */
	public function unBlockEndWarmUp($time = -1) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.BlockEndWarmUp', array(false, $time));
	}

	/**
	 * Get the status of the warmup.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getWarmupStatus() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.GetStatus', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::MP_WARMUP_STATUS, $responseId);
	}

	/**
	 * Get the status of the pause.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getPauseStatus() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.Pause.GetStatus', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::MP_PAUSE_STATUS, $responseId);
	}

	/**
	 * Start a Pause and triggers a Callback for the Pause Status
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback To get The Pause Status You can directly set a callable on it via setCallable()
	 */
	public function startPause() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.Pause.SetActive', array(true, $responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::MP_PAUSE_STATUS, $responseId);
	}

	/**
	 * End a Pause and triggers a Callback for the Pause Status
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback To get The Pause Status You can directly set a callable on it via setCallable()
	 */
	public function endPause() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.Pause.SetActive', array(false, $responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::MP_PAUSE_STATUS, $responseId);
	}

	/**
	 * Returns if the GameMode is a TeamMode or not
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback To get The TeamMode Status You can directly set a callable on it via setCallable()
	 */
	public function isTeamMode() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.Mode.GetUseTeams', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::MP_USES_TEAMMODE, $responseId);
	}

	/**
	 * Move the spectators' timers UI.
	 *
	 * @api
	 * @param $x
	 * @param $y
	 * @param $z
	 */
	public function comboSetTimerPosition($x, $y, $z) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.Combo.SetTimersPosition', array(strval(floatval($x)), strval(floatval($y)), strval(floatval($z))));
	}

	/**
	 * Move the progression UI.
	 *
	 * @api
	 * @param $x
	 * @param $y
	 * @param $z
	 */
	public function suegeSetProgressionUIPosition($x, $y, $z) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.Siege.SetProgressionUIPosition', array(strval(floatval($x)), strval(floatval($y)), strval(floatval($z))));
	}


	/**
	 * Request the current scores. This method will trigger the "Shootmania.Scores" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getShootmaniaScores() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.GetScores', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::SM_SCORES, $responseId);
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getShootmaniaUIProperties() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.UI.GetProperties', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::SM_UIPROPERTIES, $responseId);
	}

	/**
	 * Update the ui properties.
	 *
	 * @api
	 * @param string Json-Encoded Xml UI Property String
	 */
	public function setShootmaniaUIProperties($properties) {
		$this->maniaControl->getClient()->triggerModeScriptEvent(' Shootmania.UI.SetProperties', array($properties));
	}

	/**
	 * Request the current scores. This method will trigger the "Trackmania.Scores" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getTrackmaniaScores() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetScores', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::TM_SCORES, $responseId);
	}

	/**
	 * Request the current points repartition. This method will trigger the "Trackmania.PointsRepartition" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getTrackmaniaPointsRepartition() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetPointsRepartition', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::TM_POINTSREPARTITION, $responseId);
	}

	/**
	 * Update the points repartition.
	 *
	 * @api
	 * @param array String Array of Points
	 */
	public function setTrackmaniaPointsRepartition($pointArray) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetPointsRepartition', array($pointArray));
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getTrackmaniaUIProperties() {
		$responseId = $this->generateResponseId();
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.UI.SetProperties', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::TM_SCORES, $responseId);
	}

	/**
	 * Update the ui properties.
	 *
	 * @api
	 * @param string Json-Encoded Xml UI Property String
	 */
	public function setTrackmaniaUIProperties($properties) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.UI.GetProperties', array($properties));
	}

	/**
	 * Stop the whole warm up sequence.
	 *
	 * @api
	 */
	public function stopTrackmaniaWarmup() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.WarmUp.Stop');
	}

	/**
	 * Stop the current warm up round.
	 *
	 * @api
	 */
	public function stopTrackmaniaRound() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.WarmUp.StopRound');
	}


	/**
	 * Stop the current round. Only available in Cup, Rounds and Team modes.
	 *
	 * @api
	 */
	public function forceTrackmaniaRoundEnd() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.ForceEndRound');
	}

	/**
	 * Generates the needed Unique ResponseId
	 *
	 * @return string
	 */
	private function generateResponseId() {
		return uniqid("ManiaControl.");
	}
}