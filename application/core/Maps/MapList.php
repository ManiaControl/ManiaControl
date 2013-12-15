<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 14.12.13
 * Time: 19:42
 */

namespace ManiaControl\Maps;
use FML\Controls\Control;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Maps\Map;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use MXInfoSearcher;

class MapList implements ManialinkPageAnswerListener, CallbackListener {

	const ACTION_CLOSEWIDGET = 'MapList.CloseWidget';
	const ACTION_ADD_MAP = 'MapList.AddMap';
	const MAX_MAPS_PER_PAGE = 15;


	/**
	 * Private properties
	 */
	private $maniaControl = null;
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


		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET , $this,
			'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this,
			'handleManialinkPageAnswer');

		//settings
		$this->width = 150;
		$this->height = 80;
		$this->quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$this->quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

	}


	/**
	 * Displays the Mania Exchange List
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function showManiaExchangeList(array $chatCallback, Player $player){
		$params = explode(' ', $chatCallback[1][2]);
		//$commandCount = count(explode(' ', $chatCallback[1][2]));
		//var_dump($chatCallback[1][2]);
		//echo $commandCount;

		$section = 'SM'; //TODO get from mc
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
		$maps = new MXInfoSearcher($section, $mapName, $author, $environment, $recent);

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
		$headFrame->setY($y - 3);
		$array = array("Id" => $x + 5, "Name" => $x + 17, "Author" => $x + 70, "Mood" => $x + 90, "Type" => $x + 105);
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		$i = 0;
		$y -= 10;
		foreach($maps as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$array = array($map->id => $x + 5, $map->name => $x + 17, $map->author => $x + 70, $map->mood => $x + 90, $map->maptype => $x + 105);
			$this->maniaControl->manialinkManager->labelLine($mapFrame,$array);
			$mapFrame->setY($y);

			//TODO only for admins:
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


			$y -= 4;
			$i++;
			if($i == self::MAX_MAPS_PER_PAGE)
				break;
		}

		//TODO add MX info screen
		//TODO add download Map button

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
		$closeQuad->setAction(self::ACTION_CLOSEWIDGET );

		return $frame;
	}
	/**
	 * Displayes a MapList on the screen
	 * @param Player $player
	 */
	public function showMapList(Player $player){

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$frame = $this->buildMainFrame();
		$maniaLink->add($frame);


		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($this->height / 2 - 3);
		$x = -$this->width / 2;
		$array = array("Id" => $x + 5, "Mx ID" => $x + 10, "MapName" => $x + 20, "Author" => $x + 70);
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		//Get Maplist
		$mapList = $this->maniaControl->mapManager->getMapList();

		//TODO add pages

		$id = 1;
		$y = $this->height / 2 - 10;
		foreach($mapList as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$this->displayMap($id, $map, $mapFrame);
			$mapFrame->setY($y);
			$y -= 4;
			$id++;
			if($id == self::MAX_MAPS_PER_PAGE - 1)
				break;
		}

		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Displays a single Map in the Maplist
	 * @param       $id
	 * @param Map   $map
	 * @param Frame $frame
	 */
	private function displayMap($id, Map $map, Frame $frame){
		$frame->setZ(-0.01);

		//set starting x-value
		$x = -$this->width / 2;


		$mxId = '';
		if(isset($map->mx->id))
			$mxId = $map->mx->id;

		//Display Maps
		$array = array($id => $x + 5, $mxId => $x + 10, $map->name => $x + 20, $map->authorNick => $x + 70);
		$this->maniaControl->manialinkManager->labelLine($frame,$array);
		//TODO detailed mx info page with link to mx
		//TODO action detailed map info
		//TODO action detailed map info
		//TODO later add buttons for jukebox, admin control buttons (remove map, change to map)
		//TODO side switch
	}


	/**
	 * Closes the widget
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closeWidget(array $callback, Player $player) {
		$this->maniaControl->manialinkManager->closeWidget($player);
	}

	/**
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback){
		$actionId = $callback[1][2];
		$addMap = (strpos($actionId, self::ACTION_ADD_MAP) === 0);
		if(!$addMap)
			return;

		$actionArray = explode(".", $actionId);
		var_dump($actionArray);
		$this->maniaControl->mapManager->addMapFromMx($actionArray[2],$callback[1][1]);
	}
} 