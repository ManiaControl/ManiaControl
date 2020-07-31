<?php

namespace ManiaControl\Admin;

use ManiaControl\Commands\CommandListener;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Class offering Commands to grant Authorizations to Players
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AuthCommands implements CommandListener, UsageInformationAble {
	use UsageInformationTrait;

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new AuthCommands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Commands
		$this->maniaControl->getCommandManager()->registerCommandListener('addsuperadmin', $this, 'command_AddSuperAdmin', true, 'Add Player to the AdminList as SuperAdmin.');
		$this->maniaControl->getCommandManager()->registerCommandListener('addadmin', $this, 'command_AddAdmin', true, 'Add Player to the AdminList as Admin.');
		$this->maniaControl->getCommandManager()->registerCommandListener('addmod', $this, 'command_AddModerator', true, 'Add Player to the AdminList as Moderator.');

		$this->maniaControl->getCommandManager()->registerCommandListener('removerights', $this, 'command_RemoveRights', true, 'Remove Player from the AdminList.');
	}

	/**
	 * Handle all //add commands
	 * @internal
	 * @param array $chatCallback
	 * @param Player $player
	 * @param string $targetAuthLevel
	 */
	private function command_Add(array $chatCallback, Player $player, $targetAuthLevel) {
		// $player needs to be at least one AuthLevel higher as the one to be granted
		if (!AuthenticationManager::checkRight($player, $targetAuthLevel+1)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$text         = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Usage Example: %s %s',
				$commandParts[0],
				'login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($commandParts[1]);
		if (!$target) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Player %s not found!',
				$commandParts[1]
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, $targetAuthLevel);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred!', $player);
			return;
		}

		$authName = AuthenticationManager::getAuthLevelName($targetAuthLevel);
		$message = $this->maniaControl->getChat()->formatMessage(
			"%s added %s as {$authName}.",
			$player,
			$target
		);
		$this->maniaControl->getChat()->sendSuccess($message);
	}

	/**
	 * Handle //addsuperadmin command
	 *
	 * @internal
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddSuperAdmin(array $chatCallback, Player $player) {
		$this->command_Add($chatCallback, $player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
	}

	/**
	 * Handle //addadmin command
	 *
	 * @internal
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddAdmin(array $chatCallback, Player $player) {
		$this->command_Add($chatCallback, $player, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * Handle //addmod command
	 *
	 * @internal
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_AddModerator(array $chatCallback, Player $player) {
		$this->command_Add($chatCallback, $player, AuthenticationManager::AUTH_LEVEL_MODERATOR);
	}

	/**
	 * Handle //removerights command
	 *
	 * @internal
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_RemoveRights(array $chatCallback, Player $player) {
		$text         = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Usage Example: %s %s',
				$commandParts[0],
				'login'
			);
			$this->maniaControl->getChat()->sendUsageInfo($message, $player);
			return;
		}

		$target = $this->maniaControl->getPlayerManager()->getPlayer($commandParts[1]);
		if (!$target) {
			$message = $this->maniaControl->getChat()->formatMessage(
				'Player %s not found!',
				$commandParts[1]
			);
			$this->maniaControl->getChat()->sendError($message, $player);
			return;
		}

		if ($target->authLevel >= AuthenticationManager::AUTH_LEVEL_MASTERADMIN) {
			$this->maniaControl->getChat()->sendError('You cannot remove rights of a MasterAdmin!', $player);
			return;
		}

		if ($target->authLevel <= AuthenticationManager::AUTH_LEVEL_PLAYER) {
			$this->maniaControl->getChat()->sendError('Cannot remove rights of a player!', $player);
			return;
		}

		if ($player->authLevel <= $target->authLevel) {
			$this->maniaControl->getChat()->sendError('You cannot remove rights of a higher privileged player!', $player);
			return;
		}

		$targetAuthName = $target->getAuthLevelName();
		$success = $this->maniaControl->getAuthenticationManager()->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_PLAYER);
		if (!$success) {
			$this->maniaControl->getChat()->sendError('Error occurred!', $player);
			return;
		}

		$message = $this->maniaControl->getChat()->formatMessage(
			"%s removed %s from {$targetAuthName}s.",
			$player,
			$target
		);
		$this->maniaControl->getChat()->sendSuccess($message);
	}
}
