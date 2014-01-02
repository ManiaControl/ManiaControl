<?php

namespace ManiaControl\Maps;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use KarmaPlugin;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ColorUtil;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
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
	const ACTION_ADD_MAP              = 'MapList.AddMap';
	const ACTION_ERASE_MAP            = 'MapList.EraseMap';
	const ACTION_SWITCH_MAP           = 'MapList.SwitchMap';
	const ACTION_QUEUED_MAP           = 'MapList.QueueMap';
	const ACTION_CONFIRM_ERASE_MAP    = 'MapList.ConfirmEraseMap';
	const ACTION_CONFIRM_SWITCHTO_MAP = 'MapList.ConfirmSwitchToMap';
	const MAX_MAPS_PER_PAGE           = 15;
	const SHOW_MX_LIST                = 1;
	const SHOW_MAP_LIST               = 2;

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

		$this->maniaControl->callbackManager->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		//Update Widget actions
		$this->maniaControl->callbackManager->registerCallbackListener(MapQueue::CB_MAPQUEUE_CHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_MAPLIST_UPDATED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_BEGINMAP, $this, 'updateWidget'); //TODO not working yet
		//TODO update on Karma Update

		//settings
		$this->width        = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$this->height       = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();
		$this->quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
		$this->quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

		/** @var just a test $itemQuad
		 * $itemQuad = new Quad();
		 * $itemQuad->setStyles('Icons128x32_1', Quad_Icons128x128_1::SUBSTYLE_Create);
		 * $itemQuad->setAction(self::ACTION_ADD_MAP);
		 * $this->maniaControl->adminMenu->addMenuItem($itemQuad, 4);*/
	}


	/**
	 * Displays the Mania Exchange List
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showManiaExchangeList(array $chatCallback, Player $player) {
		$this->mapListShown[$player->login] = self::SHOW_MX_LIST;

		$params = explode(' ', $chatCallback[1][2]);

		$serverInfo = $this->maniaControl->server->getSystemInfo();
		$title      = strtoupper(substr($serverInfo['TitleId'], 0, 2));

		$mapName     = '';
		$author      = '';
		$environment = ''; //TODO also get actual environment
		$recent      = true;

		if(count($params) > 1) {
			foreach($params as $param) {
				if($param == '/xlist') {
					continue;
				}
				if(strtolower(substr($param, 0, 5)) == 'auth:') {
					$author = substr($param, 5);
				} elseif(strtolower(substr($param, 0, 4)) == 'env:') {
					$environment = substr($param, 4);
				} else {
					if($mapName == '') {
						$mapName = $param;
					} else // concatenate words in name
					{
						$mapName .= '%20' . $param;
					}
				}
			}

			$recent = false;
		}

		// search for matching maps
		$maps = new MXInfoSearcher($title, $mapName, $author, $environment, $recent);

		//check if there are any results
		if(!$maps->valid()) {
			$this->maniaControl->chat->sendError('No maps found, or MX is down!', $player->login);
			if($maps->error != '') {
				trigger_error($maps->error, E_USER_WARNING);
			}
			return;
		}


		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame     = $this->buildMainFrame();
		$maniaLink->add($frame);

		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		//Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		$array = array("Id" => $x + 5, "Name" => $x + 17, "Author" => $x + 65, "Mood" => $x + 100, "Type" => $x + 115);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$i = 0;
		$y -= 10;
		foreach($maps as $map) {
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$array = array($map->id => $x + 5, $map->name => $x + 17, $map->author => $x + 65, $map->mood => $x + 100, $map->maptype => $x + 115);
			$this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			$mapFrame->setY($y);

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) { //todoSET as setting who can add maps
				//Add-Map-Button
				$addQuad = new Quad_Icons64x64_1();
				$mapFrame->add($addQuad);
				$addQuad->setX($x + 15);
				$addQuad->setZ(-0.1);
				$addQuad->setSubStyle($addQuad::SUBSTYLE_Add);
				$addQuad->setSize(4, 4);
				$addQuad->setAction(self::ACTION_ADD_MAP . "." . $map->id);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Add-Map: {$map->name}");
				$script->addTooltip($addQuad, $descriptionLabel);

			}

			$y -= 4;
			$i++;
			if($i == self::MAX_MAPS_PER_PAGE) {
				break;
			}
		}

		//TODO add MX info screen

		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Builds the mainFrame
	 *
	 * @return Frame $frame
	 */
	public function buildMainFrame() {
		//mainframe
		$frame = new Frame();
		$frame->setSize($this->width, $this->height);
		$frame->setPosition(0, 0);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($this->width, $this->height);
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
	 *
	 * @param Player $player
	 */
	public function showMapList(Player $player, $confirmAction = null, $confirmMapId = '') {

		$this->mapListShown[$player->login] = self::SHOW_MAP_LIST;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame     = $this->buildMainFrame();
		$maniaLink->add($frame);




		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($this->height / 2 - 5);
		$x     = -$this->width / 2;
		$array = array("Id" => $x + 5, "Mx ID" => $x + 10, "MapName" => $x + 20, "Author" => $x + 68, "Karma" => $x + 115, "Actions" => $this->width / 2 - 15);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		//Get Maplist
		$mapList = $this->maniaControl->mapManager->getMapList();

		//TODO add pages

		$queuedMaps = $this->maniaControl->mapManager->mapQueue->getQueuedMapsRanking();
		/** @var  KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->pluginManager->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$id = 1;
		$y  = $this->height / 2 - 10;
		/** @var  Map $map */
		foreach($mapList as $map) {
			//Map Frame
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$mapFrame->setZ(0.1);
			$mapFrame->setY($y);

			if($id % 2 != 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->add($lineQuad);
				$lineQuad->setSize($this->width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}


			if($this->maniaControl->mapManager->getCurrentMap() === $map) {
				$currentQuad = new Quad_Icons64x64_1();
				$mapFrame->add($currentQuad);
				$currentQuad->setX($x + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			$mxId = '-';
			if(isset($map->mx->id)) {
				$mxId = $map->mx->id;
			}

			//Display Maps
			$array = array($id => $x + 5, $mxId => $x + 10, $map->name => $x + 20, $map->authorNick => $x + 68);
			$this->maniaControl->manialinkManager->labelLine($mapFrame, $array);
			//TODO detailed mx info page with link to mxo
			//TODO action detailed map info
			//TODO side switch


			//MapQueue Description Label
			$descriptionLabel = new Label();
			$frame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
			$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
			$descriptionLabel->setSize($this->width * 0.7, 4);
			$descriptionLabel->setTextSize(2);
			$descriptionLabel->setVisible(false);

			//Map-Queue-Map-Label
			if(isset($queuedMaps[$map->uid])) {
				$label = new Label_Text();
				$mapFrame->add($label);
				$label->setX($this->width / 2 - 15);
				$label->setAlign(Control::CENTER, Control::CENTER);
				$label->setZ(0.2);
				$label->setTextSize(1.5);
				$label->setText($queuedMaps[$map->uid]);
				$label->setTextColor("FFF");

				$descriptionLabel->setText("{$map->name} \$zis on Map-Queue Position: {$queuedMaps[$map->uid]}");
				//$tooltips->add($jukeLabel, $descriptionLabel);
			} else {
				//Map-Queue-Map-Button
				$buttLabel = new Label_Button();
				$mapFrame->add($buttLabel);
				$buttLabel->setX($this->width / 2 - 15);
				$buttLabel->setZ(0.2);
				$buttLabel->setSize(3, 3);
				$buttLabel->setAction(self::ACTION_QUEUED_MAP . "." . $map->uid);
				$buttLabel->setText("+");
				$buttLabel->setTextColor("09F");


				$descriptionLabel->setText("Add Map to the Map Queue: {$map->name}");
				$script->addTooltip($buttLabel, $descriptionLabel);
			}

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_ADMIN)) { //TODO SET as setting who can add maps
				//erase map quad
				$eraseQuad = new Label_Button(); //TODO change name to label
				$mapFrame->add($eraseQuad);
				$eraseQuad->setX($this->width / 2 - 5);
				$eraseQuad->setZ(0.2);
				$eraseQuad->setSize(3, 3);
				$eraseQuad->setTextSize(1);
				$eraseQuad->setText("x");
				$eraseQuad->setTextColor("A00");
				//$eraseQuad->setAction(self::ACTION_ERASE_MAP . "." . ($id - 1) . "." . $map->uid);
				$eraseQuad->setAction(self::ACTION_CONFIRM_ERASE_MAP . "." . ($id));

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Remove Map: {$map->name}");
				$script->addTooltip($eraseQuad, $descriptionLabel);
			}
			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_MODERATOR)) { //TODO SET as setting who can add maps
				//switch to map quad
				//$switchToQuad = new Quad_Icons64x64_1(); //TODO change name to label
				$switchToQuad = new Label_Button();
				$mapFrame->add($switchToQuad);
				$switchToQuad->setX($this->width / 2 - 10);
				$switchToQuad->setZ(0.2);
				$switchToQuad->setSize(3, 3);
				$switchToQuad->setTextSize(2);
				//$switchToQuad->setSubStyle($switchToQuad::SUBSTYLE_ArrowFastNext);
				$switchToQuad->setText("»");
				$switchToQuad->setTextColor("0F0");

				$switchToQuad->setAction(self::ACTION_CONFIRM_SWITCHTO_MAP . "." . ($id));

				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Switch Directly to Map: {$map->name}");
				$script->addTooltip($switchToQuad, $descriptionLabel);
			}

			//Display Karma bar
			if($karmaPlugin != null) {
				$karma = $karmaPlugin->getMapKarma($map);
				$votes = $karmaPlugin->getMapVotes($map);
				if(is_numeric($karma)) {
					$karmaGauge = new Gauge();
					$mapFrame->add($karmaGauge);
					$karmaGauge->setZ(2);
					$karmaGauge->setX($x + 120);
					$karmaGauge->setSize(20, 9);
					$karmaGauge->setDrawBg(false);
					$karma = floatval($karma);
					$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
					$karmaColor = ColorUtil::floatToStatusColor($karma);
					$karmaGauge->setColor($karmaColor . '9');

					$karmaLabel = new Label();
					$mapFrame->add($karmaLabel);
					$karmaLabel->setZ(2);
					$karmaLabel->setX($x + 120);
					$karmaLabel->setSize(20 * 0.9, 5);
					$karmaLabel->setTextSize(0.9);
					$karmaLabel->setTextColor("000");
					$karmaLabel->setAlign(Control::CENTER, Control::CENTER);
					$karmaLabel->setText('  ' . round($karma * 100.) . '% (' . $votes['count'] . ')');
				}
			}


			//Confirm Frame
			if($id == $confirmMapId && ($confirmAction == self::ACTION_CONFIRM_SWITCHTO_MAP || $confirmAction == self::ACTION_CONFIRM_ERASE_MAP)){
				$confirmFrame = new Frame();
				$maniaLink->add($confirmFrame);
				$confirmFrame->setPosition($this->width / 2 + 6, $y);

				$quad = new Quad();
				$confirmFrame->add($quad);
				$quad->setStyles($this->quadStyle, $this->quadSubstyle);
				$quad->setSize(12, 4);


				$quad = new Quad_BgsPlayerCard();
				$confirmFrame->add($quad);
				//$quad->setX(0);
				//$quad->setY($y);
				$quad->setSubStyle($quad::SUBSTYLE_BgCardSystem);
				$quad->setSize(11,3.5);

				$label = new Label_Button();
				$confirmFrame->add($label);
				$label->setAlign(Control::CENTER, Control::CENTER);
				$label->setText("Sure");
				$label->setTextSize(1);
				$label->setScale(0.90);
				$label->setX(-1.3);

				$buttLabel = new Label_Button();
				$confirmFrame->add($buttLabel);
				$buttLabel->setPosition(3.2,0.4,0.2);
				$buttLabel->setSize(3, 3);
			//	$buttLabel->setTextSize(1);
				$buttLabel->setAlign(Control::CENTER, Control::CENTER);

				if($confirmAction == self::ACTION_CONFIRM_SWITCHTO_MAP){
					$quad->setAction(self::ACTION_SWITCH_MAP . "." . ($id - 1));
					$buttLabel->setText("»");
					$buttLabel->setTextColor("0F0");
					$buttLabel->setTextSize(2);
				}else{
					$buttLabel->setTextSize(1);
					$buttLabel->setText("x");
					$buttLabel->setTextColor("A00");
					$quad->setAction(self::ACTION_ERASE_MAP . "." . ($id - 1) . "." . $map->uid);
				}
			}


			$y -= 4;
			$id++;
			if($id == self::MAX_MAPS_PER_PAGE + 1) {
				break;
			}
		}

		//TODO pages


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}


	/**
	 * Closes the widget
	 *
	 * @param array $callback
	 */
	public function closeWidget(array $callback) {
		$player = $callback[1];
		unset($this->mapListShown[$player->login]);
	}

	/**
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode(".", $actionId);
		if(count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . "." . $actionArray[1];
		$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);

		switch($action) {
			case self::ACTION_CONFIRM_SWITCHTO_MAP:
				$this->showMapList($player, self::ACTION_CONFIRM_SWITCHTO_MAP, $actionArray[2]);
				break;
			case self::ACTION_CONFIRM_ERASE_MAP:
				$this->showMapList($player, self::ACTION_CONFIRM_ERASE_MAP, $actionArray[2]);
				break;
			case self::ACTION_ADD_MAP:
				$this->maniaControl->mapManager->addMapFromMx(intval($actionArray[2]), $callback[1][1]); //TODO bestätigung
				break;
			case self::ACTION_ERASE_MAP:
				$this->maniaControl->mapManager->eraseMap(intval($actionArray[2]), $actionArray[3]); //TODO bestätigung
				$this->showMapList($player);
				break;
			case self::ACTION_SWITCH_MAP:
				$this->maniaControl->client->query('JumpToMapIndex', intval($actionArray[2])); //TODO bestätigung
				$mapList = $this->maniaControl->mapManager->getMapList();

				$this->maniaControl->chat->sendSuccess('Map switched to $z$<' . $mapList[$actionArray[2]]->name . '$>!'); //TODO specified message, who done it?
				$this->maniaControl->log(Formatter::stripCodes('Skipped to $z$<' . $mapList[$actionArray[2]]->name . '$>!'));
				break;
			case self::ACTION_QUEUED_MAP:
				$this->maniaControl->mapManager->mapQueue->addMapToMapQueue($callback[1][1], $actionArray[2]);
				break;
		}
		return;
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged
	 *
	 * @param array $callback
	 */
	public function updateWidget(array $callback) {
		foreach($this->mapListShown as $login => $shown) {
			if($shown) {
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if($player != null) {
					if($shown == self::SHOW_MX_LIST) {
						//TODO
					} else if($shown == self::SHOW_MAP_LIST) {
						$this->showMapList($player);
					}
				} else {
					unset($this->mapListShown[$login]);
				}
			}
		}
	}
} 