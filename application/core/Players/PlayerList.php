<?php

namespace ManiaControl\Players;


use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;

class PlayerList implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const ACTION_CLOSEWIDGET = 'PlayerList.CloseWidget';

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
	/*	$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this,
			'handleManialinkPageAnswer');*/

		//settings
		$this->width = 150;
		$this->height = 80;
		$this->quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$this->quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

	}

	public function showPlayerList(Player $player){
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
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

		//Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 3);
		//$array = array("Id" => $x + 5, "Nickname" => $x + 10,  "Login" => $x + 40, "Ladder" => $x + 60,"Zone" => $x + 85);
		$array = array("Id" => $x + 5, "Nickname" => $x + 18,  "Login" => $x + 60, "Home" => $x + 85);
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		//get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		$i = 1;
		$y -= 10;
		foreach($players as $listPlayer){
			//$path = substr($listPlayer->path, 6);
			$path = $listPlayer->getCountry() . " - " . $listPlayer->getProvince();
			$playerFrame = new Frame();
			$frame->add($playerFrame);
			//$array = array($i => $x + 5, $listPlayer->nickname => $x + 10,  $listPlayer->login => $x + 50, $listPlayer->ladderRank => $x + 60, $listPlayer->ladderScore => $x + 70, $path => $x + 85);
			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18,  $listPlayer->login => $x + 60, $path => $x + 85);
			$this->maniaControl->manialinkManager->labelLine($playerFrame,$array);
			$playerFrame->setY($y);

			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){
				$rightQuad = new Quad_BgRaceScore2();
				$playerFrame->add($rightQuad);
				$rightQuad->setX($x + 13);
				$rightQuad->setZ(-0.1);
				$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
				$rightQuad->setSize(7,4);

				$rightLabel = new Label_Text();
				$playerFrame->add($rightLabel);
				$rightLabel->setX($x + 13.3);
				$rightLabel->setTextSize(0.8);

				echo $this->maniaControl->authenticationManager->getAuthLevel($listPlayer->login);
				switch($listPlayer->authLevel){
					case authenticationManager::AUTH_LEVEL_MASTERADMIN:	$rightLabel->setText("MA");
																		break;
					case authenticationManager::AUTH_LEVEL_SUPERADMIN:  $rightLabel->setText("SA");
																		break;
					case authenticationManager::AUTH_LEVEL_ADMIN:		$rightLabel->setText("AD");
																		break;
					case authenticationManager::AUTH_LEVEL_OPERATOR:	$rightLabel->setText("OP");
				}

				$rightLabel->setTextColor("f00");
			}
			$i++;
			$y -= 4;
		}


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
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