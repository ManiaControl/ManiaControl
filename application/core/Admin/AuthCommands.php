<?php

namespace ManiaControl\Admin;

use ManiaControl\ManiaControl;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Players\Player;

/**
 * Class offering commands to grant authorizations to players
 *
 * @author steeffeen & kremsy
 */
class AuthCommands implements CommandListener {
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new AuthCommands instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for commands
		$this->maniaControl->commandManager->registerCommandListener('addsuperadmin', $this, 'command_AddSuperAdmin',true);
		$this->maniaControl->commandManager->registerCommandListener('addadmin', $this, 'command_AddAdmin',true);
		$this->maniaControl->commandManager->registerCommandListener('addop', $this, 'command_AddOperator',true);
	}

	/**
	 * Handle //addsuperadmin command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_AddSuperAdmin(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_MASTERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text = $chatCallback[1][2];
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
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as SuperAdmin!';
		$this->maniaControl->chat->sendSuccess($message);
	}

	/**
	 * Handle //addadmin command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_AddAdmin(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_SUPERADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text = $chatCallback[1][2];
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
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($player, AuthenticationManager::AUTH_LEVEL_ADMIN);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as Admin!';
		$this->maniaControl->chat->sendSuccess($message);
	}

	/**
	 * Handle //addop command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_AddOperator(array $chatCallback, Player $player) {
		if (!AuthenticationManager::checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$text = $chatCallback[1][2];
		$commandParts = explode(' ', $text);
		if (!array_key_exists(1, $commandParts)) {
			$this->sendAddOperatorUsageInfo($player);
			return;
		}
		$target = $this->maniaControl->playerManager->getPlayer($commandParts[1]);
		if (!$target) {
			$this->maniaControl->chat->sendError("Player '{$commandParts[1]}' not found!", $player->login);
			return;
		}
		$success = $this->maniaControl->authenticationManager->grantAuthLevel($player, AuthenticationManager::AUTH_LEVEL_OPERATOR);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred.', $player->login);
			return;
		}
		$message = '$<' . $player->nickname . '$> added $<' . $target->nickname . '$> as Operator!';
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
	 * Send usage example for //addop command
	 *
	 * @param Player $player        	
	 * @return bool
	 */
	private function sendAddOperatorUsageInfo(Player $player) {
		$message = "Usage Example: '//addop login'";
		return $this->maniaControl->chat->sendUsageInfo($message, $player->login);
	}
}

?>
