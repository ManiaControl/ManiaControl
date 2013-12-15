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

class MapList implements ManialinkPageAnswerListener {
	const ACTION_CLOSEWIDGET = 'MapList.CloseWidget';

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

		//settings
		$this->width = 150;
		$this->height = 80;
		$this->quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$this->quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

	}


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

		//TODO headline
		$mapList = $this->maniaControl->mapManager->getMapList();

		$id = 1;
		$y = $this->height / 2 - 10;
		foreach($mapList as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$this->displayMap($id, $map, $mapFrame);
			$mapFrame->setY($y);
			$y -= 4;
			$id++;
		}

		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	private function displayMap($id, Map $map, Frame $frame){

		$frame->setZ(-0.01);

		$x = -$this->width / 2;

		//TODO detailed mx info page with link to mx
		$x +=5;
		$idLabel = new Label_Text();
		$frame->add($idLabel);
		$idLabel->setHAlign(Control::LEFT);
		$idLabel->setX($x);
		//	$mxIdLabel->setSize($width * 0.5, 2);
		$idLabel->setStyle($idLabel::STYLE_TextCardSmall);
		$idLabel->setTextSize(1.5);
		$idLabel->setText($id);
		$idLabel->setTextColor('FFF');

		//TODO detailed mx info page with link to mx
		$x +=5;
		$mxIdLabel = new Label_Text();
		$frame->add($mxIdLabel);
		$mxIdLabel->setHAlign(Control::LEFT);
		$mxIdLabel->setX($x);
		//	$mxIdLabel->setSize($width * 0.5, 2);
		$mxIdLabel->setStyle($mxIdLabel::STYLE_TextCardSmall);
		$mxIdLabel->setTextSize(1.5);
		if(isset($map->mx->id))
			$mxIdLabel->setText($map->mx->id);
		else
			$mxIdLabel->setText("-");
		$mxIdLabel->setTextColor('FFF');

		//TODO action detailed map info
		$x +=10;
		$nameLabel = new Label_Text();
		$frame->add($nameLabel);
		$nameLabel->setHAlign(Control::LEFT);
		$nameLabel->setX($x);
		//$nameLabel->setSize($width * 0.5, 2);
		$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
		$nameLabel->setTextSize(1.5);
		$nameLabel->setText('$fff'.$map->name);

		//TODO action detailed map info
		$x +=50;
		$authorLabel = new Label_Text();
		$frame->add($authorLabel);
		$authorLabel->setHAlign(Control::LEFT);
		$authorLabel->setX($x);
		//$nameLabel->setSize($width * 0.5, 2);
		$authorLabel->setStyle($authorLabel::STYLE_TextCardSmall);
		$authorLabel->setTextSize(1.5);
		$authorLabel->setText($map->authorNick);
		$authorLabel->setTextColor('FFF');


		//TODO later add buttons for jukebox, admin control buttons (remove map, change to map)
		//TODO side switch
		//var_dump($map);
	}


	/**
	 * Closes the widget
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closeWidget(array $callback, Player $player) {
		$this->maniaControl->manialinkManager->closeWidget($player);
	}

} 