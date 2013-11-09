<?php

namespace ManiaControl;

/**
 * Class handling authentication levels
 *
 * @author steeffeen
 */
class Authentication {

	/**
	 * Constants
	 */
	public $RIGHTS_LEVELS = array(-1 => 'none', 0 => 'superadmin', 1 => 'admin', 2 => 'operator', 3 => 'all');

	/**
	 * Private properties
	 */
	private $mc = null;

	private $config = null;

	/**
	 * Construct authentication manager
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		// Load config
		$this->config = Tools::loadConfig('authentication.ManiaControl.xml');
	}

	/**
	 * Check if the player has enough rights
	 *
	 * @param string $login        	
	 * @param string $defaultRight        	
	 * @param string $neededRight        	
	 * @return bool
	 */
	public function checkRight($login, $neededRight) {
		$right = $this->getRights($login);
		return $this->compareRights($right, $neededRight);
	}

	/**
	 * Compare if the rights are enough
	 *
	 * @param string $hasRight        	
	 * @param string $neededRight        	
	 * @return bool
	 */
	public function compareRights($hasRight, $neededRight) {
		if (!in_array($hasRight, $this->RIGHTS_LEVELS) || !in_array($neededRight, $this->RIGHTS_LEVELS)) {
			return false;
		}
		$hasLevel = array_search($hasRight, $this->RIGHTS_LEVELS);
		$neededLevel = array_search($neededRight, $this->RIGHTS_LEVELS);
		if ($hasLevel > $neededLevel) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Get rights of the given login
	 *
	 * @param string $login        	
	 * @param string $defaultRights        	
	 * @return string
	 */
	public function getRights($login, $defaultRight = 'all') {
		$groups = $this->config->xpath('//login[text()="' . $login . '"]/..');
		if (empty($groups)) return $defaultRight;
		$right = $defaultRight;
		$rightLevel = array_search($right, $this->RIGHTS_LEVELS);
		foreach ($groups as $group) {
			$level = array_search($group->getName(), $this->RIGHTS_LEVELS);
			if ($level === false) continue;
			if ($level < $rightLevel || $rightLevel === false) {
				$right = $group->getName();
				$rightLevel = $level;
			}
		}
		return $right;
	}

	/**
	 * Sends an error message to the login
	 *
	 * @param string $login        	
	 */
	public function sendNotAllowed($login) {
		if (!$this->mc->chat->sendError('You do not have the required rights to perform this command!', $login)) {
			trigger_error("Couldn't send forbidden message to login '" . $login . "'. " . $this->mc->getClientErrorText());
		}
	}
}

?>
