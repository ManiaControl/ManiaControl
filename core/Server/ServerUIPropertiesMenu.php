<?php

namespace ManiaControl\Server;

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
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Callbacks\Structures\Common\UIPropertiesBaseStructure;

use ManiaControl\Configurator\ConfiguratorMenu;
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Utils\DataUtil;
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;
use Maniaplanet\DedicatedServer\Xmlrpc\GameModeException;

/**
 * Class offering a Configurator for the Server UI Properties
 *
 * @author    axelalex2
 * @copyright 
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ServerUIPropertiesMenu implements ConfiguratorMenu, CallbackListener, TimerListener {
	/*
	 * Constants
	 */
	const ACTION_PREFIX_SERVER_UI_PROPERTIES                  = 'ServerUIProperties.';
	const CB_SERVERUIPROPERTY_CHANGED                         = 'ServerUIProperties.PropertyChanged';
	const CB_SERVERUIPROPERTIES_CHANGED                       = 'ServerUIProperties.PropertiesChanged';
	const TABLE_SERVER_UI_PROPERTIES                          = 'mc_serveruiproperties';
	const SETTING_LOAD_DEFAULT_SERVER_UI_PROPERTIES_MAP_BEGIN = 'Load Stored ServerUIProperties on Map-Begin';
	const SETTING_PERMISSION_CHANGE_SERVER_UI_PROPERTIES      = 'Change ServerUIProperties';

	const CONFIGURATOR_MENU_DELIMITER                         = '.';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl     = null;
	/** @var string $gameShort */
	private $gameShort        = '';
	/** @var array $liveUIProperties */
	private $liveUIProperties = array();

	/**
	 * Construct a new ServerUIPropertiesMenu instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		$this->initTables();

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'onInit');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'onBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::SM_UIPROPERTIES, $this, 'onUIProperties');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::TM_UIPROPERTIES, $this, 'onUIProperties');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_LOAD_DEFAULT_SERVER_UI_PROPERTIES_MAP_BEGIN, false);

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_SERVER_UI_PROPERTIES, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * Create all necessary database tables
	 *
	 * @return boolean
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_SERVER_UI_PROPERTIES . "` (
				`index` int(11) NOT NULL AUTO_INCREMENT,
				`serverIndex` int(11) NOT NULL,
				`uiPropertyName` varchar(100) NOT NULL DEFAULT '',
				`uiPropertyValue` varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY (`index`),
				UNIQUE KEY `uiProperty` (`serverIndex`, `uiPropertyName`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Server UI Properties' AUTO_INCREMENT=1;";

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
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return 'Server UI Properties';
	}

	/**
	 * Handle OnInit callback
	 */
	public function onInit() {
		$this->gameShort = $this->maniaControl->getMapManager()->getCurrentMap()->getGame();
		$this->loadServerUIPropertiesFromDatabase();
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function onBeginMap() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_LOAD_DEFAULT_SERVER_UI_PROPERTIES_MAP_BEGIN)) {
			$this->loadServerUIPropertiesFromDatabase();
		}
	}

	/**
	 * Handle UI Properties Callback
	 *
	 * @param UIPropertiesBaseStructure
	 */
	public function onUIProperties(UIPropertiesBaseStructure $structure) {
		$liveUIProperties = DataUtil::flattenArray($structure->getUiPropertiesArray(), self::CONFIGURATOR_MENU_DELIMITER);
		ksort($liveUIProperties);
		$this->liveUIProperties = $liveUIProperties;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		try {
			$this->loadLiveServerUIProperties();
		} catch (GameModeException $e) {
			return;
		}

		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		// Config
		$pagerSize        = 9.;
		$uiPropertyHeight = 5.;
		$labelTextSize    = 2;

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
		$maxCount  = (int) floor(($height - 2*$pagerSize) / $uiPropertyHeight);

		foreach ($this->liveUIProperties as $uiPropertyName => $uiPropertyValue) {
			if (!isset($this->liveUIProperties[$uiPropertyName])) {
				continue;
			}

			if ($index % $maxCount === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height * 0.41;
				$paging->addPageControl($pageFrame);
			}

			$uiPropertyFrame = new Frame();
			$pageFrame->addChild($uiPropertyFrame);
			$uiPropertyFrame->setY($posY);

			$nameLabel = new Label_Text();
			$uiPropertyFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.4, $uiPropertyHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($uiPropertyName);

			if (is_bool($uiPropertyValue)) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setX($width / 2 * 0.545);
				$quad->setSize(4, 4);
				$checkBox = new CheckBox(self::ACTION_PREFIX_SERVER_UI_PROPERTIES . $uiPropertyName, $uiPropertyValue, $quad);
				$uiPropertyFrame->addChild($checkBox);
			} else {
				// Value entry
				$entry = new Entry();
				$uiPropertyFrame->addChild($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setX($width / 2 * 0.55);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.3, $uiPropertyHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SERVER_UI_PROPERTIES . $uiPropertyName);
				$entry->setDefault($uiPropertyValue);
			}

			$posY -= $uiPropertyHeight;
			$index++;
		}

		return $frame;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_SERVER_UI_PROPERTIES)) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SERVER_UI_PROPERTIES) !== 0) {
			return;
		}

		$prefixLength = strlen(self::ACTION_PREFIX_SERVER_UI_PROPERTIES);

		$newUIProperties = array();
		foreach ($configData[3] as $uiProperty) {
			$uiPropertyName = substr($uiProperty['Name'], $prefixLength);
			if (!isset($this->liveUIProperties[$uiPropertyName])) {
				continue;
			}

			if ($uiProperty['Value'] == $this->liveUIProperties[$uiPropertyName]) {
				// Not changed
				continue;
			}

			$newUIProperties[$uiPropertyName] = $uiProperty['Value'];
			settype($newUIProperties[$uiPropertyName], gettype($this->liveUIProperties[$uiPropertyName]));
		}

		$success = $this->applyNewServerUIProperties($newUIProperties, $player);
		if ($success) {
			$this->maniaControl->getChat()->sendSuccess('Server UI Properties saved!', $player);
		} else {
			$this->maniaControl->getChat()->sendError('Server UI Properties Saving failed!', $player);
		}

		// Reopen the Menu (delayed, so Configurator doesn't show nothing)
		$this->maniaControl->getTimerManager()->registerOneTimeListening(
			$this,
			function () use ($player) {
				$this->maniaControl->getConfigurator()->showMenu($player, $this);
			},
			100
		);
	}

	/**
	 * Load Settings from Database
	 *
	 * @return bool
	 */
	public function loadServerUIPropertiesFromDatabase() {
		try {
			$this->loadLiveServerUIProperties();
		} catch (GameModeException $e) {
			return false;
		}

		$mysqli      = $this->maniaControl->getDatabase()->getMysqli();
		$serverIndex = $this->maniaControl->getServer()->index;
		$query       = "SELECT * FROM `" . self::TABLE_SERVER_UI_PROPERTIES . "`
				WHERE serverIndex = {$serverIndex};";
		$result      = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}

		$loadedUIProperties = array();
		while ($row = $result->fetch_object()) {
			if (!isset($this->liveUIProperties[$row->uiPropertyName])) {
				continue;
			}
			$loadedUIProperties[$row->uiPropertyName] = $row->uiPropertyValue;
			settype($loadedUIProperties[$row->uiPropertyName], gettype($this->liveUIProperties[$row->uiPropertyName]));
		}
		$result->free();
		if (empty($loadedUIProperties)) {
			return true;
		}

		return $this->setServerUIProperties($loadedUIProperties);
	}

	/**
	 * Triggers a callback to receive the current UI Properties of the server.
	 */
	private function loadLiveServerUIProperties() {
		switch ($this->gameShort) {
			case 'sm':
				$this->maniaControl->getModeScriptEventManager()->getShootmaniaUIProperties();
				break;
			case 'tm':
				$this->maniaControl->getModeScriptEventManager()->getTrackmaniaUIProperties();
				break;
		}
	}

	/**
	 * Sets the given UI Properties by XML to the server.
	 *
	 * @param array $uiProperties
	 */
	private function setServerUIProperties(array $uiProperties) {
		$xmlProperties = $this->buildXmlUIProperties($uiProperties);
		switch ($this->gameShort) {
			case 'sm':
				$this->maniaControl->getModeScriptEventManager()->setShootmaniaUIProperties($xmlProperties);
				break;
			case 'tm':
				$this->maniaControl->getModeScriptEventManager()->setTrackmaniaUIProperties($xmlProperties);
				break;
		}
	}

	/**
	 * Builds the given UI Properties into a XML string representation.
	 *
	 * @param array $uiProperties
	 * @return string
	 */
	private function buildXmlUIProperties(array $uiProperties) {
		$this->includePositions($uiProperties);
		$uiProperties = DataUtil::unflattenArray($uiProperties, self::CONFIGURATOR_MENU_DELIMITER);
		$uiProperties = DataUtil::implodePositions($uiProperties);
		return DataUtil::buildXmlStandaloneFromArray($uiProperties, 'ui_properties');
	}

	/**
	 * Includes possibly missing position properties in given UI Properties.
	 *
	 * @param array &$uiProperties
	 */
	private function includePositions(array &$uiProperties) {
		$uiPropertiesToAdd = array();
		$positions = array('x', 'y', 'z');
		foreach ($uiProperties as $key => $value) {
			$keySplits = explode(self::CONFIGURATOR_MENU_DELIMITER, $key);
			$numKeySplits = count($keySplits);
			$keySplit = $keySplits[$numKeySplits-1];
			if (in_array($keySplit, $positions)) {
				foreach ($positions as $position) {
					$keySplits[$numKeySplits-1] = $position;
					$keyToAdd = implode(self::CONFIGURATOR_MENU_DELIMITER, $keySplits);
					if (array_key_exists($keyToAdd, $this->liveUIProperties)) {
						$uiPropertiesToAdd[$keyToAdd] = $this->liveUIProperties[$keyToAdd];
					}
				}
			}
		}
		$uiProperties = $uiProperties + $uiPropertiesToAdd;
	}

	/**
	 * Apply the Array of new Server UI Properties
	 *
	 * @param array  $newUIProperties
	 * @param Player $player
	 * @return bool
	 */
	private function applyNewServerUIProperties(array $newUIProperties, Player $player) {
		if (empty($newUIProperties)) {
			return true;
		}

		try {
			$this->setServerUIProperties($newUIProperties);
		} catch (FaultException $e) {
			return false;
		}


		// Save Settings into Database
		$mysqli = $this->maniaControl->getDatabase()->getMysqli();
		$query  = "INSERT INTO `" . self::TABLE_SERVER_UI_PROPERTIES . "` (
				`serverIndex`,
				`uiPropertyName`,
				`uiPropertyValue`
				) VALUES (
				?, ?, ?
				) ON DUPLICATE KEY UPDATE
				`uiPropertyValue` = VALUES(`uiPropertyValue`);";
		$statement = $mysqli->prepare($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return false;
		}
		$uiPropertyDbName  = null;
		$uiPropertyDbValue = null;
		$statement->bind_param('iss', $this->maniaControl->getServer()->index, $uiPropertyDbName, $uiPropertyDbValue);

		// Notifications
		$uiPropertiesCount = count($newUIProperties);
		$uiPropertyIndex   = 0;
		$title             = $player->getAuthLevelName();
		$chatMessage       = '$ff0' . $title . ' ' . $player->getEscapedNickname() . ' set ServerUIPropert' . ($uiPropertiesCount > 1 ? 'ies' : 'y') . ' ';
		foreach ($newUIProperties as $uiPropertyName => $uiPropertyValue) {
			$chatMessage .= '$<$fff' . $uiPropertyName . '$>$ff0 ';
			$chatMessage .= 'to $<$fff' . $this->parseServerUIPropertyValue($uiPropertyValue) . '$>';

			if ($uiPropertyIndex < $uiPropertiesCount-1) {
				$chatMessage .= ', ';
			}

			// Add To Database
			$uiPropertyDbName = $uiPropertyName;
			$uiPropertyDbValue = $uiPropertyValue;
			$statement->execute();
			if ($statement->error) {
				trigger_error($statement->error);
			}

			// Trigger own callback
			$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SERVERUIPROPERTY_CHANGED, $uiPropertyName, $uiPropertyValue);

			$uiPropertyIndex++;
		}
		$statement->close();

		$this->maniaControl->getCallbackManager()->triggerCallback(self::CB_SERVERUIPROPERTIES_CHANGED);

		$chatMessage .= '!';
		$this->maniaControl->getChat()->sendInformation($chatMessage);
		Logger::logInfo($chatMessage, true);
		return true;
	}

	/**
	 * Parse the Server UI Property to a String Representation
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function parseServerUIPropertyValue($value) {
		if (is_bool($value)) {
			return ($value ? 'True' : 'False');
		}
		return (string) $value;
	}
}
