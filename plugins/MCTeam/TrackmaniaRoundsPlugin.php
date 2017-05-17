<?php

namespace MCTeam;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\Structures\TrackMania\OnPointsRepartitionStructure;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl ServerRanking Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TrackmaniaRoundsPlugin implements Plugin, CommandListener {
	/*
	 * Constants
	 */
	const PLUGIN_ID      = 6;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'Trackmania Rounds Plugin';
	const PLUGIN_AUTHOR  = 'MCTeam';

	const MAX_POINT_DISTRIBUTIONS = 8;

	const SETTING_PERMISSION_TM_HANDLE_POINTS_REPARTITION = 'Handle Points Distribution Settings';
	const SETTING_POINT_DISTRIBUTION_NAME                 = 'Server Distribution Name ';
	const SETTING_POINT_DISTRIBUTION_VALUE                = 'Server Distribution Value ';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl * */
	private $maniaControl = null;


	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		//Authentication Permission Level
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_PERMISSION_TM_HANDLE_POINTS_REPARTITION, AuthenticationManager::getPermissionLevelNameArray(AuthenticationManager::AUTH_LEVEL_ADMIN));

		//Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POINT_DISTRIBUTION_NAME . 1, "motogp");
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POINT_DISTRIBUTION_VALUE . 1, "25,20,16,13,11,10,9,8,7,6,5,4,3,2,1,1,1,1,1");

		for ($i = 2; $i <= self::MAX_POINT_DISTRIBUTIONS; $i++) {
			$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POINT_DISTRIBUTION_NAME . $i, "distribution " . $i);
			$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_POINT_DISTRIBUTION_VALUE . $i, "");
		}

		// Commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('setrpoints',
		                                                                        'setpointsdistribution'), $this, 'commandSetPointsRepartition', true, 'Sets the Rounds Point Repartition.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('getrpoints',
		                                                                        'getpointsdistribution'), $this, 'commandGetPointsRepartition', true, 'Gets the Rounds Point Repartition.');
	}

	/**
	 * Handle //setpointsrepartition command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function commandSetPointsRepartition(array $chatCallback, Player $player) {
		$permission = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PERMISSION_TM_HANDLE_POINTS_REPARTITION);
		if (!AuthenticationManager::checkRight($player, $permission)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		// Check for delayed shutdown
		$params = explode(' ', $chatCallback[1][2]);
		if (count($params) >= 1) {
			$pointString = $params[1];
			$pointArray  = explode(',', $pointString);

			if (count($pointArray) > 0) {
				for ($i = 1; $i <= self::MAX_POINT_DISTRIBUTIONS; $i++) {
					$name = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POINT_DISTRIBUTION_NAME . $i);

					if ($name == $pointString) {
						$pointString = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_POINT_DISTRIBUTION_VALUE . $i);
						$pointArray  = explode(',', $pointString);
						break;
					}
				}
			}

			$this->maniaControl->getModeScriptEventManager()->setTrackmaniaPointsRepartition($pointArray);
			$this->maniaControl->getChat()->sendInformation('Points Distribution Changed!', $player);
			$this->commandGetPointsRepartition($chatCallback, $player);
		} else {
			$this->maniaControl->getChat()->sendError('You must provide a point Distribution in the following form: 10,8,6,4,3 !', $player);
		}

	}

	/**
	 * Handle //getpointsrepartition command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function commandGetPointsRepartition(array $chatCallback, Player $player) {
		$permission = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PERMISSION_TM_HANDLE_POINTS_REPARTITION);
		if (!AuthenticationManager::checkRight($player, $permission)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$this->maniaControl->getModeScriptEventManager()->getTrackmaniaPointsRepartition()->setCallable(function (OnPointsRepartitionStructure $structure) use ($player) {
			$pointRepartitionString = "";
			foreach ($structure->getPointsRepartition() as $points) {
				$pointRepartitionString .= $points . ',';
			}
			$pointRepartitionString = substr($pointRepartitionString, 0, -1);

			$this->maniaControl->getChat()->sendInformation('Current Points Distribution: ' . $pointRepartitionString, $player);
		});
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "Plugin offers simple functionalites for Trackmania Round, Team and Cup Modes";
	}


	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

}
