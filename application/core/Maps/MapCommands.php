<?php

namespace ManiaControl\Maps;

use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use WidgetPlugin;

/**
 * Class offering commands to manage maps
 *
 * @author steeffeen & kremsy
 */
class MapCommands implements CommandListener, ManialinkPageAnswerListener,CallbackListener {
	/**
	 * Constants
	 */
	const ACTION_OPEN_MAPLIST = 'MapList.OpenMapList';
	const ACTION_OPEN_XLIST   = 'MapList.OpenMXList';
	const ACTION_RESTART_MAP  = 'MapList.RestartMap';
	const ACTION_SKIP_MAP     = 'MapList.NextMap';

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

		//Menus Buttons
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_XLIST, $this, 'command_xList');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_OPEN_MAPLIST, $this, 'command_List');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_RESTART_MAP, $this, 'command_RestartMap');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_SKIP_MAP, $this, 'command_NextMap');
	}

	/**
	 * Handle on Init
	 * @param array $callback
	 */
	public function handleOnInit(array $callback){
		//Menu Open xList
		$itemQuad = new Quad();
		$itemQuad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
		$itemQuad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER)); //TODO move the button to the image manager
		$itemQuad->setAction(self::ACTION_OPEN_XLIST);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, true, 3, 'Open MX List');

		//Menu Open List
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Browser);
		$itemQuad->setAction(self::ACTION_OPEN_MAPLIST);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, true, 4,'Open MapList');

		//Menu RestartMap
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastPrev);
		$itemQuad->setAction(self::ACTION_RESTART_MAP);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 0, 'Restart Map');

		//Menu NextMap
		$itemQuad = new Quad_Icons64x64_1();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_ArrowFastNext);
		$itemQuad->setAction(self::ACTION_SKIP_MAP);
		$this->maniaControl->actionsMenu->addMenuItem($itemQuad, false, 1, 'Skip Map');


	}
	/**
	 * Handle removemap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_RemoveMap(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// Get map
		$map = $this->maniaControl->mapManager->getCurrentMap();
		if(!$map) {
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return;
		}
		// Remove map
		if(!$this->maniaControl->client->query('RemoveMap', $map->fileName)) {
			trigger_error("Couldn't remove current map. " . $this->maniaControl->getClientErrorText());
			$this->maniaControl->chat->sendError("Couldn't remove map.", $player->login);
			return;
		}
		$this->maniaControl->chat->sendSuccess('Map removed.', $player->login);
	}

	/**
	 * Handle addmap command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_AddMap(array $chatCallback, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		// TODO: user mx fetcher
		$params = explode(' ', $chatCallback[1][2], 2);
		if(count($params) < 2) {
			$this->maniaControl->chat->sendUsageInfo('Usage example: //addmap 1234', $player->login);
			return;
		}

		//add Map from Mania Exchange
		$this->maniaControl->mapManager->addMapFromMx($params[1], $player->login);
	}

	/**
	 * Handle nextmap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_NextMap(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->query('NextMap');
	}

	/**
	 * Handle restartmap command
	 *
	 * @param array                        $chat
	 * @param \ManiaControl\Players\Player $player
	 */
	public function command_RestartMap(array $chat, Player $player) {
		if(!$this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		$this->maniaControl->client->query('RestartMap');
	}

	/**
	 * Handle list maps command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_List(array $chatCallback, Player $player) {
		$this->mapList->showMapList($player);
	}

	/**
	 * Handle ManiaExchange list command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_xList(array $chatCallback, Player $player) {
		$this->mapList->showManiaExchangeList($chatCallback, $player);
	}
}
