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

	/**
	 * Create MapCommands instance
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		$this->maniaControl->commandManager->registerCommandListener('nextmap', $this, 'command_NextMap', true);
		$this->maniaControl->commandManager->registerCommandListener('restartmap', $this, 'command_RestartMap', true);
		$this->maniaControl->commandManager->registerCommandListener('addmap', $this, 'command_AddMap', true);
		$this->maniaControl->commandManager->registerCommandListener('removemap', $this, 'command_RemoveMap', true);
		
		// Register for chat commands
		$this->maniaControl->commandManager->registerCommandListener('list', $this, 'command_List');
		$this->maniaControl->commandManager->registerCommandListener('maps', $this, 'command_List');
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
		// Check if ManiaControl can even write to the maps dir
		if (!$this->maniaControl->client->query('GetMapsDirectory')) {
			trigger_error("Couldn't get map directory. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("ManiaControl couldn't retrieve the maps directory.", $player->login);
			return;
		}
		$mapDir = $this->maniaControl->client->getResponse();
		if (!is_dir($mapDir)) {
			trigger_error("ManiaControl doesn't have have access to the maps directory in '{$mapDir}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have access to the maps directory.", $player->login);
			return;
		}
		$downloadDirectory = $this->maniaControl->settingManager->getSetting($this, 'MapDownloadDirectory', 'MX');
		// Create download directory if necessary
		if (!is_dir($mapDir . $downloadDirectory) && !mkdir($mapDir . $downloadDirectory)) {
			trigger_error("ManiaControl doesn't have to rights to save maps in '{$mapDir}{$downloadDirectory}'.");
			$this->maniaControl->chat->sendError("ManiaControl doesn't have the rights to save maps.", $player->login);
			return;
		}
		$mapDir .= $downloadDirectory . '/';
		// Download the map
		$mapId = $params[1];
		if (is_numeric($mapId)) {
			// Load from MX
			$serverInfo = $this->maniaControl->server->getSystemInfo();
			$title = strtolower(substr($serverInfo['TitleId'], 0, 2));
			// Check if map exists
			$url = "http://{$title}.mania-exchange.com/api/tracks/get_track_info/id/{$mapId}?format=json";
			$mapInfo = FileUtil::loadFile($url);
			if (!$mapInfo || strlen($mapInfo) <= 0) {
				// Invalid id
				$this->maniaControl->chat->sendError('Invalid MX-Id!', $player->login);
				return;
			}
			$mapInfo = json_decode($mapInfo, true);
			$url = "http://{$title}.mania-exchange.com/tracks/download/{$mapId}";
			$file = FileUtil::loadFile($url);
			if (!$file) {
				// Download error
				$this->maniaControl->chat->sendError('Download failed!', $player->login);
				return;
			}
			// Save map
			$fileName = $mapInfo['TrackID'] . '_' . $mapInfo['Name'] . '.Map.Gbx';
			$fileName = FileUtil::getClearedFileName($fileName);
			if (!file_put_contents($mapDir . $fileName, $file)) {
				// Save error
				$this->maniaControl->chat->sendError('Saving map failed!', $player->login);
				return;
			}
			// Check for valid map
			$mapFileName = $downloadDirectory . '/' . $fileName;
			if (!$this->maniaControl->client->query('CheckMapForCurrentServerParams', $mapFileName)) {
				trigger_error("Couldn't check if map is valid ('{$mapFileName}'). " . $this->maniaControl->getClientErrorText());
				$this->maniaControl->chat->sendError('Error checking map!', $player->login);
				return;
			}
			$response = $this->maniaControl->client->getResponse();
			if (!$response) {
				// Invalid map type
				$this->maniaControl->chat->sendError("Invalid map type.", $player->login);
				return;
			}
			// Add map to map list
			if (!$this->maniaControl->client->query('InsertMap', $mapFileName)) {
				$this->maniaControl->chat->sendError("Couldn't add map to match settings!", $player->login);
				return;
			}
			$this->maniaControl->chat->sendSuccess('Map $<' . $mapInfo['Name'] . '$> added!');
			return;
		}
		// TODO: add local map by filename
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
		// var_dump($chatCallback);
		$mapList = new MapList($this->maniaControl);
		$mapList->showMapList($player);
	}
}
