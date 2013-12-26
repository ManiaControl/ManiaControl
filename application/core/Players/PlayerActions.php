<?php

namespace ManiaControl\Players;


use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;

/**
 * PlayerActions Class
 *
 * @author steeffeen & kremsy
 */
class PlayerActions {
	/**
	 * Constants
	 */
	const BLUE_TEAM = 0;
	const RED_TEAM = 1;

	const SPECTATOR_USER_SELECTABLE = 0;
	const SPECTATOR_SPECTATOR = 1;
	const SPECTATOR_PLAYER = 2;
	const SPECTATOR_BUT_KEEP_SELECTABLE = 3;
	/**
	 * Private properties
	 */
	private $maniaControl = null;

	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Forces a Player to a certain team
	 * @param $adminLogin
	 * @param $targetLogin
	 * @param $teamId
	 */
	public function forcePlayerToTeam($adminLogin, $targetLogin, $teamId){ //TODO get used by playercommands
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		if($target->isSpectator){
			$success = $this->maniaControl->client->query('ForceSpectator', $targetLogin, self::SPECTATOR_PLAYER);
			if (!$success) {
				$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
				return;
			}
		}

		$success = $this->maniaControl->client->query('ForcePlayerTeam', $targetLogin, $teamId); //TODO bestätigung

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		if($teamId == self::BLUE_TEAM){
			$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Blue-Team!');
			$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' forced player '. Formatter::stripCodes($target->nickname) . ' into the Blue-Team');
		}else if($teamId == self::RED_TEAM){
			$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Red-Team!');
			$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' forced player '. Formatter::stripCodes($target->nickname) . ' into the Red-Team');
		}
	}

	/**
	 * Forces a Player to spectator
	 * @param     $adminLogin
	 * @param     $targetLogin
	 * @param int $spectatorState
	 */
	public function forcePlayerToSpectator($adminLogin, $targetLogin, $spectatorState = self::SPECTATOR_BUT_KEEP_SELECTABLE){ //TODO get used by playercommands
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		$success = $this->maniaControl->client->query('ForceSpectator', $targetLogin, $spectatorState); //TODO bestätigung
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> to spectator!');

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' forced player '. Formatter::stripCodes($target->nickname) . ' to Spectator');
	}

	/**
	 * Warn a Player
	 * @param        $adminLogin
	 * @param        $targetLogin
	 */
	public function warnPlayer($adminLogin, $targetLogin){ //TODO chatcommand
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		// display warning message
		$message = '$s$f00This is an administrative warning.{br}{br}$gWhatever you wrote or you have done is against {br} our server\'s policy.
						{br}Not respecting other players, or{br}using offensive language might result in a{br}$f00kick, or ban $ff0the next time.
						{br}{br}$gThe server administrators.';
		$message = preg_split('/{br}/', $message);


		$width = 80;
		$height = 50;
		$quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width,$height);
		$frame->setPosition(0, 10);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width,$height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.473, $height * 0.457, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		//Headline Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setHAlign(Control::CENTER);
		$label->setX(0);
		$label->setY($height / 2 - 5);
		$label->setStyle(Label_Text::STYLE_TextCardMedium);
		$label->setTextSize(4);
		$label->setText('Administrative Warning');
		$label->setTextColor('F00');

		$y = $height / 2 - 15;
		foreach ($message as &$line){
			//Warn Labels
			$label = new Label_Text();
			$frame->add($label);
			$label->setHAlign(Control::CENTER);
			//$label->setX(-$width / 2 + 5);
			$label->setX(0);
			$label->setY($y);
			$label->setStyle(Label_Text::STYLE_TextCardMedium);
			$label->setTextSize(1.6);
			$label->setText($line);
			$label->setTextColor('FF0');
			$y -= 4;
		}


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $target);

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' warned player '. Formatter::stripCodes($target->nickname));

		// show chat message
		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> warned $<' . $target->nickname . '$>!');
	}


	/**
	 * Kicks a Player
	 * @param        $adminLogin
	 * @param        $targetLogin
	 * @param string $message
	 */
	public function kickPlayer($adminLogin, $targetLogin, $message = ''){
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		$success = $this->maniaControl->client->query('Kick', $target->login, $message); //TODO bestätigung
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> kicked $<' . $target->nickname . '$>!');

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' kicked player '. Formatter::stripCodes($target->nickname));
	}

	/**
	 * Bans a Player
	 * @param        $adminLogin
	 * @param        $targetLogin
	 * @param string $message
	 */
	public function banPlayer($adminLogin, $targetLogin, $message = ''){
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		$success = $this->maniaControl->client->query('Ban', $target->login, $message); //TODO bestätigung

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> banned $<' . $target->nickname . '$>!');

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' banned player '. Formatter::stripCodes($target->nickname));
	}

	/**
	 * Grands Player an authorization level
	 * @param $adminLogin
	 * @param $targetLogin
	 * @param $authLevel
	 */
	public function grandAuthLevel($adminLogin, $targetLogin, $authLevel){
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);
		//TODO check for bot
		if($this->maniaControl->authenticationManager->checkRight($target,$authLevel)){
			$this->maniaControl->chat->sendError('This admin is already ' . $this->maniaControl->authenticationManager->getAuthLevelName($target->authLevel), $admin->login);
			return;
		}

		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, $authLevel);

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		$authLevelName = $this->maniaControl->authenticationManager->getAuthLevelName($authLevel);

		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> added $<' . $target->nickname . '$> as $< ' . $authLevelName. '$>!');

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' added player '. Formatter::stripCodes($target->nickname) . ' as ' . $authLevelName);
	}

	/**
	 * Revokes all rights from a Admin
	 * @param $adminLogin
	 * @param $targetLogin
	 */
	public function revokeAuthLevel($adminLogin, $targetLogin){
		$admin = $this->maniaControl->playerManager->getPlayer($adminLogin);
		$target = $this->maniaControl->playerManager->getPlayer($targetLogin);
		$title = $this->maniaControl->authenticationManager->getAuthLevelName($admin->authLevel);

		if($this->maniaControl->authenticationManager->checkRight($target,AuthenticationManager::AUTH_LEVEL_MASTERADMIN)){
			$this->maniaControl->chat->sendError('MasterAdmins can\'t be removed ', $admin->login);
			return;
		}

		$success = $this->maniaControl->authenticationManager->grantAuthLevel($target, AuthenticationManager::AUTH_LEVEL_PLAYER);

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		$this->maniaControl->chat->sendInformation($title . ' $<' . $admin->nickname . '$> revokes $<' . $target->nickname . '$> rights!');

		// log console message
		$this->maniaControl->log($title .' ' . Formatter::stripCodes($admin->nickname) . ' revokes '. Formatter::stripCodes($target->nickname) . ' rights');
	}
} 