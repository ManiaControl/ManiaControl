<?php

namespace ManiaControl\Players;

use FML\Controls\Quad;
use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Commands\CommandListener;

/**
 * Class offering various admin commands related to players
 *
 * @author steeffeen & kremsy
 */
class PlayerCommands implements CommandListener {
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $playerList = null;
	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Register for admin commands
		$this->maniaControl->commandManager->registerCommandListener('teambalance', $this, 'command_TeamBalance',true);
		$this->maniaControl->commandManager->registerCommandListener('autoteambalance', $this, 'command_TeamBalance',true);
		$this->maniaControl->commandManager->registerCommandListener('kick', $this, 'command_Kick',true);
		$this->maniaControl->commandManager->registerCommandListener('forcespec', $this, 'command_ForceSpectator',true);
		$this->maniaControl->commandManager->registerCommandListener('forcespectator', $this, 'command_ForceSpectator',true);
		$this->maniaControl->commandManager->registerCommandListener('forceplay', $this, 'command_ForcePlayer',true);
		$this->maniaControl->commandManager->registerCommandListener('forceplayer', $this, 'command_ForcePlayer',true);
		$this->maniaControl->commandManager->registerCommandListener('addbot', $this, 'command_AddFakePlayers',true);
		$this->maniaControl->commandManager->registerCommandListener('removebot', $this, 'command_RemoveFakePlayers',true);
		$this->maniaControl->commandManager->registerCommandListener('addbots', $this, 'command_AddFakePlayers',true);
		$this->maniaControl->commandManager->registerCommandListener('removebots', $this, 'command_RemoveFakePlayers',true);

		// Register for player chat commands
		$this->maniaControl->commandManager->registerCommandListener('player', $this, 'command_playerList');
		$this->maniaControl->commandManager->registerCommandListener('players', $this, 'command_playerList');

		$this->playerList = new PlayerList($this->maniaControl);

	}

	/**
	 * Handle //teambalance command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_TeamBalance(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$success = $this->maniaControl->client->query('AutoTeamBalance');
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> balanced Teams!');
	}

	/**
	 * Handle //kick command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 */
	public function command_Kick(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2], 3);
		if (!isset($params[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //kick login', $player->login);
			return;
		}
		$target = $params[1];
		$target = $this->maniaControl->playerManager->getPlayer($target);
		if (!$target) {
			$this->maniaControl->chat->sendError("Invalid player login.", $player->login);
			return;
		}
		$message = '';
		if (isset($params[2])) {
			$message = $params[2];
		}

		$this->maniaControl->playerManager->playerActions->kickPlayer($player->login, $target, $message);
	}

	/**
	 * Handle //forcespec command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 */
	public function command_ForceSpectator(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2]);
		if (!isset($params[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //forcespec login', $player->login);
			return;
		}
		$target = $params[1];
		$target = $this->maniaControl->playerManager->getPlayer($target);
		if (!$target) {
			$this->maniaControl->chat->sendError("Invalid player login.", $player->login);
			return;
		}
		$type = 3;
		if (isset($params[2]) && is_numeric($params[2])) {
			$type = intval($params[2]);
		}
		$success = $this->maniaControl->client->query('ForceSpectator', $target->login, $type);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		if ($type != 1) {
			$this->maniaControl->client->query('ForceSpectator', $target->login, 0);
		}
		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> forced $<' . $target->nickname . '$> to spectator!');
	}

	/**
	 * Handle //forceplay command
	 *
	 * @param array $chat        	
	 * @param Player $player        	
	 */
	public function command_ForcePlayer(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$params = explode(' ', $chat[1][2]);
		if (!isset($params[1])) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //forceplay login', $player->login);
			return;
		}
		$target = $params[1];
		$target = $this->maniaControl->playerManager->getPlayer($target);
		if (!$target) {
			$this->maniaControl->chat->sendError("Invalid player login.", $player->login);
			return;
		}
		$type = 2;
		if (isset($params[2]) && is_numeric($params[2])) {
			$type = intval($params[2]);
		}
		$success = $this->maniaControl->client->query('ForceSpectator', $target->login, 2);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		if ($type != 1) {
			$this->maniaControl->client->query('ForceSpectator', $target->login, 0);
		}
		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> forced $<' . $target->nickname . '$> to player!');
	}

	/**
	 * Handle //addfakeplayers command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_AddFakePlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$amount = 1;
		$messageParts = explode(' ', $chatCallback[1][2]);
		if (isset($messageParts[1]) && is_numeric($messageParts[1])) {
			$amount = intval($messageParts[1]);
		}
		$success = true;
		for ($i = 0; $i < $amount; $i++) {
			if (!$this->maniaControl->client->query('ConnectFakePlayer')) {
				$success = false;
			}
		}
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Fake players connected!', $player->login);
	}

	/**
	 * Handle //removefakeplayers command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_RemoveFakePlayers(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$success = $this->maniaControl->client->query('DisconnectFakePlayer', '*');
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Fake players disconnected!', $player->login);
	}

	/**
	 * Handle Player list command
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_playerList(array $chatCallback, Player $player) {
			$this->playerList->addPlayerToShownList($player, PlayerList::SHOWN_MAIN_WINDOW);
			$this->playerList->showPlayerList($player);
	}
}
