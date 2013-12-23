<?php

namespace ManiaControl\Players;


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
	 * @param $login
	 * @param $teamId
	 */
	public function forcePlayerToTeam($adminLogin, $login, $teamId){
		$this->maniaControl->client->query('ForcePlayerTeam', $login, $teamId); //TODO bestätigung und chatnachricht
	}

	/**
	 * Forces a Player to spectator
	 * @param     $adminLogin
	 * @param     $login
	 * @param int $spectatorState
	 */
	public function forcePlayerToSpectator($adminLogin, $login, $spectatorState = self::SPECTATOR_BUT_KEEP_SELECTABLE){
		$this->maniaControl->client->query('ForceSpectator', $login, $spectatorState); //TODO bestätigung und chatnachricht
	}

	public function warnPlayer($adminLogin, $login, $message = ''){

	}

	public function kickPlayer($adminLogin, $login, $message = ''){

	}

	public function banPlayer($adminLogin, $login, $message = ''){

	}
} 