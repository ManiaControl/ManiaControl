<?php

namespace ManiaControl\Players;


use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\ManiaControl;

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
		if($target->isSpectator){
			$success = $this->maniaControl->client->query('ForceSpectator', $targetLogin, self::SPECTATOR_PLAYER);
			if (!$success) {
				$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
				return;
			}
		}

		$success = $this->maniaControl->client->query('ForcePlayerTeam', $targetLogin, $teamId); //TODO bestätigung und chatnachricht

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		if($teamId == self::BLUE_TEAM)
			$this->maniaControl->chat->sendInformation('$<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Blue-Team!');
		else if($teamId == self::RED_TEAM)
			$this->maniaControl->chat->sendInformation('$<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> into the Red-Team!');
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

		$success = $this->maniaControl->client->query('ForceSpectator', $targetLogin, $spectatorState); //TODO bestätigung und chatnachricht
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}

		$this->maniaControl->chat->sendInformation('$<' . $admin->nickname . '$> forced $<' . $target->nickname . '$> to spectator!'); //TODO add admin title
	}

	/**
	 * Warn a Player
	 * @param        $adminLogin
	 * @param        $login
	 * @param string $message
	 */
	public function warnPlayer($adminLogin, $login, $message = ''){

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

		$success = $this->maniaControl->client->query('Kick', $target->login, $message); //TODO bestätigung und chatnachricht
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		$this->maniaControl->chat->sendInformation('$<' . $admin->nickname . '$> kicked $<' . $target->nickname . '$>!'); //TODO add admin title
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

		$success = $this->maniaControl->client->query('Ban', $target->login, $message); //TODO bestätigung und chatnachricht

		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $admin->login);
			return;
		}
		$this->maniaControl->chat->sendInformation('$<' . $admin->nickname . '$> banned $<' . $target->nickname . '$>!'); //TODO add admin title
	}
} 