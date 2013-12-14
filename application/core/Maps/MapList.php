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
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Maps\Map;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\ManiaLink;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class MapList implements ManialinkPageAnswerListener {
	const ACTION_CLOSEWIDGET = 'MapList.CloseWidget';
	const MLID_WIDGET = 'MapList.WidgetId';

	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;


		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET , $this,
			'closeWidget');

		// Register for player commands
		//$this->maniaControl->commandManager->registerCommandListener('list', $this, 'command_list');
	}



	public function showMapList(Player $player){
		$maniaLink = new ManiaLink(self::MLID_WIDGET);

		//settings
		$width = 150;
		$height = 80;
		$quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width,$height);
		$frame->setPosition(0, 0);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width,$height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		//TODO headline
		$mapList = $this->maniaControl->mapManager->getMapList();

		$id = 1;
		$y = $height / 2 - 10;
		foreach($mapList as $map){
			$mapFrame = new Frame();
			$frame->add($mapFrame);
			$this->displayMap($id, $map, $mapFrame, $width, $height);
			$mapFrame->setY($y);
			$y -= 4;
			$id++;
		}

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(self::ACTION_CLOSEWIDGET );

		//render and display xml
		$maniaLinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($maniaLinkText, $player->login);
		$this->maniaControl->manialinkManager->disableAltMenu($player);
	}

	private function displayMap($id, Map $map, Frame $frame, $width){

		$frame->setZ(-0.01);

		$x = -$width / 2;

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
		$nameLabel->setText($map->name);

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


	public function closeWidget(array $callback, Player $player) {
		$emptyManialink = new ManiaLink(self::MLID_WIDGET);
		$manialinkText = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $player->login);
		$this->maniaControl->manialinkManager->enableAltMenu($player);
		unset($this->playersMenuShown[$player->login]);
	}

} 