<?php

namespace ManiaControl\Server;

use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\Structures\Common\StatusCallbackStructure;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Class offering various Commands related to the Dedicated Server
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Commands implements CallbackListener, CommandListener, ManialinkPageAnswerListener, TimerListener {
	/*
	 * Constants
	 */
	const ACTION_SET_PAUSE                         = 'ServerCommands.SetPause';
	const ACTION_EXTEND_WARMUP                     = 'ServerCommands.ExtendWarmup';
	const ACTION_END_WARMUP                        = 'ServerCommands.EndWarmup';
	const ACTION_CANCEL_VOTE                       = 'ServerCommands.CancelVote';
	const CB_VOTE_CANCELLED                        = 'ServerCommands.VoteCancelled';
	const SETTING_PERMISSION_CANCEL_VOTE           = 'Cancel Vote';
	const SETTING_PERMISSION_SET_PAUSE             = 'Set Pause';
	const SETTING_PERMISSION_HANDLE_WARMUP         = 'Handle Warmup';
	const SETTING_PERMISSION_SHOW_SYSTEMINFO       = 'Show SystemInfo';
	const SETTING_PERMISSION_SHUTDOWN_SERVER       = 'Shutdown Server';
	const SETTING_PERMISSION_CHANGE_SERVERSETTINGS = 'Change ServerSettings';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl        = null;
	private $serverShutdownTime  = -1;
	private $serverShutdownEmpty = false;

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getTimerManager()->registerTimerListening($this, 'each5Seconds', 5000);
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_WARMUP_STATUS, $this, 'handleWarmUpStatus');

		// Chat commands
		$this->maniaControl->getCommandManager()->registerCommandListener('setservername', $this, 'commandSetServerName', true, 'Sets the ServerName.');
		$this->maniaControl->getCommandManager()->registerCommandListener('setpwd', $this, 'commandSetPwd', true, 'Sets play password.');
		$this->maniaControl->getCommandManager()->registerCommandListener('setspecpwd', $this, 'commandSetSpecPwd', true, 'Sets spectator password.');
		$this->maniaControl->getCommandManager()->registerCommandListener('setmaxplayers', $this, 'commandSetMaxPlayers', true, 'Sets the maximum number of players.');
		$this->maniaControl->getCommandManager()->registerCommandListener('setmaxspectators', $this, 'commandSetMaxSpectators', true, 'Sets the maximum number of spectators.');
		$this->maniaControl->getCommandManager()->registerCommandListener('shutdownserver', $this, 'commandShutdownServer', true, 'Shuts down the ManiaPlanet server.');
		$this->maniaControl->getCommandManager()->registerCommandListener('systeminfo', $this, 'commandSystemInfo', true, 'Shows system information.');
		$this->maniaControl->getCommandManager()->registerCommandListener('cancel', $this, 'commandCancelVote', true, 'Cancels the current vote.');

		// Page actions
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_SET_PAUSE, $this, 'setPause');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_EXTEND_WARMUP, $this, 'commandExtendWarmup');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_END_WARMUP, $this, 'commandEndWarmup');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_CANCEL_VOTE, $this, 'commandCancelVote');
	}

	/**
	 * Handle ManiaControl OnInit Callback
	 */
	public function handleOnInit() {
		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SHUTDOWN_SERVER, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SHOW_SYSTEMINFO, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_SET_PAUSE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CANCEL_VOTE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_HANDLE_WARMUP, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		//Triggers a WarmUp Status Callback
		$this->maniaControl->getModeScriptEventManager()->getWarmupStatus();

		$this->updateCancelVoteMenuItem();
		$this->updateWarmUpMenuItems();
	}

	/**
	 * Add the cancel vote menu item
	 */
	private function updateCancelVoteMenuItem() {
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowRed);
		$itemQuad->setAction(self::ACTION_CANCEL_VOTE);
		$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, false, 30, 'Cancel Vote');
	}

	/**
	 * Manage the WarmUp related menu items
	 */
	private function updateWarmUpMenuItems() {
		// Add pause menu item
		if ($this->maniaControl->getServer()->getScriptManager()->modeUsesPause()) {
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ManiaLinkSwitch);
			$itemQuad->setAction(self::ACTION_SET_PAUSE);
			$this->maniaControl->getActionsMenu()->addAdminMenuItem($itemQuad, 32, 'Pause the current game');
		}

	}

	/**
	 * Handle the WarmupStatus Callback, and removes or adds the Menu Items for extending / Stopping warmup
	 *
	 * @param \ManiaControl\Callbacks\Structures\Common\StatusCallbackStructure $structure
	 */
	public function handleWarmUpStatus(StatusCallbackStructure $structure) {
		if ($structure->isAvailable()) {
			// Extend WarmUp menu item
			$itemQuad = new Quad_BgRaceScore2();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_SendScore);
			$itemQuad->setAction(self::ACTION_EXTEND_WARMUP);
			$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, false, 22, 'Extend Warmup');

			// Stop WarmUp menu item
			$itemQuad = new Quad_Icons64x64_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowGreen);
			$itemQuad->setAction(self::ACTION_END_WARMUP);
			$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, false, 24, 'End Warmup');
		} else {
			$this->maniaControl->getActionsMenu()->removeMenuItem(14, false);
			$this->maniaControl->getActionsMenu()->removeMenuItem(15, false);
		}
	}

	/**
	 * Handle //cancelvote command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandCancelVote(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CANCEL_VOTE)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		if ($this->maniaControl->getClient()->cancelVote()) {
			$this->maniaControl->getChat()->sendInformation($player->getEscapedNickname() . ' cancelled the Vote!');
		} else {
			$this->maniaControl->getChat()->sendInformation("There's no vote running currently!", $player);
		}

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_VOTE_CANCELLED, $player);
	}


	/**
	 * Extend the WarmUp
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function commandExtendWarmup(array $callback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_HANDLE_WARMUP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		//TODO command paprameter for seconds
		$this->maniaControl->getModeScriptEventManager()->extendManiaPlanetWarmup(10);
		$this->maniaControl->getChat()->sendInformation($player->getEscapedNickname() . ' extended the WarmUp by 10 seconds!');

	}

	/**
	 * End the WarmUp
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function commandEndWarmup(array $callback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_HANDLE_WARMUP)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->maniaControl->getModeScriptEventManager()->stopManiaPlanetWarmup();
		$this->maniaControl->getChat()->sendInformation($player->getEscapedNickname() . ' stopped the WarmUp!');
	}

	/**
	 * Pause the current game
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function setPause(array $callback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_SET_PAUSE)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		//Normal Gamemodes
		try {
			$this->maniaControl->getClient()->sendModeScriptCommands(array('Command_ForceWarmUp' => true));
			$this->maniaControl->getChat()->sendInformation($player->getEscapedNickname() . ' paused the Game!');
		} catch (GameModeException $e) {
		}

		try {
			//Chase and Combo?
			$this->maniaControl->getClient()->sendModeScriptCommands(array('Command_SetPause' => true));
			$this->maniaControl->getChat()->sendInformation($player->getEscapedNickname() . ' paused the Game!');

			//Especially for chase, force end of the round to reach a draw
			$this->maniaControl->getClient()->sendModeScriptCommands(array('Command_ForceEndRound' => true));
		} catch (GameModeException $ex) {
		}

		//TODO verify if not everything is replaced through the new pause
		$this->maniaControl->getModeScriptEventManager()->startPause();
		$this->maniaControl->getChat()->sendInformation('$f8fVote to $fffpause the current Game$f8f has been successful!');

	}

	/**
	 * Check Stuff each 5 Seconds
	 */
	public function each5Seconds() {
		// TODO: move empty & delayed shutdown code into server class
		// Empty shutdown
		if ($this->serverShutdownEmpty) {
			if ($this->maniaControl->getPlayerManager()->getPlayerCount(false) <= 0) {
				$this->shutdownServer('empty');
			}
		}

		// Delayed shutdown
		if ($this->serverShutdownTime > 0) {
			if (time() >= $this->serverShutdownTime) {
				$this->shutdownServer('delayed');
			}
		}
	}

	/**
	 * Perform server shutdown
	 *
	 * @param string $login
	 */
	private function shutdownServer($login = '-') {
		Logger::logInfo("Server shutdown requested by '{$login}'!");
		$this->maniaControl->getClient()->stopServer();
	}

	/**
	 * Handle //systeminfo command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function commandSystemInfo(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_SHOW_SYSTEMINFO)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$systemInfo = $this->maniaControl->getClient()->getSystemInfo();
		$message    = 'SystemInfo: ip=' . $systemInfo->publishedIp . ', port=' . $systemInfo->port . ', p2pPort=' . $systemInfo->p2PPort . ', title=' . $systemInfo->titleId . ', login=' . $systemInfo->serverLogin . '.';
		$this->maniaControl->getChat()->sendInformation($message, $player->login);
	}

	/**
	 * Handle //shutdownserver command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function commandShutdownServer(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_SHUTDOWN_SERVER)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		// Check for delayed shutdown
		$params = explode(' ', $chat[1][2]);
		if (count($params) >= 2) {
			$param = $params[1];
			if (strtolower($param) === 'empty') {
				$this->serverShutdownEmpty = !$this->serverShutdownEmpty;
				if ($this->serverShutdownEmpty) {
					$this->maniaControl->getChat()->sendInformation("The server will shutdown as soon as it's empty!", $player);
					return;
				}
				$this->maniaControl->getChat()->sendInformation("Empty-shutdown cancelled!", $player);
				return;
			}
			$delay = (int) $param;
			if ($delay <= 0) {
				// Cancel shutdown
				$this->serverShutdownTime = -1;
				$this->maniaControl->getChat()->sendInformation("Delayed shutdown cancelled!", $player);
				return;
			}
			// Trigger delayed shutdown
			$this->serverShutdownTime = time() + $delay * 60.;
			$this->maniaControl->getChat()->sendInformation("The server will shut down in {$delay} minutes!", $player);
			return;
		}
		$this->shutdownServer($player->login);
	}

	/**
	 * Handle //setservername command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function commandSetServerName(array $chat, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //setservername ManiaPlanet Server', $player);
			return;
		}
		$serverName = $params[1];
		$this->maniaControl->getClient()->setServerName($serverName);
		$this->maniaControl->getChat()->sendSuccess("Server name changed to: '{$serverName}'!", $player);
	}

	/**
	 * Handle //setpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandSetPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Password removed!';
		if (isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Password changed to: '{$password}'!";
		}
		$this->maniaControl->getClient()->setServerPassword($password);
		$this->maniaControl->getChat()->sendSuccess($successMessage, $player);
	}

	/**
	 * Handle //setspecpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandSetSpecPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Spectator password removed!';
		if (isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Spectator password changed to: '{$password}'!";
		}
		$this->maniaControl->getClient()->setServerPasswordForSpectator($password);
		$this->maniaControl->getChat()->sendSuccess($successMessage, $player);
	}

	/**
	 * Handle //setmaxplayers command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandSetMaxPlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //setmaxplayers 16', $player);
			return;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //setmaxplayers 16', $player);
			return;
		}
		$amount = (int) $amount;
		if ($amount < 0) {
			$amount = 0;
		}

		$this->maniaControl->getClient()->setMaxPlayers($amount);
		$this->maniaControl->getChat()->sendSuccess("Changed max players to: {$amount}", $player);
	}

	/**
	 * Handle //setmaxspectators command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function commandSetMaxSpectators(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //setmaxspectators 16', $player);
			return;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->getChat()->sendUsageInfo('Usage example: //setmaxspectators 16', $player);
			return;
		}
		$amount = (int) $amount;
		if ($amount < 0) {
			$amount = 0;
		}

		$this->maniaControl->getClient()->setMaxSpectators($amount);
		$this->maniaControl->getChat()->sendSuccess("Changed max spectators to: {$amount}", $player);
	}
}
