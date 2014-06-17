<?php

namespace ManiaControl\Configurators;

use FML\Components\CheckBox;
use FML\Controls\Control;
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
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use Maniaplanet\DedicatedServer\Structures\ServerOptions;
use Maniaplanet\DedicatedServer\Xmlrpc\ServerOptionsException;

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
	const ACTION_PREFIX_SETTING                     = 'ServerSettings.';
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
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');

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
		$serverSettings = $this->maniaControl->client->getServerOptions();
		$applySettings  = false;
		while ($row = $result->fetch_object()) {
			$settingName = $row->settingName;
			if (!property_exists($serverSettings, $settingName)) {
				continue;
			}
			$oldType                      = gettype($serverSettings->$settingName);
			$serverSettings->$settingName = $row->settingValue;
			settype($serverSettings->$settingName, $oldType);
			$applySettings = true;
		}
		$result->free();
		if (!$applySettings) {
			return true;
		}

		try {
			$this->maniaControl->client->setServerOptions($serverSettings);
		} catch (ServerOptionsException $exception) {
			$this->maniaControl->chat->sendExceptionToAdmins($exception);
		}
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
		$posY      = 0.;
		$index     = 0;
		$pageFrame = null;

		foreach ($serverSettings as $name => $value) {
			// Continue on CurrentMaxPlayers...
			$pos = strpos($name, 'Current'); // TODO maybe display current somewhere
			if ($pos !== false) {
				continue;
			}

			if ($index % 13 === 0) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height * 0.41;
				$paging->addPage($pageFrame);
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($posY);

			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.4, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($name);
			$nameLabel->setTextColor("FFF");

			if (is_bool($value)) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setPosition($width * 0.23, 0, -0.01);
				$quad->setSize(4, 4);
				$checkBox = new CheckBox(self::ACTION_PREFIX_SETTING . $name, $value, $quad);
				$settingFrame->add($checkBox);
			} else {
				// Other
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setX($width * 0.23);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.48, $settingHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SETTING . $name);
				$entry->setDefault($value);

				if ($name === 'Comment') {
					$entry->setAutoNewLine(true);
					$entry->setSize($width * 0.48, $settingHeight * 3 + $settingHeight * 0.9);
					$settingFrame->setY($posY - $settingHeight * 1.5);
					// dummy:
					// TODO: "dummy:" what? remove?
					$posY -= $settingHeight * 3;
					$index += 3;
				}
			}

			$posY -= $settingHeight;
			$index++;
		}

		return $frame;
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


		$newSettings = new ServerOptions();
		foreach ($configData[3] as $setting) {
			$settingName                      = substr($setting['Name'], $prefixLength);
			$dynamicSettingName               = lcfirst($settingName);
			$newSettings->$dynamicSettingName = $setting['Value'];
			settype($newSettings->$dynamicSettingName, gettype($serverSettings[$settingName]));
		}

		$success = $this->applyNewServerSettings($newSettings, $player);
		if ($success) {
			$this->maniaControl->chat->sendSuccess('Server Settings saved!', $player);
		} else {
			$this->maniaControl->chat->sendSuccess('Server Settings Saving failed!', $player);
		}

		// Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
	}

	/**
	 * Apply the Array of new Server Settings
	 *
	 * @param ServerOptions $newSettings
	 * @param Player        $player
	 * @return bool
	 */
	private function applyNewServerSettings(ServerOptions $newSettings, Player $player) {
		if (!$newSettings) {
			return true;
		}

		try {
			$this->maniaControl->client->setServerOptions($newSettings);
		} catch (ServerOptionsException $e) {
			$this->maniaControl->chat->sendError($e->getMessage());
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

		$settingsArray = $newSettings->toArray();
		foreach ($settingsArray as $setting => $value) {
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
