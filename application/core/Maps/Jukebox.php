<?php

namespace ManiaControl\Maps;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Jukebox Class
 *
 * @author steeffeen & kremsy
 */
class Jukebox implements CallbackListener, CommandListener {
	/**
	 * Constants
	 */
	const CB_JUKEBOX_CHANGED =  'Jukebox.JukeBoxChanged';
	const SETTING_SKIP_MAP_ON_LEAVE = 'Skip Map when the requester leaves';
	const SETTING_SKIP_JUKED_ADMIN = 'Skip Map when admin leaves';

	const ADMIN_COMMAND_CLEAR_JUKEBOX = 'clearjukebox';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $jukedMaps = array();

	/**
	 * Create a new server jukebox
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ENDMAP, $this,'endMap');

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SKIP_MAP_ON_LEAVE, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SKIP_JUKED_ADMIN, false);

		//Register Admin Commands
		$this->maniaControl->commandManager->registerCommandListener(self::ADMIN_COMMAND_CLEAR_JUKEBOX, $this, 'command_ClearJukebox', true);
	}

	/**
	 * Clears the jukebox via admin command clearjukebox
	 * @param array  $chat
	 * @param Player $player
	 */
	public function command_ClearJukebox(array $chat, Player $admin){
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		//Destroy jukebox list
		$this->jukedMaps = array();

		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> cleared the Jukebox!');
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' cleared the Jukebox');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('clear'));
	}

	/**
	 * Adds a Map to the jukebox
	 * @param $login
	 * @param $uid
	 */
	public function addMapToJukebox($login, $uid){
		$player = $this->maniaControl->playerManager->getPlayer($login);

		//Check if the map is already juked
		if(array_key_exists($uid, $this->jukedMaps)){
			$this->maniaControl->chat->sendError('Map is already in the Jukebox', $login);
			return;
		}

		//TODO recently maps not able to add to jukebox setting, and management

		$map = $this->maniaControl->mapManager->getMapByUid($uid);

		$this->jukedMaps[$uid] = array($player, $map);

		$this->maniaControl->chat->sendInformation('$<' . $player->nickname . '$> added $<' . $map->name . '$> to the Jukebox!');

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('add', $this->jukedMaps[$uid]));

	}

	/**
	 * Revmoes a Map from the jukebox
	 * @param $login
	 * @param $uid
	 */
	public function removeFromJukebox($login, $uid){
		unset($this->jukedMaps[$uid]);
	}


	/**
	 * Called on endmap
	 * @param array $callback
	 */
	public function endMap(array $callback){

		if($this->maniaControl->settingManager->getSetting($this, self::SETTING_SKIP_MAP_ON_LEAVE) == TRUE){

			//Skip Map if requester has left
			foreach($this->jukedMaps as $jukedMap){
				$player = $jukedMap[0];

				//found player, so play this map
				if($this->maniaControl->playerManager->getPlayer($player->login) != null){
					break;
				}

				if($this->maniaControl->settingManager->getSetting($this, self::SETTING_SKIP_JUKED_ADMIN) == FALSE){
					//Check if the juker is a admin
					if($player->authLevel > 0){
						break;
					}
				}

				// Trigger callback
				$this->maniaControl->callbackManager->triggerCallback(self::CB_JUKEBOX_CHANGED, array('skip', $jukedMap[0]));

				//Player not found, so remove the map from the jukebox
				array_shift($this->jukedMaps);

				$this->maniaControl->chat->sendInformation('Juked Map skipped because $<' . $player->nickname . '$> left!');
			}
		}

		$nextMap = array_shift($this->jukedMaps);

		//Check if Jukebox is empty
		if($nextMap == null)
			return;

		$nextMap = $nextMap[1];


		$success = $this->maniaControl->client->query('ChooseNextMap', $nextMap->fileName);
		if (!$success) {
			trigger_error('[' . $this->maniaControl->client->getErrorCode() . '] ChooseNextMap - ' . $this->maniaControl->client->getErrorCode(), E_USER_WARNING);
			return;
		}

	}

	/**
	 * Returns a list with the indexes of the juked maps
	 * @return array
	 */
	public function getJukeBoxRanking(){
		$i = 1;
		$jukedMaps = array();
		foreach($this->jukedMaps as $map){
			$map = $map[1];
			$jukedMaps[$map->uid] = $i;
			$i++;
		}
		return $jukedMaps;
	}

	/**
	 * Dummy Function for testing
	 */
	public function printAllMaps(){
		foreach($this->jukedMaps as $map){
			$map = $map[1];
			var_dump($map->name);
		}
	}

} 