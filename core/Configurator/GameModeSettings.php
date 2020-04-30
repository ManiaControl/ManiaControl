<?php

namespace ManiaControl\Configurator;

use FML\Components\CheckBox;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Communication\CommunicationAnswer;
use ManiaControl\Communication\CommunicationListener;
use ManiaControl\Communication\CommunicationMethods;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\GameInfos;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Class offering a Configurator for Mode Settings
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class GameModeSettings implements ConfiguratorMenu, CallbackListener, CommunicationListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_SETTING                     = 'GameModeSetting.';
	const CB_GAMEMODESETTING_CHANGED                = 'GameModeSettings.SettingChanged';
	const CB_GAMEMODESETTINGS_CHANGED               = 'GameModeSettings.SettingsChanged';
	/** @deprecated */
	const CB_SCRIPTSETTING_CHANGED                  = 'GameModeSettings.SettingChanged';
	/** @deprecated */
	const CB_SCRIPTSETTINGS_CHANGED                 = 'GameModeSettings.SettingsChanged';
	const TABLE_GAMEMODE_SETTINGS                   = 'mc_gamemodesettings';
	/** @deprecated */
	const TABLE_SCRIPT_SETTINGS                     = 'mc_scriptsettings';
	const SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN   = 'Load Stored GameMode-Settings on Map-Begin';
	const SETTING_PERMISSION_CHANGE_MODE_SETTINGS   = 'Change GameMode-Settings';
	/** @deprecated */
	const SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS = 'Change Script-Settings';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new script settings instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'onBeginMap');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN, false);

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_MODE_SETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);

		//TODO remove to somewhere cleaner
		//Communication Listenings
		$this->initalizeCommunicationListenings();
	}

	/**
	 * Create all necessary database tables
	 *
	 * @return boolean
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_GAMEMODE_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`settingName` varchar(100) NOT NULL DEFAULT '',
				`settingValue` varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY (`index`),
				UNIQUE KEY `setting` (`serverIndex`, `settingName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Mode Settings' AUTO_INCREMENT=1;";

		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$statement->execute();
		if ($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}

		$dropQuery = "DROP TABLE IF EXISTS `" . self::TABLE_SCRIPT_SETTINGS . "`;";
		$result    = $mysqli->query($dropQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$statement->close();
		return true;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return 'GameMode-Settings';
	}

	/**
	 * Get the settings of the GameMode into an Array
	 * @return array|false
	 */
	public function getGameModeSettingsArray() {
		if ($this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
			return $this->maniaControl->getClient()->getModeScriptSettings();
		} else {
			$gameModeSettings = get_object_vars($this->maniaControl->getClient()->getCurrentGameInfo());
			foreach ($gameModeSettings as $name => $value) {
				unset($gameModeSettings[$name]);
				$gameModeSettings[ucfirst($name)] = $value;
			}
			return $gameModeSettings;
		}
	}

	/**
	 * Set the settings of the GameMode from an Array.
	 * Returns true, if successful.
	 * @param array $settings
	 * @return bool
	 */
	public function setGameModeSettingsArray(array $settings) {
		if ($this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
			return $this->maniaControl->getClient()->setModeScriptSettings($settings);
		} else {
			$success = true;
			foreach ($settings as $key => $value) {
				$success &= $this->maniaControl->getClient()->execute('Set'.$key, array($value));
			}
			return $success;
		}
	}

	/**
	 * Handle OnInit callback
	 */
	public function onInit() {
		$this->loadSettingsFromDatabase();
	}

	/**
	 * Load Settings from Database
	 *
	 * @return bool
	 */
	public function loadSettingsFromDatabase() {
		$gameModeSettings = null;
		try {
			$gameModeSettings = $this->getGameModeSettingsArray();
		} catch (\Exception $e) {
			return false;
		}

		$mysqli      = $this->maniaControl->getDatabase()->getMysqli();
		$serverIndex = $this->maniaControl->getServer()->index;
		$query       = "SELECT * FROM `" . self::TABLE_GAMEMODE_SETTINGS . "`
				WHERE serverIndex = {$serverIndex};";
		$result      = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$loadedSettings = array();
		while ($row = $result->fetch_object()) {
			if (!isset($gameModeSettings[$row->settingName])) {
				continue;
			}
			$loadedSettings[$row->settingName] = $row->settingValue;
			settype($loadedSettings[$row->settingName], gettype($gameModeSettings[$row->settingName]));
		}
		$result->free();
		if (empty($loadedSettings)) {
			return true;
		}

		try {
			$this->setGameModeSettingsArray($loadedSettings);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function onBeginMap() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN)) {
			$this->loadSettingsFromDatabase();
		}
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		$scriptParams = null;
		$gameModeSettings = null;
		$error = null;
		try {
			$gameModeSettings = $this->getGameModeSettingsArray();

			if ($this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
				$scriptInfo = $this->maniaControl->getClient()->getModeScriptInfo();
				$scriptParams = $scriptInfo->paramDescs;
			} else {
				$scriptParams = $gameModeSettings;
			}
		} catch (\Exception $e) {
			$label = new Label();
			$frame->addChild($label);
			$label->setText($e->getMessage());
			return $frame;
		}

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->addChild($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle($pagerPrev::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->addChild($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle($pagerNext::SUBSTYLE_ArrowNext);

		$paging->addButtonControl($pagerNext);
		$paging->addButtonControl($pagerPrev);

		$pageCountLabel = new Label_Text();
		$frame->addChild($pageCountLabel);
		$pageCountLabel->setHorizontalAlign($pageCountLabel::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		// Setting pages
		$pageFrame = null;
		$posY      = 0.;
		$index     = 0;

		foreach ($scriptParams as $key => $scriptParam) {
			$settingName = null;
			if ($this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
				$settingName = $scriptParam->name;
			} else {
				$settingName = $key;
			}

			if (!isset($gameModeSettings[$settingName])) {
				continue;
			}

			if ($index % 13 === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height * 0.41;
				$paging->addPageControl($pageFrame);
			}

			$settingFrame = new Frame();
			$pageFrame->addChild($settingFrame);
			$settingFrame->setY($posY);

			$nameLabel = new Label_Text();
			$settingFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setSize($width * 0.4, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setText($settingName);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setX($width * -0.46);

			$settingValue = $gameModeSettings[$settingName];

			if (is_bool($settingValue)) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setSize(4, 4);
				$quad->setX($width / 2 * 0.545);
				$checkBox = new CheckBox(self::ACTION_PREFIX_SETTING . $settingName, $settingValue, $quad);
				$settingFrame->addChild($checkBox);
			} else {
				// Value entry
				$entry = new Entry();
				$settingFrame->addChild($entry);
				$entry->setDefault($settingValue);
				$entry->setName(self::ACTION_PREFIX_SETTING . $settingName);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setTextSize(1);
				$entry->setX($width / 2 * 0.55);
			}

			if ($this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
				$descriptionLabel = new Label();
				$pageFrame->addChild($descriptionLabel);
				$descriptionLabel->setHorizontalAlign($descriptionLabel::LEFT);
				$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
				$descriptionLabel->setSize($width * 0.7, $settingHeight);
				$descriptionLabel->setTextSize($labelTextSize);
				$descriptionLabel->setTranslate(true);
				$nameLabel->addTooltipLabelFeature($descriptionLabel, $scriptParam->desc);
			}

			$posY -= $settingHeight;
			$index++;
		}

		return $frame;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MODE_SETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			return;
		}
		
		$gameModeSettings = null;
		try {
			$gameModeSettings = $this->getGameModeSettingsArray();
		} catch (\Exception $e) {
			return;
		}

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$newSettings = array();
		foreach ($configData[3] as $setting) {
			$settingName = substr($setting['Name'], $prefixLength);
			if (!isset($gameModeSettings[$settingName])) {
				var_dump('no setting ' . $settingName);
				continue;
			}

			if ($setting['Value'] == $gameModeSettings[$settingName]) {
				// Not changed
				continue;
			}

			$newSettings[$settingName] = $setting['Value'];
			settype($newSettings[$settingName], gettype($gameModeSettings[$settingName]));
		}

		$success = $this->applyNewModeSettings($newSettings, $player);
		if ($success) {
			$this->maniaControl->getChat()->sendSuccess('GameMode-Settings saved!', $player);
		} else {
			$this->maniaControl->getChat()->sendError('GameMode-Settings Saving failed!', $player);
		}

		// Reopen the Menu
		$this->maniaControl->getConfigurator()->showMenu($player, $this);
	}


	/**
	 * Apply the Array of new Script Settings
	 *
	 * @param array  $newSettings
	 * @param Player $player
	 * @return bool
	 */
	private function applyNewModeSettings(array $newSettings, Player $player) {
		if (empty($newSettings)) {
			return true;
		}

		try {
			$success = $this->setGameModeSettingsArray($newSettings);
			if (!$success) {
				return false;
			}
		} catch (\Exception $e) {
			$this->maniaControl->getChat()->sendException($e, $player);
			return false;
		}

		// Save Settings into Database
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "INSERT INTO `" . self::TABLE_GAMEMODE_SETTINGS . "` (
				`serverIndex`,
				`settingName`,
				`settingValue`
				) VALUES (
				?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`settingValue` = VALUES(`settingValue`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$settingName  = null;
		$settingValue = null;
		$statement->bind_param('iss', $this->maniaControl->getServer()->index, $settingName, $settingValue);

		// Notifications
		if (!$this->maniaControl->getServer()->getScriptManager()->isScriptMode()) {
			$this->maniaControl->getChat()->sendInformation('Changes will only take effect after a map skip or restart!', $player);
		}

		$settingsCount = count($newSettings);
		$settingIndex  = 0;
		$title         = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($player);
		$chatMessage   = '$ff0' . $title . ' ' . $player->getEscapedNickname() . ' set GameMode-Setting' . ($settingsCount > 1 ? 's' : '') . ' ';
		foreach ($newSettings as $settingName => $settingValue) {
			$chatMessage .= '$<' . '$fff' . preg_replace('/^S_/', '', $settingName) . '$z$ff0 ';
			$chatMessage .= 'to $fff' . $this->parseSettingValue($settingValue) . '$>';

			if ($settingIndex <= $settingsCount - 2) {
				$chatMessage .= ', ';
			}

			// Add To Database
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
			}

			// Trigger own callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_GAMEMODESETTING_CHANGED, $settingName, $settingValue);

			$settingIndex++;
		}
		$statement->close();

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_GAMEMODESETTINGS_CHANGED);

		$chatMessage .= '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
		return true;
	}

	/**
	 * Parse the Setting Value to a String Representation
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function parseSettingValue($value) {
		if (is_bool($value)) {
			return ($value ? 'True' : 'False');
		}
		return (string) $value;
	}

	/**
	 * Initializes the communication Listenings
	 */
	private function initalizeCommunicationListenings() {
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_GAMEMODE_SETTINGS, $this, function ($data) {
			try {
				$gameModeSettings = $this->getGameModeSettingsArray();
			} catch (\Exception $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}

			return new CommunicationAnswer($gameModeSettings);
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SET_GAMEMODE_SETTINGS, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "gameModeSettings")) {
				return new CommunicationAnswer("No valid GameMode-Settings provided!", true);
			}

			$gameModeSettings = null;
			try {
				$gameModeSettings = $this->getGameModeSettingsArray();
			} catch (\Exception $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}

			$newSettings = array();
			foreach ($data->gameModeSettings as $name => $value) {
				if (!isset($gameModeSettings[$name])) {
					var_dump('no setting ' . $name);
					continue;
				}

				if ($value == $gameModeSettings[$name]) {
					// unchanged
					continue;
				}

				$newSettings[$name] = $value;
				settype($newSettings[$name], gettype($gameModeSettings[$name]));
			}

			// No new Settings
			if (empty($newSettings)) {
				return new CommunicationAnswer(array("success" => true));
			}

			// Trigger GameModeSettings Changed Callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_GAMEMODESETTINGS_CHANGED);

			// Set the Settings
			try {
				$success = $this->setGameModeSettingsArray($newSettings);
				return new CommunicationAnswer(array("success" => $success));
			} catch (\Exception $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}
		});

		/** @deprecated */
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_SCRIPT_SETTINGS, $this, function ($data) {
			try {
				$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
			} catch (\Exception $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}

			return new CommunicationAnswer($scriptSettings);
		});

		/** @deprecated */
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SET_SCRIPT_SETTINGS, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "scriptSettings")) {
				return new CommunicationAnswer("No valid ScriptSettings provided!", true);
			}

			try {
				$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
			} catch (\Exception $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}

			$newSettings = array();
			foreach ($data->scriptSettings as $name => $value) {
				if (!isset($scriptSettings[$name])) {
					var_dump('no setting ' . $name);
					continue;
				}

				if ($value == $scriptSettings[$name]) {
					// Not changed
					continue;
				}

				$newSettings[$name] = $value;
				settype($newSettings[$name], gettype($scriptSettings[$name]));
			}

			//No new Settings
			if (empty($newSettings)) {
				return new CommunicationAnswer(array("success" => true));
			}

			//Trigger Scriptsettings Changed Callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED);

			//Set the Settings
			$success = $this->maniaControl->getClient()->setModeScriptSettings($newSettings);

			return new CommunicationAnswer(array("success" => $success));
		});
	}
}
