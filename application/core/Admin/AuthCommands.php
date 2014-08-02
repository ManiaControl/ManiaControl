<?php

namespace ManiaControl\Admin;

use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Class offering Commands to grant Authorizations to Players
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AuthCommands implements CommandListener {
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Create a new AuthCommands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('addsuperadmin', $this, 'command_AddSuperAdmin', true, 'Add Player to the AdminList as SuperAdmin.');
		$this->maniaControl->commandManager->registerCommandListener('addadmin', $this, 'command_AddAdmin', true, 'Add Player to the AdminList as Admin.');
		$this->maniaControl->commandManager->registerCommandListener('addmod', $this, 'command_AddModerator', true, 'Add Player to the AdminList as Moderator.');
	}

	/**
	 * Handle //addsuperadmin command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddSuperAdmin(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text         = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$this->sendAddSuperAdminUsageInfo($player);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($commandParts[1]);
		if (!$target) {
			$this->maniaControl->chat->sendError("Player '{$commandParts[1]}' not found!", $player->login);
			return;
		}
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as SuperAdmin!';
		$this->maniaControl->chat->sendSuccess($message);
	}

	/**
	 * Send usage example for //addsuperadmin command
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function sendAddSuperAdminUsageInfo(Player $player) {
		$message = "Usage Example: '//addsuperadmin login'";
		return $this->maniaControl->chat->sendUsageInfo($message, $player->login);
	}

	/**
	 * Handle //addadmin command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddAdmin(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text         = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$this->sendAddAdminUsageInfo($player);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($commandParts[1]);
		if (!$target) {
			$this->maniaControl->chat->sendError("Player '{$commandParts[1]}' not found!", $player->login);
			return;
		}
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_ADMIN);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as Admin!';
		$this->maniaControl->chat->sendSuccess($message);
	}

	/**
	 * Send usage example for //addadmin command
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function sendAddAdminUsageInfo(Player $player) {
		$message = "Usage Example: '//addadmin login'";
		return $this->maniaControl->chat->sendUsageInfo($message, $player->login);
	}

	/**
	 * Handle //addmod command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddModerator(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text         = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$this->sendAddModeratorUsageInfo($player);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($commandParts[1]);
		if (!$target) {
			$this->maniaControl->chat->sendError("Player '{$commandParts[1]}' not found!", $player->login);
			return;
		}
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_MODERATOR);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as Moderator!';
		$this->maniaControl->chat->sendSuccess($message);
	}

	/**
	 * Send usage example for //addmod command
	 *
	 * @param Player $player
	 * @return bool
	 */
	private function sendAddModeratorUsageInfo(Player $player) {
		$message = "Usage Example: '//addmod login'";
		return $this->maniaControl->chat->sendUsageInfo($message, $player->login);
	}
}
