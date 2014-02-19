<?php

namespace ManiaControl\Server;

use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;

/**
 * Class offering various commands related to the dedicated server
 *
 * @author steeffeen & kremsy
 */
class ServerCommands implements CallbackListener, CommandListener, ManialinkPageAnswerListener, TimerListener {
	/**
	 * Constants
	 */
	const ACTION_SET_PAUSE                         = 'ServerCommands.SetPause';
	const ACTION_EXTEND_WARMUP                     = 'ServerCommands.ExtendWarmup';
	const ACTION_END_WARMUP                        = 'ServerCommands.EndWarmup';
	const ACTION_CANCEL_VOTE                       = 'ServerCommands.CancelVote';
	const CB_VOTE_CANCELED                         = 'ServerCommands.VoteCanceled';
	const SETTING_PERMISSION_CANCEL_VOTE           = 'Cancel Vote';
	const SETTING_PERMISSION_SET_PAUSE             = 'Set Pause';
	const SETTING_PERMISSION_HANDLE_WARMUP         = 'Handle Warmup';
	const SETTING_PERMISSION_SHOW_SYSTEMINFO       = 'Show SystemInfo';
	const SETTING_PERMISSION_SHUTDOWN_SERVER       = 'Shutdown Server';
	const SETTING_PERMISSION_CHANGE_SERVERSETTINGS = 'Change ServerSettings';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $serverShutdownTime = -1;
	private $serverShutdownEmpty = false;

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->timerManager->registerTimerListening($this, 'each5Seconds', 5000);
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'handleOnInit');

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('setpwd', $this, 'command_SetPwd', true);
		$this->maniaControl->commandManager->registerCommandListener('setservername', $this, 'command_SetServerName', true);
		$this->maniaControl->commandManager->registerCommandListener('setmaxplayers', $this, 'command_SetMaxPlayers', true);
		$this->maniaControl->commandManager->registerCommandListener('setmaxspectators', $this, 'command_SetMaxSpectators', true);
		$this->maniaControl->commandManager->registerCommandListener('setspecpwd', $this, 'command_SetSpecPwd', true);
		$this->maniaControl->commandManager->registerCommandListener('shutdownserver', $this, 'command_ShutdownServer', true);
		$this->maniaControl->commandManager->registerCommandListener('systeminfo', $this, 'command_SystemInfo', true);

		$this->maniaControl->commandManager->registerCommandListener('cancel', $this, 'command_CancelVote', true);
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SET_PAUSE, $this, 'setPause');
	}

	/**
	 * Set Menu items on init
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		//Define Permissions
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SHUTDOWN_SERVER, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SHOW_SYSTEMINFO, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_SET_PAUSE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CANCEL_VOTE, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_HANDLE_WARMUP, AuthenticationManager::AUTH_LEVEL_MODERATOR);

		// Extend WarmUp
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_EXTEND_WARMUP, $this, 'command_extendWarmup');
		$itemQuad = new Quad_BgRaceScore2();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_SendScore);
		$itemQuad->setAction(self::ACTION_EXTEND_WARMUP);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 14, 'Extend Warmup');

		// Stop WarmUp
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_END_WARMUP, $this, 'command_endWarmup');
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowGreen);
		$itemQuad->setAction(self::ACTION_END_WARMUP);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 15, 'End Warmup');

		// Action cancel Vote
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CANCEL_VOTE, $this, 'command_cancelVote');
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowRed);
		$itemQuad->setAction(self::ACTION_CANCEL_VOTE);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 30, 'Cancel Vote');

		//Check if Pause exists in current GameMode
		try {
			$scriptInfos = $this->maniaControl->client->getModeScriptInfo();
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				return;
			}
			throw $e;
		}
		$pauseExists = false;
		foreach($scriptInfos->commandDescs as $param) {
			if ($param->name == "Command_ForceWarmUp") {
				$pauseExists = true;
				break;
			}
		}

		// Set Pause
		if ($pauseExists) {
			$itemQuad = new Quad_Icons128x32_1();
			$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ManiaLinkSwitch);
			$itemQuad->setAction(self::ACTION_SET_PAUSE);
			$this->maniaControl->actionsMenu->addAdminMenuItem($itemQuad, 13, 'Pauses the current game');
		}
	}


	/**
	 * Handle //cancelvote command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_CancelVote(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CANCEL_VOTE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->cancelVote();

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> canceled the Vote!');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_VOTE_CANCELED, array(self::CB_VOTE_CANCELED, $player));
	}


	/**
	 * Extends the Warmup
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function command_extendWarmup(array $callback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_HANDLE_WARMUP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		try {
			$this->maniaControl->client->triggerModeScriptEvent('WarmUp_Extend', '10');
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
				return;
			}
			throw $e;
		}

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> extended the WarmUp by 10 seconds!');
	}

	/**
	 * Ends the Warmup
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function command_endWarmup(array $callback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_HANDLE_WARMUP)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		try {
			$this->maniaControl->client->triggerModeScriptEvent('WarmUp_Stop', '');
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
				return;
			}
			throw $e;
		}

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> stopped the WarmUp!');
	}

	/**
	 * Breaks the current game
	 *
	 * @param array $callback
	 */
	public function setPause(array $callback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_SET_PAUSE)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		try {
			$this->maniaControl->client->sendModeScriptCommands(array('Command_ForceWarmUp' => True));
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
				return;
			}
			throw $e;
		}

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> paused the Game!');
	}

	/**
	 * Check stuff each 5 seconds
	 *
	 * @param $timer
	 * @return bool
	 */
	public function each5Seconds($timer) {
		// Empty shutdown
		if ($this->serverShutdownEmpty) {
			$players = $this->maniaControl->playerManager->getPlayers();
			if (count($players) <= 0) {
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
	 * Handle //systeminfo command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_SystemInfo(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_SHOW_SYSTEMINFO)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$systemInfo = $this->maniaControl->client->getSystemInfo();
		$message    = 'SystemInfo: ip=' . $systemInfo->publishedIp . ', port=' . $systemInfo->port . ', p2pPort=' . $systemInfo->p2PPort . ', title=' . $systemInfo->titleId . ', login=' . $systemInfo->serverLogin . '.';
		$this->maniaControl->chat->sendInformation($message, $player->login);
	}

	/**
	 * Handle //shutdownserver command
	 *
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ShutdownServer(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_SHUTDOWN_SERVER)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Check for delayed shutdown
		$params = explode(' ', $chat[1][2]);
		if (count($params) >= 2) {
			$param = $params[1];
			if ($param == 'empty') {
				$this->serverShutdownEmpty = !$this->serverShutdownEmpty;
				if ($this->serverShutdownEmpty) {
					$this->maniaControl->chat->sendInformation("The server will shutdown as soon as it's empty!", $player->login);
					return;
				}
				$this->maniaControl->chat->sendInformation("Empty-shutdown cancelled!", $player->login);
				return;
			}
			$delay = (int)$param;
			if ($delay <= 0) {
				// Cancel shutdown
				$this->serverShutdownTime = -1;
				$this->maniaControl->chat->sendInformation("Delayed shutdown cancelled!", $player->login);
				return;
			}
			// Trigger delayed shutdown
			$this->serverShutdownTime = time() + $delay * 60.;
			$this->maniaControl->chat->sendInformation("The server will shut down in {$delay} minutes!", $player->login);
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
	public function command_SetServerName(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2], 2);
		if (count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setservername ManiaPlanet Server', $player->login);
			return;
		}
		$serverName = $params[1];
		$this->maniaControl->client->setServerName($serverName);
		$this->maniaControl->chat->sendSuccess("Server name changed to: '{$serverName}'!", $player->login);
	}

	/**
	 * Handle //setpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Password removed!';
		if (isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Password changed to: '{$password}'!";
		}
		$this->maniaControl->client->setServerPassword($password);
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
	}

	/**
	 * Handle //setspecpwd command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetSpecPwd(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts   = explode(' ', $chatCallback[1][2], 2);
		$password       = '';
		$successMessage = 'Spectator password removed!';
		if (isset($messageParts[1])) {
			$password       = $messageParts[1];
			$successMessage = "Spectator password changed to: '{$password}'!";
		}
		$this->maniaControl->client->setServerPasswordForSpectator($password);
		$this->maniaControl->chat->sendSuccess($successMessage, $player->login);
	}

	/**
	 * Handle //setmaxplayers command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetMaxPlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxplayers 16', $player->login);
			return;
		}
		$amount = (int)$amount;
		if ($amount < 0) {
			$amount = 0;
		}

		$this->maniaControl->client->setMaxPlayers($amount);
		$this->maniaControl->chat->sendSuccess("Changed max players to: {$amount}", $player->login);
	}

	/**
	 * Handle //setmaxspectators command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_SetMaxSpectators(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVERSETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$messageParts = explode(' ', $chatCallback[1][2], 2);
		if (!isset($messageParts[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return;
		}
		$amount = $messageParts[1];
		if (!is_numeric($amount)) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //setmaxspectators 16', $player->login);
			return;
		}
		$amount = (int)$amount;
		if ($amount < 0) {
			$amount = 0;
		}

		$this->maniaControl->client->setMaxSpectators($amount);
		$this->maniaControl->chat->sendSuccess("Changed max spectators to: {$amount}", $player->login);
	}

	/**
	 * Perform server shutdown
	 *
	 * @param string $login
	 */
	private function shutdownServer($login = '#') {
		$this->maniaControl->client->stopServer();
		$this->maniaControl->quit("Server shutdown requested by '{$login}'");
	}
}
