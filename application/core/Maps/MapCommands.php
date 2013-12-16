<?php

namespace ManiaControl\Maps;

use ManiaControl\ManiaControl;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\FileUtil;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;

/**
 * Class offering commands to manage maps
 *
 * @author steeffeen & kremsy
 */
class MapCommands implements CommandListener {
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $mapList = null;

	/**
	 * Create MapCommands instance
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for admin chat commands
		$this->maniaControl->commandManager->registerCommandListener('nextmap', $this, 'command_NextMap', true);
		$this->maniaControl->commandManager->registerCommandListener('restartmap', $this, 'command_RestartMap', true);
		$this->maniaControl->commandManager->registerCommandListener('addmap', $this, 'command_AddMap', true);
		$this->maniaControl->commandManager->registerCommandListener('removemap', $this, 'command_RemoveMap', true);
		
		// Register for player chat commands
		$this->maniaControl->commandManager->registerCommandListener('xlist', $this, 'command_xList');
		$this->maniaControl->commandManager->registerCommandListener('list', $this, 'command_List');
		$this->maniaControl->commandManager->registerCommandListener('maps', $this, 'command_List');

		$this->mapList = new MapList($this->maniaControl);
	}

	/**
	 * Handle removemap command
	 *
	 * @param array $chat        	
	 * @param \ManiaControl\Players\Player $player        	
	 */
	public function command_RemoveMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Get map
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if (!$map) {
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return;
		}
		// Remove map
		if (!$this->maniaControl->client->query('RemoveMap', $map->fileName)) {
			trigger_error("Couldn't remove current map. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Map removed.', $player->login);
	}

	/**
	 * Handle addmap command
	 *
	 * @param array $chatCallback        	
	 * @param \ManiaControl\Players\Player $player        	
	 */
	public function command_AddMap(array $chatCallback, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// TODO: user mx fetcher
		$params = explode(' ', $chatCallback[1][2], 2);
		if (count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //addmap 1234', $player->login);
			return;
		}

		//add Map from Mania Exchange
		$this->maniaControl->mapManager->addMapFromMx($params[1],$player->login);
	}

	/**
	 * Handle nextmap command
	 *
	 * @param array $chat        	
	 * @param \ManiaControl\Players\Player $player        	
	 */
	public function command_NextMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->query('NextMap');
	}

	/**
	 * Handle restartmap command
	 *
	 * @param array $chat        	
	 * @param \ManiaControl\Players\Player $player        	
	 */
	public function command_RestartMap(array $chat, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->query('RestartMap');
	}

	/**
	 * Handle list maps command
	 *
	 * @param array $chatCallback        	
	 * @param Player $player        	
	 */
	public function command_List(array $chatCallback, Player $player) {
		$this->mapList->showMapList($player);
	}

	/**
	 * Handle ManiaExchange list command
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_xList(array $chatCallback, Player $player) {
		$this->mapList->showManiaExchangeList($chatCallback, $player);
	}
}
