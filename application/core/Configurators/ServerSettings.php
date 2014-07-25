<?php

namespace ManiaControl\Configurators;

use FML\Components\CheckBox;
use FML\Controls\Entry;
use FML\Controls\Frame;
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
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return 'Server Settings';
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
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT * FROM `" . self::TABLE_SERVER_SETTINGS . "`
				WHERE serverIndex = {$this->maniaControl->server->index};";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$oldServerOptions = $this->maniaControl->client->getServerOptions();
		$newServerOptions = new ServerOptions();

		while ($row = $result->fetch_object()) {
			$settingName = lcfirst($row->settingName);
			if (!property_exists($oldServerOptions, $settingName)) {
				continue;
			}
			$newServerOptions->$settingName = $row->settingValue;
			settype($newServerOptions->$settingName, gettype($oldServerOptions->$settingName));
		}
		$result->free();

		$this->fillUpMandatoryOptions($newServerOptions, $oldServerOptions);

		$loaded = false;
		try {
			$loaded = $this->maniaControl->client->setServerOptions($newServerOptions);
		} catch (ServerOptionsException $exception) {
			$this->maniaControl->chat->sendExceptionToAdmins($exception);
		}
		$message = ($loaded ? 'Server Settings successfully loaded!' : 'Error loading Server Settings!');
		$this->maniaControl->chat->sendSuccessToAdmins($message);
		return $loaded;
	}

	/**
	 * Fill up the new server options object with the necessary settings based on the old options object
	 *
	 * @param ServerOptions $newServerOptions
	 * @param ServerOptions $oldServerOptions
	 * @return ServerOptions
	 */
	private function fillUpMandatoryOptions(ServerOptions &$newServerOptions, ServerOptions $oldServerOptions) {
		$mandatorySettings = array('name', 'comment', 'password', 'passwordForSpectator', 'nextCallVoteTimeOut', 'callVoteRatio');
		foreach ($mandatorySettings as $settingName) {
			if (!isset($newServerOptions->$settingName) && isset($oldServerOptions->$settingName)) {
				$newServerOptions->$settingName = $oldServerOptions->$settingName;
			}
		}
		return $newServerOptions;
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		$serverOptions  = $this->maniaControl->client->getServerOptions();
		$serverSettings = $serverOptions->toArray();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;

		// Pagers
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->add($pagerPrev);
		$pagerPrev->setPosition($width * 0.39, $height * -0.44, 2)
		          ->setSize($pagerSize, $pagerSize)
		          ->setSubStyle($pagerPrev::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->add($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2)
		          ->setSize($pagerSize, $pagerSize)
		          ->setSubStyle($pagerNext::SUBSTYLE_ArrowNext);

		$pageCountLabel = new Label_Text();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign($pageCountLabel::RIGHT)
		               ->setPosition($width * 0.35, $height * -0.44, 1)
		               ->setStyle($pageCountLabel::STYLE_TextTitle1)
		               ->setTextSize(2);

		$paging->addButton($pagerNext)
		       ->addButton($pagerPrev)
		       ->setLabel($pageCountLabel);

		// Setting pages
		$posY      = 0.;
		$index     = 0;
		$pageFrame = null;

		foreach ($serverSettings as $name => $value) {
			// Continue on CurrentMaxPlayers...
			$pos = strpos($name, 'Current'); // TODO: display 'Current...' somewhere
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
			$nameLabel->setHAlign($nameLabel::LEFT)
			          ->setX($width * -0.46)
			          ->setSize($width * 0.4, $settingHeight)
			          ->setStyle($nameLabel::STYLE_TextCardSmall)
			          ->setTextSize($labelTextSize)
			          ->setText($name)
			          ->setTextColor('fff');

			if (is_bool($value)) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setPosition($width * 0.23, 0, -0.01)
				     ->setSize(4, 4);
				$checkBox = new CheckBox(self::ACTION_PREFIX_SETTING . $name, $value, $quad);
				$settingFrame->add($checkBox);
			} else {
				// Other
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall)
				      ->setX($width * 0.23)
				      ->setTextSize(1)
				      ->setSize($width * 0.48, $settingHeight * 0.9)
				      ->setName(self::ACTION_PREFIX_SETTING . $name)
				      ->setDefault($value);

				if ($name === 'Comment') {
					$entry->setSize($width * 0.48, $settingHeight * 3. + $settingHeight * 0.9)
					      ->setAutoNewLine(true);
					$settingFrame->setY($posY - $settingHeight * 1.5);
					$posY -= $settingHeight * 3.;
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

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		$oldServerOptions = $this->maniaControl->client->getServerOptions();
		$newServerOptions = new ServerOptions();

		foreach ($configData[3] as $setting) {
			$settingName                    = lcfirst(substr($setting['Name'], $prefixLength));
			$newServerOptions->$settingName = $setting['Value'];
			settype($newServerOptions->$settingName, gettype($oldServerOptions->$settingName));
		}

		$this->fillUpMandatoryOptions($newServerOptions, $oldServerOptions);

		$success = $this->applyNewServerOptions($newServerOptions, $player);
		if ($success) {
			$this->maniaControl->chat->sendSuccess('Server Settings saved!', $player);
		} else {
			$this->maniaControl->chat->sendSuccess('Server Settings saving failed!', $player);
		}

		// Reopen the Menu
		$this->maniaControl->configurator->showMenu($player, $this);
	}

	/**
	 * Apply the Array of new Server Settings
	 *
	 * @param ServerOptions $newServerOptions
	 * @param Player        $player
	 * @return bool
	 */
	private function applyNewServerOptions(ServerOptions $newServerOptions, Player $player) {
		try {
			$this->maniaControl->client->setServerOptions($newServerOptions);
		} catch (ServerOptionsException $exception) {
			$this->maniaControl->chat->sendException($exception, $player);
			return false;
		}

		$this->saveServerOptions($newServerOptions, true);

		$this->maniaControl->callbackManager->triggerCallback(self::CB_SERVERSETTINGS_CHANGED, array(self::CB_SERVERSETTINGS_CHANGED));

		return true;
	}

	/**
	 * Save the given server options in the database
	 *
	 * @param ServerOptions $serverOptions
	 * @param bool          $triggerCallbacks
	 * @return bool
	 */
	private function saveServerOptions(ServerOptions $serverOptions, $triggerCallbacks = false) {
		$mysqli    = $this->maniaControl->database->mysqli;
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

		$settingName  = null;
		$settingValue = null;
		$statement->bind_param('iss', $this->maniaControl->server->index, $settingName, $settingValue);

		$settingsArray = $serverOptions->toArray();
		foreach ($settingsArray as $settingName => $settingValue) {
			if ($settingValue === null) {
				continue;
			}

			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
				$statement->close();
				return false;
			}

			if ($triggerCallbacks) {
				$this->maniaControl->callbackManager->triggerCallback(self::CB_SERVERSETTING_CHANGED, array(self::CB_SERVERSETTING_CHANGED, $settingName, $settingValue));
			}
		}

		$statement->close();
		return true;
	}
}
