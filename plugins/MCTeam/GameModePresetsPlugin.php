<?php

namespace MCTeam;

use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Commands\CommandListener;
use ManiaControl\Files\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl GameMode Presets Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class GameModePresetsPlugin implements Plugin, CommandListener, TimerListener {
	/*
	 * Constants
	 */
	const PLUGIN_ID      = 9;
	const PLUGIN_VERSION = 0.12;
	const PLUGIN_NAME    = 'GameMode Presets Plugin';
	const PLUGIN_AUTHOR  = 'MCTeam';

	const PRESET_SETTING_MODE_NUMBER = 'Mode Number';
	const PRESET_SETTING_SCRIPT_NAME = 'Script Name';

	const SETTING_MAP_ACTION_ON_LOADMODE          = 'Map Action on //loadmode';
	const SETTING_MAP_ACTION_DELAY_ON_LOADMODE    = 'Map Action Delay on //loadmode (in ms)';
	const SETTING_PERMISSION_LOAD_GAMEMODE_PRESET = 'Permission load GameMode Preset';
	const SETTING_PERMISSION_SAVE_GAMEMODE_PRESET = 'Permission save GameMode Preset';

	const MAP_ACTION_ON_LOADMODE_NONE    = 'None';
	const MAP_ACTION_ON_LOADMODE_RESTART = 'Restart Map';
	const MAP_ACTION_ON_LOADMODE_SKIP    = 'Skip Map';

	const TABLE_GAMEMODEPRESETS = 'mc_gamemodepresets';

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

		// Authentication Permission Level
		$this->maniaControl->getAuthenticationManager()->definePluginPermissionLevel(
			$this,
			self::SETTING_PERMISSION_LOAD_GAMEMODE_PRESET,
			AuthenticationManager::AUTH_LEVEL_ADMIN
		);
		$this->maniaControl->getAuthenticationManager()->definePluginPermissionLevel(
			$this,
			self::SETTING_PERMISSION_SAVE_GAMEMODE_PRESET,
			AuthenticationManager::AUTH_LEVEL_SUPERADMIN
		);

		// Settings
		$this->maniaControl->getSettingManager()->initSetting(
			$this,
			self::SETTING_MAP_ACTION_ON_LOADMODE,
			array(
				self::MAP_ACTION_ON_LOADMODE_NONE,
				self::MAP_ACTION_ON_LOADMODE_RESTART,
				self::MAP_ACTION_ON_LOADMODE_SKIP,
			)
		);
		$this->maniaControl->getSettingManager()->initSetting(
			$this,
			self::SETTING_MAP_ACTION_DELAY_ON_LOADMODE,
			1000
		);
		
		// Commands
		$this->maniaControl->getCommandManager()->registerCommandListener(array('loadmode', 'modeload'), $this, 'commandLoadMode', true, 'Loads the mode settings from the given preset name.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('savemode', 'modesave'), $this, 'commandSaveMode', true, 'Saves the mode settings under the given preset name.');
		$this->maniaControl->getCommandManager()->registerCommandListener(array('showmode', 'modeshow'), $this, 'commandShowMode', true, 'Shows the available game mode presets.');

		$this->initTables();
	}

	/**
	 * Initialize needed database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_GAMEMODEPRESETS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(20) NOT NULL,
				`settings` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				PRIMARY KEY (`index`),
				UNIQUE KEY (`name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}
	}

	/**
	 * Fetch preset from database
	 * @param string $name
	 * @return array|null
	 */
	private function fetchPreset($name) {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT `settings`
				FROM `" . self::TABLE_GAMEMODEPRESETS . "`
				WHERE `name` LIKE ?;";

		$statement = $mysqli->prepare($query);
		if ($mysqli->error || !$statement) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return null;
		}

		$statement->bind_param('s', $name);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			$statement->close();
			return null;
		}

		$settings = null;
		$statement->store_result();
		$statement->bind_result($settings);
		$statement->fetch();
		$statement->free_result();

		return json_decode($settings, true);
	}

	/**
	 * Fetch preset from database
	 * @param string $name
	 * @return array|null
	 */
	private function storePreset($name) {
		$modeNumber = $this->maniaControl->getClient()->getGameMode();
		$scriptName = $this->maniaControl->getClient()->getScriptName()['CurrentValue'];
		$settings = null;
		if ($modeNumber === 0) {
			$settings = $this->maniaControl->getClient()->getModeScriptSettings();
		} else {
			$settings = $this->maniaControl->getClient()->execute('GetCurrentGameInfo');
		}

		$settings[self::PRESET_SETTING_MODE_NUMBER] = $modeNumber;
		$settings[self::PRESET_SETTING_SCRIPT_NAME] = $scriptName;
		$settings = json_encode($settings);

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "INSERT INTO `" . self::TABLE_GAMEMODEPRESETS . "`
				(`name`, `settings`) VALUES (?, ?)
				ON DUPLICATE KEY UPDATE `settings` = VALUES(`settings`);";

		$statement = $mysqli->prepare($query);
		if ($mysqli->error || !$statement) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}

		$statement->bind_param('ss', $name, $settings);
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}

		$statement->close();
		return true;
	}

	/**
	 * Load Script
	 * @param string $scriptName
	 */
	private function loadScript($scriptName) {
        static $scriptsDir = null;
        if ($scriptsDir === null)
        {
            $scriptsDataDir = FileUtil::shortenPath($this->maniaControl->getServer()->getDirectory()->getScriptsFolder());
            if ($this->maniaControl->getServer()->checkAccess($scriptsDataDir))
            {
                $gameShort = $this->maniaControl->getMapManager()->getCurrentMap()->getGame();
                $game = '';
                switch ($gameShort)
                {
                    case 'qm': $game = 'QuestMania'; break;
                    case 'sm': $game = 'ShootMania'; break;
                    case 'tm': $game = 'TrackMania'; break;
                }

                if ($game != '')
                {
                    $scriptsDir = $scriptsDataDir.DIRECTORY_SEPARATOR.'Modes'.DIRECTORY_SEPARATOR.$game.DIRECTORY_SEPARATOR;
                    if (!$this->maniaControl->getServer()->checkAccess($scriptsDir))
                        $scriptsDir = null;
                }
            }

            if ($scriptsDir === null)
                throw new \Exception('Scripts directory not found, unable to load different scripts!');
		}
		
		$scriptPath = $scriptsDir.$scriptName;
		if (!file_exists($scriptPath))
			throw new \Exception('Script not found ('.$scriptPath.').');

		$scriptText = file_get_contents($scriptPath);

        $this->maniaControl->getClient()->setModeScriptText($scriptText);
        $this->maniaControl->getClient()->setScriptName($scriptName);
	}

	/**
	 * Handle //loadmode command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function commandLoadMode(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::SETTING_PERMISSION_LOAD_GAMEMODE_PRESET)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chatCallback[1][2]);
		if (count($params) < 2) {
			$this->maniaControl->getChat()->sendError('You must provide a gamemode preset name to load settings from!', $player);
			return;
		} elseif (count($params) > 2) {
			$this->maniaControl->getChat()->sendError('You can only provide one gamemode preset name to load settings from!', $player);
			return;
		}

		$presetName = strtolower($params[1]);
		$presetSettings = $this->fetchPreset($presetName);
		if (!$presetSettings) {
			$this->maniaControl->getChat()->sendError('The gamemode preset $<$g$z$fff' . $presetName . '$> does not exist, use $<$g$z$fff//showmode$> to see the available presets!', $player);
			return;
		}

		$modeNumber = $presetSettings[self::PRESET_SETTING_MODE_NUMBER];
		$scriptName = $presetSettings[self::PRESET_SETTING_SCRIPT_NAME];
		unset($presetSettings[self::PRESET_SETTING_MODE_NUMBER]);
		unset($presetSettings[self::PRESET_SETTING_SCRIPT_NAME]);

		// this is a hack, because this setting always throws errors otherwise
		$presetSettings['S_MatchmakingRematchRatio'] = floatval($presetSettings['S_MatchmakingRematchRatio']);

		try {
			$this->maniaControl->getClient()->setGameMode($modeNumber);
			if ($modeNumber === 0) {
				$this->loadScript($scriptName);
        		$this->maniaControl->getClient()->setModeScriptSettings($presetSettings);
			} else {
				$this->maniaControl->getClient()->setScriptName($scriptName);
				$this->maniaControl->getClient()->execute('SetGameInfos', $presetSettings);
			}
		} catch (\Exception $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			$this->maniaControl->getChat()->sendError('Unable to load gamemode preset $<$fff' . $presetName . '$>!', $player);
			return;
		}
		
		$authLevel = AuthenticationManager::getAuthLevelInt($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PERMISSION_LOAD_GAMEMODE_PRESET));
		$this->maniaControl->getChat()->sendSuccessToAdmins($player->getEscapedNickname() . ' loaded gamemode preset $<$fff' . $presetName . '$>!', $authLevel);
		$this->maniaControl->getTimerManager()->registerOneTimeListening(
			$this,
			function () {
				$mapActionOnLoadmode = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_ACTION_ON_LOADMODE);
				switch ($mapActionOnLoadmode) {
					case self::MAP_ACTION_ON_LOADMODE_NONE:
					break;
					case self::MAP_ACTION_ON_LOADMODE_RESTART:
						$this->maniaControl->getMapManager()->getMapActions()->restartMap();
					break;
					case self::MAP_ACTION_ON_LOADMODE_SKIP:
						$this->maniaControl->getMapManager()->getMapActions()->skipMap();
					break;
				}
			},
			$this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_ACTION_DELAY_ON_LOADMODE)
		);
	}

	/**
	 * Handle //savemode command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function commandSaveMode(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::SETTING_PERMISSION_SAVE_GAMEMODE_PRESET)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$params = explode(' ', $chatCallback[1][2]);
		if (count($params) < 2 || empty($params[1])) {
			$this->maniaControl->getChat()->sendError('You must provide a gamemode preset name to save settings into!', $player);
			return;
		} elseif (count($params) > 2) {
			$this->maniaControl->getChat()->sendError('You can only provide one gamemode preset name to save settings into!', $player);
			return;
		}

		$presetName = strtolower($params[1]);
		try {
			$this->storePreset($presetName);
		} catch (\Exception $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			$this->maniaControl->getChat()->sendError('Unable to save gamemode preset $<$fff' . $presetName . '$>!', $player);
			return;
		}

		$authLevel = AuthenticationManager::getAuthLevelInt($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_PERMISSION_SAVE_GAMEMODE_PRESET));
		$this->maniaControl->getChat()->sendSuccessToAdmins($player->getEscapedNickname() . ' saved gamemode settings in preset $<$fff' . $presetName . '$>!', $authLevel);
	}

	/**
	 * Handle //showmode command
	 *
	 * @param array                        $chatCallback
	 * @param \ManiaControl\Players\Player $player
	 */
	public function commandShowMode(array $chatCallback, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPluginPermission($this, $player, self::SETTING_PERMISSION_LOAD_GAMEMODE_PRESET)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}

		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "SELECT `name`
				FROM `" . self::TABLE_GAMEMODEPRESETS . "`;";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		$presets = array();
		while ($preset = $result->fetch_object()) {
			array_push($presets, $preset->name);
		}
		$result->free();

		$this->maniaControl->getChat()->sendInformation('Available presets: $<$g$z$fff' . implode(', ', $presets) . '$>', $player);
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
		return "Plugin offers presets functionalites for GameModes";
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}
}
