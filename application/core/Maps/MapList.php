<?php

namespace ManiaControl\Maps;
use FML\Controls\Control;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use MXInfoSearcher;

/**
 * MapList Widget Class
 *
 * @author steeffeen & kremsy
 */

class MapList implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const ACTION_ADD_MAP = 'MapList.AddMap';
	const ACTION_ERASE_MAP = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP = 'MapList.SwitchMap';
	const ACTION_JUKE_MAP = 'MapList.JukeMap';
	const MAX_MAPS_PER_PAGE = 15;
	const SHOW_MX_LIST = 1;
	const SHOW_MAP_LIST = 2;

	const DEFAULT_KARMA_PLUGIN = 'KarmaPlugin';
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $mapListShown = array();
	private $width;
	private $height;
	private $quadStyle;
	private $quadSubstyle;

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this,'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this,'handleManialinkPageAnswer');

		//Update Widget actions
		$this->maniaControl->callbackManager->registerCallbackListener(Jukebox::CB_JUKEBOX_CHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_MAPLIST_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'updateWidget'); //TODO not working yet
		//TODO update on Karma Update

		//settings
		$this->width = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$this->height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$this->quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		/** @var just a test $itemQuad
		$itemQuad = new Quad();
		$itemQuad->setStyles('Icons128x32_1', Quad_Icons128x128_1::SUBSTYLE_Create);
		$itemQuad->setAction(self::ACTION_ADD_MAP);
		$this->maniaControl->adminMenu->addMenuItem($itemQuad, 4);*/
	}


	/**
	 * Displays the Mania Exchange List
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showManiaExchangeList(array $chatCallback, Player $player){
		$this->mapListShown[$player->login] = self::SHOW_MX_LIST;

		$params = explode(' ', $chatCallback[1][2]);

		$serverInfo = $this->maniaControl->server->getSystemInfo();
		$title = strtoupper(substr($serverInfo['TitleId'], 0, 2));

		$mapName = '';
		$author = '';
		$environment = ''; //TODO also get actual environment
		$recent = true;

		if(count($params) > 1){
			foreach($params as $param){
				if($param == '/xlist')
					continue;
				if (strtolower(substr($param, 0, 5)) == 'auth:') {
					$author = substr($param, 5);
				} elseif (strtolower(substr($param, 0, 4)) == 'env:') {
					$environment = substr($param, 4);
				} else {
					if ($mapName == '')
						$mapName = $param;
					else  // concatenate words in name
						$mapName .= '%20' . $param;
				}
			}

			$recent = false;
		}

		// search for matching maps
		$maps = new MXInfoSearcher($title, $mapName, $author, $environment, $recent);

		//check if there are any results
		if(!$maps->valid()){
			$this->maniaControl->chat->sendError('No maps found, or MX is down!', $player->login);
			if($maps->error != '')
				trigger_error($maps->error, E_USER_WARNING);
			return;
		}


		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame = $this->buildMainFrame();
		$maniaLink->add($frame);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$tooltips = new Tooltips();
		$script->addFeature($tooltips);

		//Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		$array = array("Id" => $x + 5, "Name" => $x + 17, "Author" => $x + 65, "Mood" => $x + 100, "Type" => $x + 115);
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		$i = 0;
		$y -= 10;
		foreach($maps as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$array = array($map->id => $x + 5, $map->name => $x + 17, $map->author => $x + 65, $map->mood => $x + 100, $map->maptype => $x + 115);
			$this->maniaControl->manialinkManager->labelLine($mapFrame,$array);
			$mapFrame->setY($y);

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)){ //todoSET as setting who can add maps
				//Add-Map-Button
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->add($addQuad);
				$addQuad->setX($x + 15);
				$addQuad->setZ(-0.1);
				$addQuad->setSubStyle($addQuad::SUBSTYLE_Add);
				$addQuad->setSize(4,4);
				$addQuad->setAction(self::ACTION_ADD_MAP . "." .$map->id);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Add-Map: {$map->name}");
				$tooltips->add($addQuad, $descriptionLabel);

			}

			$y -= 4;
			$i++;
			if($i == self::MAX_MAPS_PER_PAGE)
				break;
		}

		//TODO add MX info screen

		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Builds the mainFrame
	 * @return Frame $frame
	 */
	public function buildMainFrame(){
		//mainframe
		$frame = new Frame();
		$frame->setSize($this->width,$this->height);
		$frame->setPosition(0, 0);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($this->width,$this->height);
		$backgroundQuad->setStyles($this->quadStyle, $this->quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($this->width * 0.483, $this->height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		return $frame;
	}
	/**
	 * Displayes a MapList on the screen
	 * @param Player $player
	 */
	public function showMapList(Player $player){

		$this->mapListShown[$player->login] = self::SHOW_MAP_LIST;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame = $this->buildMainFrame();
		$maniaLink->add($frame);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$tooltips = new Tooltips();
		$script->addFeature($tooltips);

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($this->height / 2 - 5);
		$x = -$this->width / 2;
		$array = array("Id" => $x + 5, "Mx ID" => $x + 10, "MapName" => $x + 20, "Author" => $x + 73, "Karma" => $x + 105, "Actions" => $this->width / 2 - 15);
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		//Get Maplist
		$mapList = $this->maniaControl->mapManager->getMapList();

		//TODO add pages

		$jukedMaps = $this->maniaControl->mapManager->jukebox->getJukeBoxRanking();
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$id = 1;
		$y = $this->height / 2 - 10;
		foreach($mapList as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$this->displayMap($id, $map, $mapFrame, $tooltips);
			$mapFrame->setY($y);


			//Jukebox Description Label
			$descriptionLabel = new Label();
			$frame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
			$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
			$descriptionLabel->setSize($this->width * 0.7, 4);
			$descriptionLabel->setTextSize(2);
			$descriptionLabel->setVisible(false);

			//Juke-Map-Label
			if(isset($jukedMaps[$map->uid])){
				$jukeLabel = new Label_Text();
				$mapFrame->add($jukeLabel);
				$jukeLabel->setX($this->width/2 - 15);
				$jukeLabel->setAlign(Control::CENTER,Control::CENTER);
				$jukeLabel->setZ(0.2);
				$jukeLabel->setTextSize(1.5);
				$jukeLabel->setText($jukedMaps[$map->uid]);
				$jukeLabel->setTextColor("FFF");

				$descriptionLabel->setText("{$map->name} \$zis on Jukebox Position: {$jukedMaps[$map->uid]}");
				$tooltips->add($jukeLabel, $descriptionLabel);
			}else{
				//Juke-Map-Button
				$jukeQuad = new Quad_Icons128x128_1();
				$mapFrame->add($jukeQuad);
				$jukeQuad->setX($this->width/2 - 15);
				$jukeQuad->setZ(0.2);
				$jukeQuad->setSize(4,4);
				$jukeQuad->setSubStyle($jukeQuad::SUBSTYLE_Load);
				$jukeQuad->setAction(self::ACTION_JUKE_MAP . "." . $map->uid);

				$descriptionLabel->setText("Add Map to Jukebox: {$map->name}");
				$tooltips->add($jukeQuad, $descriptionLabel);
			}

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)){ //TODO SET as setting who can add maps
				//erase map quad
				$eraseQuad = new Quad_UIConstruction_Buttons();
				$mapFrame->add($eraseQuad);
				$eraseQuad->setX($this->width/2 - 5);
				$eraseQuad->setZ(0.2);
				$eraseQuad->setSize(4,4);
				$eraseQuad->setSubStyle($eraseQuad::SUBSTYLE_Erase);
				$eraseQuad->setAction(self::ACTION_ERASE_MAP . "." .($id-1) . "." . $map->uid);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Remove Map: {$map->name}");
				$tooltips->add($eraseQuad, $descriptionLabel);
			}
			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){ //TODO SET as setting who can add maps
				//switch to map quad
				$switchToQuad = new Quad_Icons64x64_1();
				$mapFrame->add($switchToQuad);
				$switchToQuad->setX($this->width/2 - 10);
				$switchToQuad->setZ(0.2);
				$switchToQuad->setSize(4, 4);
				$switchToQuad->setSubStyle($switchToQuad::SUBSTYLE_ArrowFastNext);
				$switchToQuad->setAction(self::ACTION_SWITCH_MAP . "." .($id-1));

				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Switch Directly to Map: {$map->name}");
				$tooltips->add($switchToQuad, $descriptionLabel);
			}


			//Display Karma bar
			if($karmaPlugin != null){
				$karma = $karmaPlugin->getMapKarma($map);
				$votes = $karmaPlugin->getMapVotes($map);
				if(is_numeric($karma)){
					$karmaGauge = new Gauge();
					$mapFrame->add($karmaGauge);
					$karmaGauge->setX($x + 110);
					$karmaGauge->setSize(20, 9);
					$karmaGauge->setDrawBg(false);
					$karma = floatval($karma);
					$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
					$karmaColor = ColorUtil::floatToStatusColor($karma);
					$karmaGauge->setColor($karmaColor . '9');

					$karmaLabel = new Label();
					$mapFrame->add($karmaLabel);
					$karmaLabel->setX($x + 110);
					$karmaLabel->setSize(20 * 0.9, 5);
					$karmaLabel->setTextSize(0.9);
					$karmaLabel->setTextColor("000");
					$karmaLabel->setAlign(Control::CENTER, Control::CENTER);
					$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $votes['count'] . ')');
				}
			}

			$y -= 4;
			$id++;
			if($id == self::MAX_MAPS_PER_PAGE + 1)
				break;
		}

		//TODO pages


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Displays a single Map in the Maplist
	 * @param       $id
	 * @param Map   $map
	 * @param Frame $frame
	 */
	private function displayMap($id, Map $map, Frame $frame, Tooltips $tooltips){
		$frame->setZ(0.1);

		//set starting x-value
		$x = -$this->width / 2;

		if($this->maniaControl->mapManager->getCurrentMap() === $map){
			$currentQuad = new Quad_Icons64x64_1();
			$frame->add($currentQuad);
			$currentQuad->setX($x + 3.5);
			$currentQuad->setZ(0.2);
			$currentQuad->setSize(4, 4);
			$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
		}

		$mxId = '-';
		if(isset($map->mx->id))
			$mxId = $map->mx->id;

		//Display Maps
		$array = array($id => $x + 5, $mxId => $x + 10, $map->name => $x + 20, $map->authorNick => $x + 73);
		$this->maniaControl->manialinkManager->labelLine($frame,$array);
		//TODO detailed mx info page with link to mxo
		//TODO action detailed map info
		//TODO side switch
	}


	/**
	 * Closes the widget
	 * @param array  $callback
	 */
	public function closeWidget(array $callback) {
		$player = $callback[1];
		unset($this->mapListShown[$player->login]);
	}

	/**
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback){
		$actionId = $callback[1][2];
		$addMap = (strpos($actionId, self::ACTION_ADD_MAP) === 0);
		$eraseMap = (strpos($actionId, self::ACTION_ERASE_MAP) === 0);
		$switchMap = (strpos($actionId, self::ACTION_SWITCH_MAP) === 0);
		$jukeMap = (strpos($actionId, self::ACTION_JUKE_MAP) === 0);

		if(!$addMap && !$eraseMap && !$switchMap && !$jukeMap)
			return;

		$actionArray = explode(".", $actionId);

		$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);

		if($addMap){ //TODO log and chat message
			$this->maniaControl->mapManager->addMapFromMx(intval($actionArray[2]),$callback[1][1]); //TODO bestätigung
		}else if($eraseMap){ //TODO log and chat message
			$this->maniaControl->mapManager->eraseMap(intval($actionArray[2]), $actionArray[3]); //TODO bestätigung
			$this->showMapList($player);
		}else if($switchMap){ //TODO log and chat message
			$this->maniaControl->client->query('JumpToMapIndex', intval($actionArray[2])); //TODO bestätigung
			$mapList = $this->maniaControl->mapManager->getMapList();

			$this->maniaControl->chat->sendSuccess('Map switched to $z$<' . $mapList[$actionArray[2]]->name . '$>!'); //TODO specified message, who done it?
			$this->maniaControl->log('Skipped to $z$<' . $mapList[$actionArray[2]]->name . '$>!');
		}else if($jukeMap){
			$this->maniaControl->mapManager->jukebox->addMapToJukebox($callback[1][1], $actionArray[2]);
		}

	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged
	 * @param array $callback
	 */
	public function updateWidget(array $callback){
		foreach($this->mapListShown as $login => $shown){
			if($shown){
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if($player != null){
					if($shown == self::SHOW_MX_LIST){
						//TODO
					}else if($shown == self::SHOW_MAP_LIST){
						$this->showMapList($player);
					}
				}else{
					unset($this->mapListShown[$login]);
				}
			}
		}
	}
} 