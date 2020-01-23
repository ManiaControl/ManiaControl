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
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Class offering a Configurator for Script Settings
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptSettings implements ConfiguratorMenu, CallbackListener, CommunicationListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_SETTING                     = 'ScriptSetting.';
	const CB_SCRIPTSETTING_CHANGED                  = 'ScriptSettings.SettingChanged';
	const CB_SCRIPTSETTINGS_CHANGED                 = 'ScriptSettings.SettingsChanged';
	const TABLE_SCRIPT_SETTINGS                     = 'mc_scriptsettings';
	const SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN   = 'Load Stored Script-Settings on Map-Begin';
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
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);

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
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SCRIPT_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`settingName` varchar(100) NOT NULL DEFAULT '',
				`settingValue` varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY (`index`),
				UNIQUE KEY `setting` (`serverIndex`, `settingName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Script Settings' AUTO_INCREMENT=1;";

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

		//TODO remove later again (added in v0.165)
		//For Mysql 5.7 add Default Values
		$alterQuery = "ALTER TABLE `" . self::TABLE_SCRIPT_SETTINGS . "` CHANGE settingName settingName varchar(100) DEFAULT ''";
		$result     = $mysqli->query($alterQuery);
		if (!$result) {
			trigger_error($mysqli->error);
			return false;
		}

		$alterQuery = "ALTER TABLE `" . self::TABLE_SCRIPT_SETTINGS . "` CHANGE settingValue settingValue varchar(500) DEFAULT ''";
		$result     = $mysqli->query($alterQuery);
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
		return 'Script Settings';
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
		try {
			$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
		} catch (GameModeException $e) {
			return false;
		}

		$mysqli      = $this->maniaControl->getDatabase()->getMysqli();
		$serverIndex = $this->maniaControl->getServer()->index;
		$query       = "SELECT * FROM `" . self::TABLE_SCRIPT_SETTINGS . "`
				WHERE serverIndex = {$serverIndex};";
		$result      = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$loadedSettings = array();
		while ($row = $result->fetch_object()) {
			if (!isset($scriptSettings[$row->settingName])) {
				continue;
			}
			$loadedSettings[$row->settingName] = $row->settingValue;
			settype($loadedSettings[$row->settingName], gettype($scriptSettings[$row->settingName]));
		}
		$result->free();
		if (empty($loadedSettings)) {
			return true;
		}

		return $this->maniaControl->getClient()->setModeScriptSettings($loadedSettings);
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

		try {
			$scriptInfo = $this->maniaControl->getClient()->getModeScriptInfo();
		} catch (GameModeException $e) {
			$label = new Label();
			$frame->addChild($label);
			$label->setText($e->getMessage());
			return $frame;
		}

		$scriptParams = $scriptInfo->paramDescs;

		try {
			$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
		} catch (GameModeException $e) {
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

		foreach ($scriptParams as $index => $scriptParam) {
			/** @var \Maniaplanet\DedicatedServer\Structures\ScriptSettings $scriptParam */
			$settingName = $scriptParam->name;

			if (!isset($scriptSettings[$settingName])) {
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
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.4, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($settingName);

			$settingValue = $scriptSettings[$settingName];

			if (is_bool($settingValue)) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setX($width / 2 * 0.545);
				$quad->setSize(4, 4);
				$checkBox = new CheckBox(self::ACTION_PREFIX_SETTING . $settingName, $settingValue, $quad);
				$settingFrame->addChild($checkBox);
			} else {
				// Value entry
				$entry = new Entry();
				$settingFrame->addChild($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setX($width / 2 * 0.55);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SETTING . $settingName);
				$entry->setDefault($settingValue);
			}

			$descriptionLabel = new Label();
			$pageFrame->addChild($descriptionLabel);
			$descriptionLabel->setHorizontalAlign($descriptionLabel::LEFT);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
			$descriptionLabel->setSize($width * 0.7, $settingHeight);
			$descriptionLabel->setTextSize($labelTextSize);
			$descriptionLabel->setTranslate(true);
			$nameLabel->addTooltipLabelFeature($descriptionLabel, $scriptParam->desc);

			$posY -= $settingHeight;
		}

		return $frame;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			return;
		}

		try {
			$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
		} catch (GameModeException $e) {
			return;
		}

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$newSettings = array();
		foreach ($configData[3] as $setting) {
			$settingName = substr($setting['Name'], $prefixLength);
			if (!isset($scriptSettings[$settingName])) {
				var_dump('no setting ' . $settingName);
				continue;
			}

			if ($setting['Value'] == $scriptSettings[$settingName]) {
				// Not changed
				continue;
			}

			$newSettings[$settingName] = $setting['Value'];
			settype($newSettings[$settingName], gettype($scriptSettings[$settingName]));
		}

		$success = $this->applyNewScriptSettings($newSettings, $player);
		if ($success) {
			$this->maniaControl->getChat()->sendSuccess('Script Settings saved!', $player);
		} else {
			$this->maniaControl->getChat()->sendError('Script Settings Saving failed!', $player);
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
	private function applyNewScriptSettings(array $newSettings, Player $player) {
		if (empty($newSettings)) {
			return true;
		}

		try {
			$this->maniaControl->getClient()->setModeScriptSettings($newSettings);
		} catch (FaultException $e) {
			return false;
		}


		// Save Settings into Database
		$mysqli    = $this->maniaControl->getDatabase()->getMysqli();
		$query     = "INSERT INTO `" . self::TABLE_SCRIPT_SETTINGS . "` (
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
		$settingsCount = count($newSettings);
		$settingIndex  = 0;
		$title         = $this->maniaControl->getAuthenticationManager()->getAuthLevelName($player);
		$chatMessage   = '$ff0' . $title . ' ' . $player->getEscapedNickname() . ' set ScriptSetting' . ($settingsCount > 1 ? 's' : '') . ' ';
		foreach ($newSettings as $setting => $value) {
			$chatMessage .= '$<' . '$fff' . preg_replace('/^S_/', '', $setting) . '$z$s$ff0 ';
			$chatMessage .= 'to $fff' . $this->parseSettingValue($value) . '$>';

			if ($settingIndex <= $settingsCount - 2) {
				$chatMessage .= ', ';
			}

			// Add To Database
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
			}

			// Trigger own callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SCRIPTSETTING_CHANGED, $setting, $value);

			$settingIndex++;
		}
		$statement->close();

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED);

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
		//Communication Listenings
		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::GET_SCRIPT_SETTINGS, $this, function ($data) {
			try {
				$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
			} catch (GameModeException $e) {
				return new CommunicationAnswer($e->getMessage(), true);
			}

			return new CommunicationAnswer($scriptSettings);
		});

		$this->maniaControl->getCommunicationManager()->registerCommunicationListener(CommunicationMethods::SET_SCRIPT_SETTINGS, $this, function ($data) {
			if (!is_object($data) || !property_exists($data, "scriptSettings")) {
				return new CommunicationAnswer("No valid ScriptSettings provided!", true);
			}

			try {
				$scriptSettings = $this->maniaControl->getClient()->getModeScriptSettings();
			} catch (GameModeException $e) {
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
