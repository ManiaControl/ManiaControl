<?php

namespace ManiaControl\Configurators;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Maps\Map;
use ManiaControl\Maps\MapManager;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\Exception;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInScriptModeException;

/**
 * Class offering a Configurator for Script Settings
 *
 * @author    steeffeen & kremsy
 * @copyright ManiaControl Copyright © 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ScriptSettings implements ConfiguratorMenu, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_SETTING                     = 'ScriptSetting';
	const ACTION_SETTING_BOOL                       = 'ScriptSetting.ActionBoolSetting.';
	const CB_SCRIPTSETTING_CHANGED                  = 'ScriptSettings.SettingChanged';
	const CB_SCRIPTSETTINGS_CHANGED                 = 'ScriptSettings.SettingsChanged';
	const TABLE_SCRIPT_SETTINGS                     = 'mc_scriptsettings';
	const SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN   = 'Load Stored Script-Settings on Map-Begin';
	const SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS = 'Change Script-Settings';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Script Settings Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'onInit');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'onBeginMap');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN, true);
		$this->initTables();

		//Permission for Change Script-Settings
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * Create all necessary Database Tables
	 *
	 * @return boolean
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SCRIPT_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`settingName` varchar(100) NOT NULL,
				`settingValue` varchar(500) NOT NULL,
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
		$statement->close();
		return true;
	}

	/**
	 * Handle OnInit callback
	 */
	public function onInit() {
		$this->loadSettingsFromDatabase();
	}

	/**
	 * Handle OnBegin Map Callback
	 *
	 * @param Map $map
	 */
	public function onBeginMap(Map $map) {
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_LOAD_DEFAULT_SETTINGS_MAP_BEGIN)) {
			$this->loadSettingsFromDatabase();
		}
	}

	/**
	 * Load Settings from Database
	 *
	 * @return bool
	 */
	public function loadSettingsFromDatabase() {
		try {
			$scriptSettings = $this->maniaControl->client->getModeScriptSettings();
		} catch(NotInScriptModeException $e) {
			return false;
		}

		$mysqli   = $this->maniaControl->database->mysqli;
		$serverId = $this->maniaControl->server->index;
		$query    = "SELECT * FROM `" . self::TABLE_SCRIPT_SETTINGS . "` WHERE serverIndex = " . $serverId . ";";
		$result   = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$loadedSettings = array();
		while($row = $result->fetch_object()) {
			if (!isset($scriptSettings[$row->settingName])) {
				continue;
			}
			$loadedSettings[$row->settingName] = $row->settingValue;
			settype($loadedSettings[$row->settingName], gettype($scriptSettings[$row->settingName]));
		}
		$result->close();
		if (!$loadedSettings) {
			return true;
		}

		try {
			$this->maniaControl->client->setModeScriptSettings($loadedSettings);
		} catch(Exception $e) {
			trigger_error('Error occured: ' . $e->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return 'Script Settings';
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script) {
		$pagesId = 'ScriptSettingsPages';
		$frame   = new Frame();

		try {
			$scriptInfo = $this->maniaControl->client->getModeScriptInfo();
		} catch(Exception $e) {
			if ($e->getMessage() == 'Not in script mode.') {
				$label = new Label();
				$frame->add($label);
				$label->setText($e->getMessage());
				return $frame;
			}
			throw $e;
		}

		$scriptParams = $scriptInfo->paramDescs;

		try {
			$scriptSettings = $this->maniaControl->client->getModeScriptSettings();
		} catch(NotInScriptModeException $e) {
			//do nothing
		}

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount  = 13;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2);
		$pagerPrev->setSize($pagerSize, $pagerSize);
		$pagerPrev->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2);
		$pagerNext->setSize($pagerSize, $pagerSize);
		$pagerNext->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_ArrowNext);

		$script->addPager($pagerPrev, -1, $pagesId);
		$script->addPager($pagerNext, 1, $pagesId);

		$pageCountLabel = new Label();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle('TextTitle1');
		$pageCountLabel->setTextSize(2);

		$script->addPageLabel($pageCountLabel, $pagesId);

		// Setting pages
		$pageFrames = array();
		$y          = 0.;
		foreach($scriptParams as $index => $scriptParam) {
			/** @var \Maniaplanet\DedicatedServer\Structures\ScriptSettings $scriptParam */
			$settingName = $scriptParam->name;

			if (!isset($scriptSettings[$settingName])) {
				continue;
			}

			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if (!empty($pageFrames)) {
					$pageFrame->setVisible(false);
				}
				array_push($pageFrames, $pageFrame);
				$y = $height * 0.41;
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);

			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.4, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($settingName);

			$settingValue = $scriptSettings[$settingName];

			$substyle = '';
			if ($settingValue === false) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
			} else if ($settingValue === true) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
			}

			if ($substyle != '') {
				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.545);
				$quad->setZ(-0.01);
				$quad->setSubStyle($substyle);
				$quad->setSize(4, 4);
				$quad->setHAlign(Control::CENTER);
				$quad->setAction(self::ACTION_SETTING_BOOL . $settingName);
			} else {
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setHAlign(Control::CENTER);
				$entry->setX($width / 2 * 0.55);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SETTING . '.' . $settingName);
				$entry->setDefault($settingValue);
			}

			$descriptionLabel = new Label();
			$pageFrame->add($descriptionLabel);
			$descriptionLabel->setHAlign(Control::LEFT);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
			$descriptionLabel->setSize($width * 0.7, $settingHeight);
			$descriptionLabel->setTextSize($labelTextSize);
			$descriptionLabel->setTranslate(true);
			$descriptionLabel->setText($scriptParam->desc);
			$script->addTooltip($nameLabel, $descriptionLabel);

			$y -= $settingHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}
		}

		return $frame;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SCRIPT_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$prefix = explode(".", $configData[3][0]['Name']);
		if ($prefix[0] != self::ACTION_PREFIX_SETTING) {
			return;
		}

		try {
			$scriptSettings = $this->maniaControl->client->getModeScriptSettings();
		} catch(NotInScriptModeException $e) {
			return;
		}

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$newSettings = array();
		foreach($configData[3] as $setting) {


			$settingName = substr($setting['Name'], $prefixLength + 1);
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

		$this->applyNewScriptSettings($newSettings, $player);

		//Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$boolSetting = (strpos($actionId, self::ACTION_SETTING_BOOL) === 0);
		if (!$boolSetting) {
			return;
		}

		$actionArray = explode(".", $actionId);
		$setting     = $actionArray[2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		// Toggle the Boolean Setting
		$this->toggleBooleanSetting($setting, $player);

		// Save all Changes
		$this->saveConfigData($callback[1], $player);
	}

	/**
	 * Toogle a Boolean Setting
	 *
	 * @param Player $player
	 * @param        $setting
	 */
	public function toggleBooleanSetting($setting, Player $player) {
		try {
			$scriptSettings = $this->maniaControl->client->getModeScriptSettings();
		} catch(NotInScriptModeException $e) {
			return;
		}

		if (!isset($scriptSettings[$setting])) {
			var_dump('no setting ' . $setting);
			return;
		}

		$newSettings           = array();
		$newSettings[$setting] = ($scriptSettings[$setting] ? false : true);

		$this->applyNewScriptSettings($newSettings, $player);
	}

	/**
	 * Apply the Array of new Script Settings
	 *
	 * @param array  $newSettings
	 * @param Player $player
	 * @param        bool
	 */
	private function applyNewScriptSettings(array $newSettings, Player $player) {
		if (!$newSettings) {
			return true;
		}

		try {
			$this->maniaControl->client->setModeScriptSettings($newSettings);
		} catch(Exception $e) {
			//TODO temp added 19.04.2014
			$this->maniaControl->errorHandler->triggerDebugNotice("Exception line 416 ScriptSettings.php" . $e->getMessage());
			$this->maniaControl->chat->sendError('Error occurred: ' . $e->getMessage(), $player->login);
			return false;
		}

		// Save Settings into Database
		$mysqli    = $this->maniaControl->database->mysqli;
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

		// Notifications
		$settingsCount = count($newSettings);
		$settingIndex  = 0;
		$title         = $this->maniaControl->authenticationManager->getAuthLevelName($player->authLevel);
		$chatMessage   = '$ff0' . $title . ' $<' . $player->nickname . '$> set ScriptSetting' . ($settingsCount > 1 ? 's' : '') . ' ';
		foreach($newSettings as $setting => $value) {
			$chatMessage .= '$<' . '$fff' . preg_replace('/^S_/', '', $setting) . '$z$s$ff0 ';
			$chatMessage .= 'to $fff' . $this->parseSettingValue($value) . '$>';

			if ($settingIndex <= $settingsCount - 2) {
				$chatMessage .= ', ';
			}

			// Add To Database
			$statement->bind_param('iss', $this->maniaControl->server->index, $setting, $value);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
			}

			// Trigger own callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTING_CHANGED, $setting, $value);

			$settingIndex++;
		}
		$statement->close();

		$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED);

		$chatMessage .= '!';
		$this->maniaControl->chat->sendInformation($chatMessage);
		$this->maniaControl->log($chatMessage, true);
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
		return (string)$value;
	}
}
