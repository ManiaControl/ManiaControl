<?php

namespace ManiaControl\Script;

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
	 * @param string $responseId
	 */
	public function getCallbacksList($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.GetCallbacksList', array($responseId));
	}

	/**
	 * Provide a Array of Callbacks you want to Block
	 *
	 * @param array $callbackNames
	 */
	public function blockCallbacks($callbackNames) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.BlockCallbacks', $callbackNames);
	}

	/**
	 * Block a Single Callback
	 *
	 * @param $callbackName
	 */
	public function blockCallback($callbackName) {
		$this->blockCallbacks(array($callbackName));
	}

	/**
	 * Provide a Array of Callbacks you want to Block
	 *
	 * @param array $callbackNames
	 */
	public function unBlockCallbacks($callbackNames) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('XmlRpc.UnblockCallbacks', $callbackNames);
	}

	/**
	 * Block a Single Callback
	 *
	 * @param $callbackName
	 */
	public function unBlockCallback($callbackName) {
		$this->unBlockCallbacks(array($callbackName));
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
	 * Extend the duration of any ongoing warmup.
	 *
	 * @param $milisec < the duration of the extension in milliseconds.
	 */
	public function extendManiaPlanetWarmup($milisec) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.Extend', array($milisec));
	}

	/**
	 *  Stop any ongoing warmup.
	 */
	public function stopManiaPlanetWarmup() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.Stop');
	}

	/**
	 * Get the status of the warmup.
	 *
	 * @param string $responseId
	 */
	public function getWarmupStatus($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Maniaplanet.WarmUp.GetStatus', array($responseId));
	}

	/**
	 * Get the status of the pause.
	 *
	 * @param string $responseId
	 */
	public function getComboPauseStatus($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.Combo.GetPause', array($responseId));
	}

	/**
	 * Start a Pause in Combo
	 *
	 * @param string $responseId
	 */
	public function startComboPause($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.Combo.SetPause', array(true, $responseId));
	}

	/**
	 * End a Pause in Combo
	 *
	 * @param string $responseId
	 */
	public function endComboPause($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.Combo.SetPause', array(false, $responseId));
	}

	/**
	 * Move the spectators' timers UI.
	 *
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

	/**
	 * Request the current scores. This method will trigger the "Trackmania.Scores" callback.
	 *
	 * @param string $responseId
	 */
	public function getTrackmaniaScores($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetScores', array($responseId));
	}

	/**
	 * Request the current points repartition. This method will trigger the "Trackmania.PointsRepartition" callback.
	 *
	 * @param string $responseId
	 */
	public function getTrackmaniaPointsRepartition($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetPointsRepartition', array($responseId));
	}

	/**
	 * Update the points repartition.
	 *
	 * @param array String Array of Points
	 */
	public function setTrackmaniaPointsRepartition($pointArray) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetPointsRepartition', array($pointArray));
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @param string $responseId
	 */
	public function getTrackmaniaUIProperties($responseId = "DefaultResponseId") {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.GetUIProperties', array($responseId));
	}

	/**
	 * Update the ui properties.
	 *
	 * @param string Json-Encoded Xml UI Property String
	 */
	public function setTrackmaniaUIProperties($properties) {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Shootmania.GetUIProperties', array($properties));
	}

	/**
	 * Stop the whole warm up sequence.
	 */
	public function stopTrackmaniaWarmup() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.WarmUp.Stop');
	}

	/**
	 * Stop the current warm up round.
	 */
	public function stopTrackmaniaRound() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.WarmUp.StopRound');
	}


	/**
	 * Stop the current round. Only available in Cup, Rounds and Team modes.
	 */
	public function forceTrackmaniaRoundEnd() {
		$this->maniaControl->getClient()->triggerModeScriptEvent('Trackmania.ForceEndRound');
	}
}