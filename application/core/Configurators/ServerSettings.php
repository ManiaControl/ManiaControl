<?php

namespace ManiaControl\Configurators;

use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Xmlrpc\LadderModeUnknownException;

/**
 * Class offering a Configurator for Server Settings
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ServerSettings implements ConfiguratorMenu, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_SETTING                     = 'ServerSettings';
	const ACTION_SETTING_BOOL                       = 'ServerSettings.ActionBoolSetting.';
	const CB_SERVERSETTING_CHANGED                  = 'ServerSettings.SettingChanged';
	const CB_SERVERSETTINGS_CHANGED                 = 'ServerSettings.SettingsChanged';
	const TABLE_SERVER_SETTINGS                     = 'mc_serversettings';
	const SETTING_PERMISSION_CHANGE_SERVER_SETTINGS = 'Change Server-Settings';

	/*
	 * Private Properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new Server Settings Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_ONINIT, $this, 'onInit');

		//Permission for Change Script-Settings
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SERVER_SETTINGS, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
	}

	/**
	 * Initialize necessary database tables
	 *
	 * @return bool
	 */
	private function initTables() {
		$mysqli    = $this->maniaControl->database->mysqli;
		$query     = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SERVER_SETTINGS . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`settingName` varchar(100) NOT NULL,
				`settingValue` varchar(500) NOT NULL,
				PRIMARY KEY (`index`),
				UNIQUE KEY `setting` (`serverIndex`, `settingName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Server Settings' AUTO_INCREMENT=1;";
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
	 * Load Settings from Database
	 *
	 * @return bool
	 */
	public function loadSettingsFromDatabase() {
		$serverId = $this->maniaControl->server->index;
		$mysqli   = $this->maniaControl->database->mysqli;
		$query    = "SELECT * FROM `" . self::TABLE_SERVER_SETTINGS . "` WHERE serverIndex = " . $serverId . ";";
		$result   = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$serverSettings = $this->maniaControl->client->getServerOptions()->toArray();
		$applySettings  = false;
		while ($row = $result->fetch_object()) {
			if (!isset($serverSettings[$row->settingName])) {
				continue;
			}
			$oldType                           = gettype($serverSettings[$row->settingName]);
			$serverSettings[$row->settingName] = $row->settingValue;
			settype($serverSettings[$row->settingName], $oldType);
			$applySettings = true;
		}
		$result->close();
		if (!$applySettings) {
			return true;
		}

		$this->maniaControl->client->setServerOptions($serverSettings);
		return true;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		$serverSettings = $this->maniaControl->client->getServerOptions()->toArray();

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

		$paging->addButton($pagerNext);
		$paging->addButton($pagerPrev);

		$pageCountLabel = new Label();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle('TextTitle1');
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		// Setting pages
		$pageFrames = array();
		$y          = 0.;
		$id         = 0;
		foreach ($serverSettings as $name => $value) {
			// Continue on CurrentMaxPlayers...
			$pos = strpos($name, "Current"); // TODO maybe display current somewhere
			if ($pos !== false) {
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
				$paging->addPage($pageFrame);
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
			$nameLabel->setText($name);
			$nameLabel->setTextColor("FFF");

			$substyle = '';
			if ($value === false) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
			} else if ($value === true) {
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
			}

			$entry = new Entry();
			$settingFrame->add($entry);
			$entry->setStyle(Label_Text::STYLE_TextValueSmall);
			$entry->setHAlign(Control::CENTER);
			$entry->setX($width / 2 * 0.46);
			$entry->setTextSize(1);
			$entry->setSize($width * 0.48, $settingHeight * 0.9);
			$entry->setName(self::ACTION_PREFIX_SETTING . '.' . $name);
			$entry->setDefault($value);

			if ($name == "Comment") { //
				$entry->setAutoNewLine(true);
				$entry->setSize($width * 0.48, $settingHeight * 3 + $settingHeight * 0.9);
				$settingFrame->setY($y - $settingHeight * 1.5);
				// dummy:
				$y -= $settingHeight * 3;
				$id += 3;
			}

			if ($substyle != '') {
				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.46);
				$quad->setZ(-0.01);
				$quad->setSubStyle($substyle);
				$quad->setSize(4, 4);
				$quad->setHAlign(Control::CENTER);
				$quad->setAction(self::ACTION_SETTING_BOOL . $name);

				$entry->setVisible(false);
			}

			$y -= $settingHeight;
			if ($id % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}

			$id++;
		}

		return $frame;
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

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		// Save all Changes
		$this->saveConfigData($callback[1], $player);
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVER_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			return;
		}

		$serverSettings = $this->maniaControl->client->getServerOptions()->toArray();

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$actionArray = explode(".", $configData[2]);

		$boolSettingName = '';
		if (isset($actionArray[2])) {
			$boolSettingName = self::ACTION_PREFIX_SETTING . '.' . $actionArray[2];
		}

		$newSettings = array();
		foreach ($configData[3] as $setting) {
			// Check if it was a boolean button
			if ($setting['Name'] == $boolSettingName) {
				$setting['Value'] = ($setting['Value'] ? false : true);
			}

			$settingName = substr($setting['Name'], $prefixLength + 1);

			$newSettings[$settingName] = $setting['Value'];
			settype($newSettings[$settingName], gettype($serverSettings[$settingName]));
		}

		$this->applyNewServerSettings($newSettings, $player);

		//Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * Apply the Array of new Server Settings
	 *
	 * @param array  $newSettings
	 * @param Player $player
	 * @return bool
	 */
	private function applyNewServerSettings(array $newSettings, Player $player) {
		$this->maniaControl->client->setServerName('$z$w$ADFP$9CFa$7BFr$7BFa$5AFg$2AFo$09Fn$fffElite #1 $s$i$aaaOfficial Maps 900k');
		sleep(1);
		var_dump($this->maniaControl->client->getServerName());
		if (!$newSettings) {
			return true;
		}

		try {
			$this->maniaControl->client->setServerOptions($newSettings);
		} catch (LadderModeUnknownException $e) {
			$this->maniaControl->chat->sendError("Unknown Ladder-Mode");
			return false;
		}

		// Save Settings into Database
		$mysqli = $this->maniaControl->database->mysqli;

		$query     = "INSERT INTO `" . self::TABLE_SERVER_SETTINGS . "` (
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

		foreach ($newSettings as $setting => $value) {
			if ($value === null) {
				continue;
			}

			$statement->bind_param('iss', $this->maniaControl->server->index, $setting, $value);
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				$statement->close();
				return false;
			}

			// Trigger own callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_SERVERSETTING_CHANGED, array(self::CB_SERVERSETTING_CHANGED, $setting, $value));
		}

		$statement->close();

		$this->maniaControl->callbackManager->triggerCallback(self::CB_SERVERSETTINGS_CHANGED, array(self::CB_SERVERSETTINGS_CHANGED));

		return true;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public function getTitle() {
		return 'Server Settings';
	}
}
