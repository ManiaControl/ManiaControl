<?php

namespace ManiaControl\Players;

use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Emblems;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;

/**
 * PlayerList Widget Class
 *
 * @author steeffeen & kremsy
 */
class PlayerList implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const ACTION_FORCE_RED = 'PlayerList.ForceRed';
	const ACTION_FORCE_BLUE = 'PlayerList.ForceBlue';
	const ACTION_FORCE_SPEC = 'PlayerList.ForceSpec';

	const ACTION_PLAYER_ADV = 'PlayerList.PlayerAdvancedActions';
	const ACTION_CLOSE_PLAYER_ADV = 'PlayerList.ClosePlayerAdvWidget';
	const ACTION_WARN_PLAYER = 'PlayerList.WarnPlayer';
	const ACTION_KICK_PLAYER = 'PlayerList.KickPlayer';
	const ACTION_BAN_PLAYER = 'PlayerList.BanPlayer';
	const ACTION_ADD_AS_MASTER = 'PlayerList.PlayerAddAsMaster';
	const ACTION_ADD_AS_ADMIN = 'PlayerList.PlayerAddAsAdmin';
	const ACTION_ADD_AS_MOD  = 'PlayerList.PlayerAddAsModerator';
	const ACTION_REVOKE_RIGHTS = 'PlayerList.RevokeRights';

	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $width;
	private $height;
	private $quadStyle;
	private $quadSubstyle;
	private $playersListShown = array();

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this,
			'closeWidget');
		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSE_PLAYER_ADV , $this,
			'closePlayerAdvancedWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this,
			'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCONNECT, $this, 'updateWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERDISCONNECT, $this, 'updateWidget');
		//settings
		$this->width = 150;
		$this->height = 80;
		$this->quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$this->quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

	}

	public function showPlayerList(Player $player){
		$this->playersListShown[$player->login] = true;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);


		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$tooltips = new Tooltips();
		$script->addFeature($tooltips);

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
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		//Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		//$array = array("Id" => $x + 5, "Nickname" => $x + 10,  "Login" => $x + 40, "Ladder" => $x + 60,"Zone" => $x + 85);
		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){
			$array = array("Id" => $x + 5, "Nickname" => $x + 18,  "Login" => $x + 60, "Location" => $x + 91, "Actions" => $x + 135);
		}else{
			$array = array("Id" => $x + 5, "Nickname" => $x + 18,  "Login" => $x + 60, "Location" => $x + 91);
		}
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		//get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		$i = 1;
		$y -= 10;
		foreach($players as $listPlayer){

			//$path = substr($listPlayer->path, 6);
			//$path = $listPlayer->getCountry() . " - " . $listPlayer->getProvince();
			$path = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$frame->add($playerFrame);
			//$array = array($i => $x + 5, $listPlayer->nickname => $x + 10,  $listPlayer->login => $x + 50, $listPlayer->ladderRank => $x + 60, $listPlayer->ladderScore => $x + 70, $path => $x + 85);
			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18,  $listPlayer->login => $x + 60, $path => $x + 91);
			$this->maniaControl->manialinkManager->labelLine($playerFrame,$array);
			$playerFrame->setY($y);

			//Team Emblem
			if($listPlayer->teamId >= 0){ //Player is in a Team
				$redQuad = new Quad_Emblems(); //TODO rename quads
				$playerFrame->add($redQuad);
				$redQuad->setX($x + 10);
				$redQuad->setZ(0.1);
				$redQuad->setSize(3.8,3.8);

				switch($listPlayer->teamId){
					case 0: $redQuad->setSubStyle($redQuad::SUBSTYLE_1); break;
					case 1: $redQuad->setSubStyle($redQuad::SUBSTYLE_2); break;
				}
			}else if($listPlayer->isSpectator){ //Player is in Spectator Mode
				$neutralQuad = new Quad_BgRaceScore2();
				$playerFrame->add($neutralQuad);
				$neutralQuad->setX($x + 10);
				$neutralQuad->setZ(0.1);
				$neutralQuad->setSubStyle($neutralQuad::SUBSTYLE_Spectator);
				$neutralQuad->setSize(3.8,3.8);
			}

			//Nation Quad
			$countryQuad = new Quad();
			$playerFrame->add($countryQuad);

			$countryQuad->setImage("file://Skins/Avatars/Flags/{$this->mapCountry($listPlayer->getCountry())}.dds");
			$countryQuad->setX($x + 88);
			$countryQuad->setSize(4,4);
			$countryQuad->setZ(-0.1);
			//TODO mousover show locations in descript bar


			//Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->add($rightQuad);
			$rightQuad->setX($x + 13);
			$rightQuad->setZ(-0.1);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7,3.5);

			$rightLabel = new Label_Text();
			$playerFrame->add($rightLabel);
			$rightLabel->setX($x + 13.9);
			$rightLabel->setTextSize(0.8);


			//Description Label
			$descriptionLabel = new Label();
			$frame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
			$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
			$descriptionLabel->setSize($this->width * 0.7, 4);
			$descriptionLabel->setTextSize(2);
			$descriptionLabel->setVisible(false);
			$descriptionLabel->setText($this->maniaControl->authenticationManager->getAuthLevelName($listPlayer->authLevel) .  " " . $listPlayer->nickname);
			$tooltips->add($rightQuad, $descriptionLabel);

			switch($listPlayer->authLevel){
				case authenticationManager::AUTH_LEVEL_MASTERADMIN:
				case authenticationManager::AUTH_LEVEL_SUPERADMIN:  $rightLabel->setText("MA"); break;
				case authenticationManager::AUTH_LEVEL_ADMIN:		$rightLabel->setText("AD"); break;
				case authenticationManager::AUTH_LEVEL_OPERATOR:	$rightLabel->setText("OP");
			}

			$rightLabel->setTextColor("fff");


			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){

				//Further Player actions
				$playerQuad = new Quad_Icons64x64_1();
				$playerFrame->add($playerQuad);
				$playerQuad->setX($x + 132);
				$playerQuad->setZ(0.1);
				$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Buddy);
				$playerQuad->setSize(3.8,3.8);
				$playerQuad->setAction(self::ACTION_PLAYER_ADV . "." .$listPlayer->login);
				//TODO special player thing


				$redQuad = new Quad_Emblems();
				$playerFrame->add($redQuad);
				$redQuad->setX($x + 145);
				$redQuad->setZ(0.1);
				$redQuad->setSubStyle($redQuad::SUBSTYLE_2);
				$redQuad->setSize(3.8,3.8);
				$redQuad->setAction(self::ACTION_FORCE_RED . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Red Team!');
				$tooltips->add($redQuad, $descriptionLabel);

				//Force to Blue Team
				$blueQuad = new Quad_Emblems();
				$playerFrame->add($blueQuad);
				$blueQuad->setX($x + 141);
				$blueQuad->setZ(0.1);
				$blueQuad->setSubStyle($blueQuad::SUBSTYLE_1);
				$blueQuad->setSize(3.8,3.8);
				$blueQuad->setAction(self::ACTION_FORCE_BLUE . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Blue Team!');
				$tooltips->add($blueQuad, $descriptionLabel);


				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->add($spectatorQuad);
				$spectatorQuad->setX($x + 137);
				$spectatorQuad->setZ(0.1);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setSize(3.8,3.8);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Spectator!');
				$tooltips->add($spectatorQuad, $descriptionLabel);
			}
			$i++;
			$y -= 4;
		}


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);

		return $maniaLink;
	}


	/**
	 * Extra window with special actions on players like warn,kick, ban, authorization levels...
	 * @param array  $callback
	 * @param Player $caller
	 * @param        $login
	 */
	public function advancedPlayerWidget(array $callback, Player $caller, $login){
		$maniaLink = $this->showPlayerList($caller);

		//todo all configurable or as constants
		$x = $this->width / 2 + 2.5;
		$width = 35;
		$height = $this->height * 0.7;
		$hAlign = Control::LEFT;
		$style = Label_Text::STYLE_TextCardSmall;
		$textSize = 1.5;
		$textColor = 'FFF';
		$quadWidth = $width - 7;

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width,$height);
		$frame->setPosition($x + $width / 2, 0);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.4, $height * 0.43, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(self::ACTION_CLOSE_PLAYER_ADV );

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width,$height);
		$backgroundQuad->setStyles($this->quadStyle, $this->quadSubstyle);
		$backgroundQuad->setZ(0.1);

		//Show headline
		$label = new Label_Text();
		$frame->add($label);
		$label->setHAlign($hAlign);
		$label->setX(-$width / 2 + 5);
		$label->setY($height / 2 - 5);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Advanced Actions");
		$label->setTextColor($textColor);

		$player = $this->maniaControl->playerManager->getPlayer($login);

		//Show Nickname
		$label = new Label_Text();
		$frame->add($label);
		$label->setHAlign($hAlign);
		$label->setX(0);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setY($height / 2 - 8);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText($player->nickname);
		$label->setTextColor($textColor);

		$y = $height / 2 - 14;
		//Show Warn
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_WARN_PLAYER . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setX(0);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Warn");
		$label->setTextColor($textColor);

		$y -= 5;
		//Show Kick
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_KICK_PLAYER . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Kick");
		$label->setTextColor($textColor);

		$y -= 5;
		//Show Ban
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_BAN_PLAYER . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Ban");
		$label->setTextColor($textColor);

		$y -= 10;
		//Show Add as Master-Admin
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_ADD_AS_MASTER . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);

		$label->setText("Add MasterAdmin");

		$label->setTextColor($textColor);

		$y -= 5;
		//Show Add as Admin
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_ADD_AS_ADMIN . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Add Admin");
		$label->setTextColor($textColor);

		$y -= 5;
		//Show Add as Moderator
		$quad = new Quad_BgsPlayerCard();
		$frame->add($quad);
		$quad->setX(0);
		$quad->setY($y);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
		$quad->setSize($quadWidth, 5);
		$quad->setAction(self::ACTION_ADD_AS_MOD . "." .$login);

		$label = new Label_Button();
		$frame->add($label);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setX(0);
		$label->setY($y);
		$label->setStyle($style);
		$label->setTextSize($textSize);
		$label->setText("Add Moderator");
		$label->setTextColor($textColor);


		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){
			$y -= 5;
			//Revoke Rights
			$quad = new Quad_BgsPlayerCard();
			$frame->add($quad);
			$quad->setX(0);
			$quad->setY($y);
			$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCard);
			$quad->setSize($quadWidth, 5);
			$quad->setAction(self::ACTION_REVOKE_RIGHTS . "." .$login);

			$label = new Label_Button();
			$frame->add($label);
			$label->setAlign(Control::CENTER,Control::CENTER);
			$label->setX(0);
			$label->setY($y);
			$label->setStyle($style);
			$label->setTextSize($textSize);
			$label->setText("Revoke Rights");
			$label->setTextColor($textColor);
		}

		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $caller);

	}
	/**
	 * Closes the widget
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closeWidget(array $callback, Player $player) {
		$this->playersListShown[$player->login] = false; //TODO unset
	}


	/**
	 * Closes the player advanced widget widget
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closePlayerAdvancedWidget(array $callback, Player $player) {
		$this->showPlayerList($player); //overwrite the manialink
		//TODO remove double rendering
	}



	/**
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback){
		$actionId = $callback[1][2];
		$actionArray = explode(".", $actionId);

		//TODO maybe with ids instead of logins, lower network traffic
		switch($actionArray[0].".".$actionArray[1]){
			case self::ACTION_FORCE_BLUE:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($callback[1][1],$actionArray[2],playerActions::BLUE_TEAM);
				break;
			case self::ACTION_FORCE_RED:
				$this->maniaControl->playerManager->playerActions->forcePlayerToTeam($callback[1][1],$actionArray[2],playerActions::RED_TEAM);
				break;
			case self::ACTION_FORCE_SPEC:
				$this->maniaControl->playerManager->playerActions->forcePlayerToSpectator($callback[1][1],$actionArray[2],playerActions::SPECTATOR_BUT_KEEP_SELECTABLE);
				break;
			case self::ACTION_WARN_PLAYER:
				$this->maniaControl->playerManager->playerActions->warnPlayer($callback[1][1],$actionArray[2]);
				break;
			case self::ACTION_KICK_PLAYER:
				$this->maniaControl->playerManager->playerActions->kickPlayer($callback[1][1],$actionArray[2]);
				break;
			case self::ACTION_BAN_PLAYER:
				$this->maniaControl->playerManager->playerActions->banPlayer($callback[1][1],$actionArray[2]);
				break;
			case self::ACTION_PLAYER_ADV:
				$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
				$this->advancedPlayerWidget($callback, $player, $actionArray[2]);
				break;
			case self::ACTION_ADD_AS_MASTER:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($callback[1][1], $actionArray[2], AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
				break;
			case self::ACTION_ADD_AS_ADMIN:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($callback[1][1], $actionArray[2], AuthenticationManager::AUTH_LEVEL_ADMIN);
				break;
			case self::ACTION_ADD_AS_MOD:
				$this->maniaControl->playerManager->playerActions->grandAuthLevel($callback[1][1], $actionArray[2], AuthenticationManager::AUTH_LEVEL_OPERATOR);
				break;
			case self::ACTION_REVOKE_RIGHTS:
				$this->maniaControl->playerManager->playerActions->revokeAuthLevel($callback[1][1], $actionArray[2]);
				break;
		}
	}

	/**
	 * Reopen the widget on PlayerInfoChanged / Player Connect and Disconnect
	 * @param array $callback
	 */
	public function updateWidget(array $callback){
		foreach($this->playersListShown as $login => $shown){
			if($shown == true){
				$player = $this->maniaControl->playerManager->getPlayer($login);
				if($player != null)
					$this->showPlayerList($player);
				else
					unset($this->playersListShown[$login]);
			}
		}
	}



	//TODO move that into somewhere
	/**
	 * Map country names to 3-letter Nation abbreviations
	 * Created by Xymph
	 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
	 * See also http://en.wikipedia.org/wiki/Comparison_of_IOC,_FIFA,_and_ISO_3166_country_codes
	 */
	private function mapCountry($country) {

		$nations = array(
			'Afghanistan' => 'AFG',
			'Albania' => 'ALB',
			'Algeria' => 'ALG',
			'Andorra' => 'AND',
			'Angola' => 'ANG',
			'Argentina' => 'ARG',
			'Armenia' => 'ARM',
			'Aruba' => 'ARU',
			'Australia' => 'AUS',
			'Austria' => 'AUT',
			'Azerbaijan' => 'AZE',
			'Bahamas' => 'BAH',
			'Bahrain' => 'BRN',
			'Bangladesh' => 'BAN',
			'Barbados' => 'BAR',
			'Belarus' => 'BLR',
			'Belgium' => 'BEL',
			'Belize' => 'BIZ',
			'Benin' => 'BEN',
			'Bermuda' => 'BER',
			'Bhutan' => 'BHU',
			'Bolivia' => 'BOL',
			'Bosnia&Herzegovina' => 'BIH',
			'Botswana' => 'BOT',
			'Brazil' => 'BRA',
			'Brunei' => 'BRU',
			'Bulgaria' => 'BUL',
			'Burkina Faso' => 'BUR',
			'Burundi' => 'BDI',
			'Cambodia' => 'CAM',
			'Cameroon' => 'CAR',  // actually CMR
			'Canada' => 'CAN',
			'Cape Verde' => 'CPV',
			'Central African Republic' => 'CAF',
			'Chad' => 'CHA',
			'Chile' => 'CHI',
			'China' => 'CHN',
			'Chinese Taipei' => 'TPE',
			'Colombia' => 'COL',
			'Congo' => 'CGO',
			'Costa Rica' => 'CRC',
			'Croatia' => 'CRO',
			'Cuba' => 'CUB',
			'Cyprus' => 'CYP',
			'Czech Republic' => 'CZE',
			'Czech republic' => 'CZE',
			'DR Congo' => 'COD',
			'Denmark' => 'DEN',
			'Djibouti' => 'DJI',
			'Dominica' => 'DMA',
			'Dominican Republic' => 'DOM',
			'Ecuador' => 'ECU',
			'Egypt' => 'EGY',
			'El Salvador' => 'ESA',
			'Eritrea' => 'ERI',
			'Estonia' => 'EST',
			'Ethiopia' => 'ETH',
			'Fiji' => 'FIJ',
			'Finland' => 'FIN',
			'France' => 'FRA',
			'Gabon' => 'GAB',
			'Gambia' => 'GAM',
			'Georgia' => 'GEO',
			'Germany' => 'GER',
			'Ghana' => 'GHA',
			'Greece' => 'GRE',
			'Grenada' => 'GRN',
			'Guam' => 'GUM',
			'Guatemala' => 'GUA',
			'Guinea' => 'GUI',
			'Guinea-Bissau' => 'GBS',
			'Guyana' => 'GUY',
			'Haiti' => 'HAI',
			'Honduras' => 'HON',
			'Hong Kong' => 'HKG',
			'Hungary' => 'HUN',
			'Iceland' => 'ISL',
			'India' => 'IND',
			'Indonesia' => 'INA',
			'Iran' => 'IRI',
			'Iraq' => 'IRQ',
			'Ireland' => 'IRL',
			'Israel' => 'ISR',
			'Italy' => 'ITA',
			'Ivory Coast' => 'CIV',
			'Jamaica' => 'JAM',
			'Japan' => 'JPN',
			'Jordan' => 'JOR',
			'Kazakhstan' => 'KAZ',
			'Kenya' => 'KEN',
			'Kiribati' => 'KIR',
			'Korea' => 'KOR',
			'Kuwait' => 'KUW',
			'Kyrgyzstan' => 'KGZ',
			'Laos' => 'LAO',
			'Latvia' => 'LAT',
			'Lebanon' => 'LIB',
			'Lesotho' => 'LES',
			'Liberia' => 'LBR',
			'Libya' => 'LBA',
			'Liechtenstein' => 'LIE',
			'Lithuania' => 'LTU',
			'Luxembourg' => 'LUX',
			'Macedonia' => 'MKD',
			'Malawi' => 'MAW',
			'Malaysia' => 'MAS',
			'Mali' => 'MLI',
			'Malta' => 'MLT',
			'Mauritania' => 'MTN',
			'Mauritius' => 'MRI',
			'Mexico' => 'MEX',
			'Moldova' => 'MDA',
			'Monaco' => 'MON',
			'Mongolia' => 'MGL',
			'Montenegro' => 'MNE',
			'Morocco' => 'MAR',
			'Mozambique' => 'MOZ',
			'Myanmar' => 'MYA',
			'Namibia' => 'NAM',
			'Nauru' => 'NRU',
			'Nepal' => 'NEP',
			'Netherlands' => 'NED',
			'New Zealand' => 'NZL',
			'Nicaragua' => 'NCA',
			'Niger' => 'NIG',
			'Nigeria' => 'NGR',
			'Norway' => 'NOR',
			'Oman' => 'OMA',
			'Other Countries' => 'OTH',
			'Pakistan' => 'PAK',
			'Palau' => 'PLW',
			'Palestine' => 'PLE',
			'Panama' => 'PAN',
			'Paraguay' => 'PAR',
			'Peru' => 'PER',
			'Philippines' => 'PHI',
			'Poland' => 'POL',
			'Portugal' => 'POR',
			'Puerto Rico' => 'PUR',
			'Qatar' => 'QAT',
			'Romania' => 'ROM',  // actually ROU
			'Russia' => 'RUS',
			'Rwanda' => 'RWA',
			'Samoa' => 'SAM',
			'San Marino' => 'SMR',
			'Saudi Arabia' => 'KSA',
			'Senegal' => 'SEN',
			'Serbia' => 'SCG',  // actually SRB
			'Sierra Leone' => 'SLE',
			'Singapore' => 'SIN',
			'Slovakia' => 'SVK',
			'Slovenia' => 'SLO',
			'Somalia' => 'SOM',
			'South Africa' => 'RSA',
			'Spain' => 'ESP',
			'Sri Lanka' => 'SRI',
			'Sudan' => 'SUD',
			'Suriname' => 'SUR',
			'Swaziland' => 'SWZ',
			'Sweden' => 'SWE',
			'Switzerland' => 'SUI',
			'Syria' => 'SYR',
			'Taiwan' => 'TWN',
			'Tajikistan' => 'TJK',
			'Tanzania' => 'TAN',
			'Thailand' => 'THA',
			'Togo' => 'TOG',
			'Tonga' => 'TGA',
			'Trinidad and Tobago' => 'TRI',
			'Tunisia' => 'TUN',
			'Turkey' => 'TUR',
			'Turkmenistan' => 'TKM',
			'Tuvalu' => 'TUV',
			'Uganda' => 'UGA',
			'Ukraine' => 'UKR',
			'United Arab Emirates' => 'UAE',
			'United Kingdom' => 'GBR',
			'United States of America' => 'USA',
			'Uruguay' => 'URU',
			'Uzbekistan' => 'UZB',
			'Vanuatu' => 'VAN',
			'Venezuela' => 'VEN',
			'Vietnam' => 'VIE',
			'Yemen' => 'YEM',
			'Zambia' => 'ZAM',
			'Zimbabwe' => 'ZIM',
		);

		if (array_key_exists($country, $nations)) {
			$nation = $nations[$country];
		} else {
			$nation = 'OTH';
			if ($country != '')
				trigger_error('Could not map country: ' . $country, E_USER_WARNING);
		}
		return $nation;
	}
} 