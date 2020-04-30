<?php

namespace ManiaControl\Script;

use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Structures\XmlRpc\CallbackListStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\DocumentationStructure;
use ManiaControl\Callbacks\Structures\XmlRpc\MethodListStructure;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Manager for Mode Script Events
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ModeScriptEventManager implements UsageInformationAble {
	use UsageInformationTrait;

	const API_VERSION = "2.5.0";

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
		$this->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('true'));

		$this->setApiVersion(self::API_VERSION);
		$this->unBlockAllCallbacks();
	}

	/**
	 * Disables XmlRpc Callbacks
	 */
	public function disableCallbacks() {
		$this->triggerModeScriptEvent('XmlRpc.EnableCallbacks', array('false'));
	}


	/**
	 * Request a list of all available callbacks. This method will trigger the "XmlRpc.CallbacksList" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getCallbacksList() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('XmlRpc.GetCallbacksList', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_CALLBACKSLIST, $responseId);
	}

	/**
	 * Prints the List of XMLRPC Callbacks in the Console
	 *
	 * @api
	 */
	public function printCallbacksList() {
		$this->getCallbacksList()->setCallable(function (CallbackListStructure $structure) {
			var_dump($structure->getCallbacks());
		});
	}

	/**
	 * Provide a Array of Callbacks you want to Block
	 *
	 * @api
	 * @param array $callbackNames
	 */
	public function blockCallbacks($callbackNames) {
		$this->triggerModeScriptEvent('XmlRpc.BlockCallbacks', $callbackNames);
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
	 * UnBlocks All Callbacks
	 *
	 * @api
	 */
	public function unBlockAllCallbacks() {
		$this->getListOfDisabledCallbacks()->setCallable(function (CallbackListStructure $structure) {
			$this->unBlockCallbacks($structure->getCallbacks());
		});
	}

	/**
	 * Provide a Array of Callbacks you want to UnBlock
	 *
	 * @api
	 * @param array $callbackNames
	 */
	public function unBlockCallbacks($callbackNames) {
		$this->triggerModeScriptEvent('XmlRpc.UnblockCallbacks', $callbackNames);
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
		$this->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Enabled', array($responseId));
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
		$this->triggerModeScriptEvent('XmlRpc.GetCallbacksList_Disabled', array($responseId));
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
		$this->triggerModeScriptEvent('XmlRpc.GetCallbackHelp', array($callbackName, $responseId));
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
		$this->triggerModeScriptEvent('XmlRpc.GetMethodsList', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_METHODSLIST, $responseId);
	}

	/**
	 * Prints the List of XMLRPC Methods in the Console
	 *
	 * @api
	 */
	public function printMethodsList() {
		$this->getMethodsList()->setCallable(function (MethodListStructure $structure) {
			var_dump($structure->getMethods());
		});
	}

	/**
	 * Sets the Api Version
	 *
	 * @param string $version
	 */
	public function setApiVersion($version = self::API_VERSION) {
		$this->triggerModeScriptEvent('XmlRpc.SetApiVersion', array($version));
	}

	/**
	 * Gets the Api Version
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getApiVersion() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('XmlRpc.GetApiVersion', array($responseId));
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
		$this->triggerModeScriptEvent('XmlRpc.GetMethodHelp', array($methodName, $responseId));
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
		$this->triggerModeScriptEvent('XmlRpc.GetDocumentation', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_DOCUMENTATION, $responseId);
	}

	/**
	 *  Printes the XMLRPC Documentation in the Console
	 *
	 * @api
	 */
	public function printDocumentation() {
		$this->getDocumentation()->setCallable(function (DocumentationStructure $structure) {
			var_dump($structure->getDocumentation());
		});
	}

	/**
	 * Gets a List of All Api Version
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getAllApiVersions() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('XmlRpc.GetAllApiVersions', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::XMLRPC_ALLAPIVERSIONS, $responseId);
	}

	/**
	 * Hides the Scoreboard on Pressing Alt
	 *
	 * @api
	 * @param \ManiaControl\Players\Player $player
	 */
	public function hideScoreBoardOnAlt(Player $player) {
		$login = Player::parseLogin($player);
		$this->triggerModeScriptEvent('Maniaplanet.UI.SetAltScoresTableVisibility', array($login, "False"));
	}

	/**
	 * Displays the Scoreboard on Pressing Alt
	 *
	 * @api
	 * @param \ManiaControl\Players\Player $player
	 */
	public function displayScoreBoardOnAlt(Player $player) {
		$login = Player::parseLogin($player);
		$this->triggerModeScriptEvent('Maniaplanet.UI.SetAltScoresTableVisibility', array($login, "True"));
	}

	/**
	 * Hides the Scoreboard
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function hideScoreBoard(Player $player) {
		$login = Player::parseLogin($player);
		$this->triggerModeScriptEvent('Maniaplanet.UI.SetScoresTableVisibility', array($login, "False"));
	}

	/**
	 * Displays the Scoreboard
	 *
	 * @param \ManiaControl\Players\Player $player
	 */
	public function displayScoreBoard(Player $player) {
		$login = Player::parseLogin($player);
		$this->triggerModeScriptEvent('Maniaplanet.UI.SetScoresTableVisibility', array($login, "True"));
	}

	/**
	 * Extend the duration of any ongoing warmup.
	 *
	 * @api
	 * @param $seconds < the duration of the extension in seconds.
	 */
	public function extendManiaPlanetWarmup($seconds) {
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.Extend', array(strval($seconds * 1000)));
	}

	/**
	 *  Stop any ongoing warmup.
	 *
	 * @api
	 */
	public function stopManiaPlanetWarmup() {
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.Stop');
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.ForceStop');
	}

	/**
	 * Blocks the End of the Warmup,
	 *
	 * @param int $time Timer before the end of the warmup when all players are ready. Use a negative value to prevent the warmup from ending even if all players are ready.
	 */
	public function blockEndWarmUp($time = -1) {
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.BlockEndWarmUp', array("True", strval($time)));
	}

	/**
	 * Blocks the End of the Warmup,
	 *
	 * @param int $time Timer before the end of the warmup when all players are ready. Use a negative value to prevent the warmup from ending even if all players are ready.
	 */
	public function unBlockEndWarmUp($time = -1) {
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.BlockEndWarmUp', array("False", strval($time)));
	}

	/**
	 * Get the status of the warmup.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getWarmupStatus() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('Maniaplanet.WarmUp.GetStatus', array($responseId));
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
		$this->triggerModeScriptEvent('Maniaplanet.Pause.GetStatus', array($responseId));
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
		$this->triggerModeScriptEvent('Maniaplanet.Pause.SetActive', array("True", $responseId));
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
		$this->triggerModeScriptEvent('Maniaplanet.Pause.SetActive', array("False", $responseId));
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
		$this->triggerModeScriptEvent('Maniaplanet.Mode.GetUseTeams', array($responseId));
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
	public function setComboTimerPosition($x, $y, $z) {
		$this->triggerModeScriptEvent('Shootmania.Combo.SetTimersPosition', array(strval(floatval($x)), strval(floatval($y)), strval(floatval($z))));
	}

	/**
	 * Move the progression UI.
	 *
	 * @api
	 * @param $x
	 * @param $y
	 * @param $z
	 */
	public function setSiegeProgressionUIPosition($x, $y, $z) {
		$this->triggerModeScriptEvent('Shootmania.Siege.SetProgressionUIPosition', array(strval(floatval($x)), strval(floatval($y)), strval(floatval($z))));
	}


	/**
	 * Request the current scores. This method will trigger the "Shootmania.Scores" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getShootmaniaScores() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('Shootmania.GetScores', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::SM_SCORES, $responseId);
	}

	/**
	 * Request the current properties of the AFK libraries.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getShootmaniaAFKProperties() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent(' Shootmania.AFK.GetProperties', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::SM_AFKPROPERTIES, $responseId);
	}

	/**
	 * Set the properties of the AFK library.
	 *
	 * @api
	 * @param int $idleTimeLimit
	 * @param int $spawnTimeLimit
	 * @param int $checkInterval
	 * @param int $forceSpec
	 */
	public function setShootmaniaAFKProperties($idleTimeLimit, $spawnTimeLimit, $checkInterval, $forceSpec) {
		$this->triggerModeScriptEvent('Shootmania.AFK.SetProperties', array(strval($idleTimeLimit), strval($spawnTimeLimit), strval($checkInterval), strval($forceSpec)));
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getShootmaniaUIProperties() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('Shootmania.UI.GetProperties', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::SM_UIPROPERTIES, $responseId);
	}

	/**
	 * Update the ui properties.
	 *
	 * @api
	 * @param string Json-Encoded Xml UI Property String
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable() to get the updated Properties
	 */
	public function setShootmaniaUIProperties($properties) {
		$this->triggerModeScriptEvent('Shootmania.UI.SetProperties', array($properties));
		return $this->getShootmaniaUIProperties();
	}

	/**
	 * Request the current scores. This method will trigger the "Trackmania.Scores" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getTrackmaniaScores() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('Trackmania.GetScores', array($responseId));
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
		$this->triggerModeScriptEvent('Trackmania.GetPointsRepartition', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::TM_POINTSREPARTITION, $responseId);
	}

	/**
	 * Update the points repartition.
	 *
	 * @api
	 * @param array String Array of Points
	 */
	public function setTrackmaniaPointsRepartition($pointArray) {
		$this->triggerModeScriptEvent('Trackmania.SetPointsRepartition', $pointArray);
	}

	/**
	 * Sets the Trackmania Player Points
	 *
	 * @param \ManiaControl\Players\Player $player
	 * @param string|int                   $roundPoints //< The round points, use an empty string to not update.
	 * @param string|int                   $mapPoints   //< The map points, use an empty string to not update.
	 * @param string|int                   $matchPoints //< The match points, use an empty string to not update.
	 */
	public function setTrackmaniaPlayerPoints(Player $player, $roundPoints = "", $mapPoints = "", $matchPoints = "") {
		$login = Player::parseLogin($player);
		$this->triggerModeScriptEvent('Trackmania.SetPlayerPoints', array($login, strval($roundPoints), strval($mapPoints), strval($matchPoints)));
	}

	/**
	 * Sets the Trackmania Team Points
	 *
	 * @param int        $teamId //< Id of the team t. Can be 1 or 2.
	 * @param string|int $roundPoints
	 * @param string|int $mapPoints
	 * @param string|int $matchPoints
	 */
	public function setTrackmaniaTeamPoints($teamId, $roundPoints = "", $mapPoints = "", $matchPoints = "") {
		$this->triggerModeScriptEvent('Trackmania.SetTeamPoints', array(strval($teamId), strval($roundPoints), strval($mapPoints), strval($matchPoints)));
	}

	/**
	 * Request the current ui properties. This method will trigger the "Shootmania.UIProperties" callback.
	 *
	 * @api
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable()
	 */
	public function getTrackmaniaUIProperties() {
		$responseId = $this->generateResponseId();
		$this->triggerModeScriptEvent('Trackmania.UI.GetProperties', array($responseId));
		return new InvokeScriptCallback($this->maniaControl, Callbacks::TM_UIPROPERTIES, $responseId);
	}

	/**
	 * Update the ui properties.
	 *
	 * @api
	 * @param string Json-Encoded Xml UI Property String
	 * @return \ManiaControl\Script\InvokeScriptCallback You can directly set a callable on it via setCallable() to get the updated Properties
	 */
	public function setTrackmaniaUIProperties($properties) {
		$this->triggerModeScriptEvent('Trackmania.UI.SetProperties', array($properties));
		return $this->getTrackmaniaUIProperties();
	}

	/**
	 * Stop the whole warm up sequence.
	 *
	 * @api
	 */
	public function stopTrackmaniaWarmup() {
		$this->triggerModeScriptEvent('Trackmania.WarmUp.Stop');
	}

	/**
	 * Stop the current warm up round.
	 *
	 * @api
	 */
	public function stopTrackmaniaRound() {
		$this->triggerModeScriptEvent('Trackmania.WarmUp.StopRound');
	}


	/**
	 * Stop the current round. Only available in Cup, Rounds and Team modes.
	 *
	 * @api
	 */
	public function forceTrackmaniaRoundEnd() {
		$this->triggerModeScriptEvent('Trackmania.ForceEndRound');
	}

	/**
	 * Triggers a ModeScript Event
	 *
	 * @api
	 * @param        $eventName
	 * @param array  $data
	 */
	public function triggerModeScriptEvent($eventName, $data = array()) {
		$this->maniaControl->getClient()->triggerModeScriptEvent($eventName, $data, function ($exception) use ($eventName) {
			if ($exception instanceof GameModeException) {
				if ($exception->getMessage() != 'Not in script mode.') {
					throw $exception;
				}
				Logger::logWarning($eventName . " can't be triggered because you are not in Scriptmode, start your server in Scriptmode!");
			}
		});
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