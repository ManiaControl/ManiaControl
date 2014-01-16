<?php

namespace ManiaControl\Configurators;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

/**
 * Class offering a Configurator for Script Settings
 *
 * @author steeffeen & kremsy
 */
class ScriptSettings implements ConfiguratorMenu, CallbackListener {
	/**
	 * Constants
	 */
	const ACTION_PREFIX_SETTING     = 'ScriptSetting';
	const ACTION_SETTING_BOOL       = 'ScriptSetting.ActionBoolSetting.';
	const CB_SCRIPTSETTING_CHANGED  = 'ScriptSettings.SettingChanged';
	const CB_SCRIPTSETTINGS_CHANGED = 'ScriptSettings.SettingsChanged';
	const TABLE_SCRIPT_SETTINGS     = 'mc_scriptsettings';

	/**
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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'onInit');
		$this->initTables();
	}

	/**
	 * Create all necessary Database Tables
	 *
	 * @return boolean
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SCRIPT_SETTINGS . "` (
				`serverIndex` int(11) NOT NULL AUTO_INCREMENT,
				`settingName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
				`settingValue` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
				UNIQUE KEY `setting` (`serverIndex`, `settingName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Script Settings' AUTO_INCREMENT=1;";

		$statement = $mysqli->prepare($query);
		if($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
			return false;
		}
		$statement->execute();
		if($statement->error) {
			trigger_error($statement->error, E_USER_ERROR);
			return false;
		}
		$statement->close();
		return true;
	}

	/**
	 * Handle OnInit callback
	 *
	 * @param array $callback
	 */
	public function onInit(array $callback) {
		$this->loadSettingsFromDatabase();
	}

	/**
	 * Load Settings from Database
	 *
	 * @return bool
	 */
	public function loadSettingsFromDatabase() {
		$scriptSettings = (array)$this->maniaControl->client->getModeScriptSettings();
		if(isset($scriptSettings['faultString'])) {
			if($scriptSettings['faultString'] == 'Not in script mode.') {
				return false;
			}
			trigger_error('Error occured: ' . $scriptSettings['faultString']);
			return false;
		}
		$mysqli   = $this->maniaControl->database->mysqli;
		$serverId = $this->maniaControl->server->index;
		$query    = "SELECT * FROM `" . self::TABLE_SCRIPT_SETTINGS . "` WHERE serverIndex = " . $serverId . ";";
		$result   = $mysqli->query($query);
		if($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$loadedSettings = array();
		while($row = $result->fetch_object()) {
			if(!isset($scriptSettings[$row->settingName])) {
				continue;
			}
			$loadedSettings[$row->settingName] = $row->settingValue;
			settype($loadedSettings[$row->settingName], gettype($scriptSettings[$row->settingName]));
		}
		$result->close();
		if(!$loadedSettings) {
			return true;
		}

		$success = $this->maniaControl->client->setModeScriptSettings($loadedSettings);
		if(!$success) {
			trigger_error('Error occured: ' . $this->maniaControl->getClientErrorText());
			return false;
		}
		return true;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return 'Script Settings';
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script) {
		$pagesId = 'ScriptSettingsPages';
		$frame   = new Frame();

		//$scriptInfo = (array)$this->maniaControl->client->getModeScriptInfo();
		$scriptInfo = $this->maniaControl->client->execute('GetModeScriptInfo');
		if(isset($scriptInfo['faultCode'])) {
			// Not in script mode
			$label = new Label();
			$frame->add($label);
			$label->setText($scriptInfo['faultString']);
			return $frame;
		}
		$scriptParams = $scriptInfo['ParamDescs'];

		$scriptSettings = $this->maniaControl->client->execute('GetModeScriptSettings');

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
			$settingName = $scriptParam['Name'];

			if(!isset($scriptSettings[$settingName])) {
				continue;
			}

			if(!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				if(!empty($pageFrames)) {
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
			if($settingValue === false) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
			} else if($settingValue === true) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
			}

			if($substyle != '') {
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
			$descriptionLabel->setText($scriptParam['Desc']);
			$script->addTooltip($nameLabel, $descriptionLabel);

			$y -= $settingHeight;
			if($index % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}
		}

		return $frame;
	}

	/**
	 *
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {

		$prefix = explode(".", $configData[3][0]['Name']);
		if($prefix[0] != self::ACTION_PREFIX_SETTING) {
			return;
		}

		$scriptSettings = $this->maniaControl->client->execute('GetModeScriptSettings');
		
		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$newSettings = array();
		foreach($configData[3] as $setting) {


			$settingName = substr($setting['Name'], $prefixLength + 1);
			if(!isset($scriptSettings[$settingName])) {
				var_dump('no setting ' . $settingName);
				continue;
			}

			if($setting['Value'] == $scriptSettings[$settingName]) {
				// Not changed
				continue;
			}

			$newSettings[$settingName] = $setting['Value'];
			settype($newSettings[$settingName], gettype($scriptSettings[$settingName]));
		}

		$this->applyNewScriptSettings($newSettings, $player);

		//Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($menuId);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$boolSetting = (strpos($actionId, self::ACTION_SETTING_BOOL) === 0);
		if(!$boolSetting) {
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
		$scriptSettings = $this->maniaControl->client->execute('GetModeScriptSettings');
		if(!isset($scriptSettings[$setting])) {
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
		if(!$newSettings) {
			return true;
		}
		$success = $this->maniaControl->client->setModeScriptSettings($newSettings);
		if(!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
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
		if($mysqli->error) {
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

			if($settingIndex <= $settingsCount - 2) {
				$chatMessage .= ', ';
			}

			// Add To Database
			$statement->bind_param('iss', $this->maniaControl->server->index, $setting, $value);
			$statement->execute();
			if($statement->error) {
				trigger_error($statement->error);
				$statement->close();
				return false;
			}

			// Trigger own callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTING_CHANGED, array(self::CB_SCRIPTSETTING_CHANGED, $setting, $value));

			$settingIndex++;
		}
		$statement->close();

		$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED, array(self::CB_SCRIPTSETTINGS_CHANGED));

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
		if(is_bool($value)) {
			return ($value ? 'True' : 'False');
		}
		return (string)$value;
	}
}
