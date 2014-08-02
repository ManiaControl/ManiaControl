<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Settings\Setting;
use ManiaControl\Settings\SettingManager;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Widget Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class WidgetPlugin implements CallbackListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const PLUGIN_ID      = 1;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'WidgetPlugin';
	const PLUGIN_AUTHOR  = 'kremsy';

	// MapWidget Properties
	const MLID_MAP_WIDGET              = 'WidgetPlugin.MapWidget';
	const SETTING_MAP_WIDGET_ACTIVATED = 'Map-Widget Activated';
	const SETTING_MAP_WIDGET_POSX      = 'Map-Widget-Position: X';
	const SETTING_MAP_WIDGET_POSY      = 'Map-Widget-Position: Y';
	const SETTING_MAP_WIDGET_WIDTH     = 'Map-Widget-Size: Width';
	const SETTING_MAP_WIDGET_HEIGHT    = 'Map-Widget-Size: Height';

	// ClockWidget Properties
	const MLID_CLOCK_WIDGET              = 'WidgetPlugin.ClockWidget';
	const SETTING_CLOCK_WIDGET_ACTIVATED = 'Clock-Widget Activated';
	const SETTING_CLOCK_WIDGET_POSX      = 'Clock-Widget-Position: X';
	const SETTING_CLOCK_WIDGET_POSY      = 'Clock-Widget-Position: Y';
	const SETTING_CLOCK_WIDGET_WIDTH     = 'Clock-Widget-Size: Width';
	const SETTING_CLOCK_WIDGET_HEIGHT    = 'Clock-Widget-Size: Height';

	// NextMapWidget Properties
	const MLID_NEXTMAP_WIDGET              = 'WidgetPlugin.NextMapWidget';
	const SETTING_NEXTMAP_WIDGET_ACTIVATED = 'Nextmap-Widget Activated';
	const SETTING_NEXTMAP_WIDGET_POSX      = 'Nextmap-Widget-Position: X';
	const SETTING_NEXTMAP_WIDGET_POSY      = 'Nextmap-Widget-Position: Y';
	const SETTING_NEXTMAP_WIDGET_WIDTH     = 'Nextmap-Widget-Size: Width';
	const SETTING_NEXTMAP_WIDGET_HEIGHT    = 'Nextmap-Widget-Size: Height';

	// ServerInfoWidget Properties
	const MLID_SERVERINFO_WIDGET              = 'WidgetPlugin.ServerInfoWidget';
	const SETTING_SERVERINFO_WIDGET_ACTIVATED = 'ServerInfo-Widget Activated';
	const SETTING_SERVERINFO_WIDGET_POSX      = 'ServerInfo-Widget-Position: X';
	const SETTING_SERVERINFO_WIDGET_POSY      = 'ServerInfo-Widget-Position: Y';
	const SETTING_SERVERINFO_WIDGET_WIDTH     = 'ServerInfo-Widget-Size: Width';
	const SETTING_SERVERINFO_WIDGET_HEIGHT    = 'ServerInfo-Widget-Size: Height';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
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
		return 'Plugin offers some Widgets';
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Set CustomUI Setting
		$this->maniaControl->getManialinkManager()->getCustomUIManager()->setChallengeInfoVisible(false);

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleOnBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleOnEndMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updateWidgets');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'updateWidgets');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'updateSettings');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_POSX, 160 - 20);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_POSY, 90 - 4.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_WIDTH, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_HEIGHT, 9.);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSX, -160 + 17.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSY, 90 - 4.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SERVERINFO_WIDGET_WIDTH, 35);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT, 9.);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSX, 160 - 20);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSY, 90 - 25.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEXTMAP_WIDGET_WIDTH, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT, 12.);

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_CLOCK_WIDGET_POSX, 160 - 5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_CLOCK_WIDGET_POSY, 90 - 11);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_CLOCK_WIDGET_WIDTH, 10);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_CLOCK_WIDGET_HEIGHT, 5.5);

		$this->displayWidgets();

		return true;
	}

	/**
	 * Display the Widgets
	 */
	private function displayWidgets() {
		// Display Map Widget
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->maniaControl->getClient()->triggerModeScriptEvent("Siege_SetProgressionLayerPosition", array("160.", "-67.", "0."));
			$this->displayMapWidget();
		}
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget();
		}
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}

	/**
	 * Display the Map Widget
	 *
	 * @param string $login
	 */
	public function displayMapWidget($login = null) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_MAP_WIDGET);
		$script    = new Script();
		$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$backgroundQuad->addMapInfoFeature();

		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($map->name));
		$label->setTextColor('fff');
		$label->setSize($width - 5, $height);

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, -1.4, 0.2);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($map->authorLogin);
		$label->setTextColor('fff');
		$label->setSize($width - 5, $height);

		if (isset($map->mx->pageurl)) {
			$quad = new Quad();
			$frame->add($quad);
			$quad->setImageFocus($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER));
			$quad->setImage($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON));
			$quad->setPosition(-$width / 2 + 4, -1.5, -0.5);
			$quad->setSize(4, 4);
			$quad->setUrl($map->mx->pageurl);
		}

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	/**
	 * Displays the Clock Widget
	 *
	 * @param bool $login
	 */
	public function displayClockWidget($login = false) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_WIDTH);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_CLOCK_WIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setVAlign($label::TOP);
		$label->setTextSize(1);
		$label->setTextColor('fff');
		$label->addClockFeature(false);

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	/**
	 * Display the Server Info Widget
	 *
	 * @param string $login
	 */
	public function displayServerInfoWidget($login = null) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_WIDTH);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_SERVERINFO_WIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$serverName = $this->maniaControl->getClient()->getServerName();

		$playerCount = $this->maniaControl->getPlayerManager()->getPlayerCount(true);
		$maxPlayers  = $this->maniaControl->getClient()->getMaxPlayers();

		$spectatorCount = $this->maniaControl->getPlayerManager()->getSpectatorCount();
		$maxSpectators  = $this->maniaControl->getClient()->getMaxSpectators();

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($serverName));
		$label->setTextColor('fff');
		//$label->setAutoNewLine(true);

		// Player Quad / Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 9, -1.5, 0.2);
		$label->setHAlign($label::LEFT);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($playerCount . " / " . $maxPlayers['NextValue']);
		$label->setTextColor('fff');

		$quad = new Quad_Icons128x128_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Multiplayer);
		$quad->setPosition(-$width / 2 + 7, -1.6, 0.2);
		$quad->setSize(2.5, 2.5);

		// Spectator Quad / Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(2, -1.5, 0.2);
		$label->setHAlign($label::LEFT);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($spectatorCount . " / " . $maxSpectators['NextValue']);
		$label->setTextColor('fff');

		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Camera);
		$quad->setPosition(0, -1.6, 0.2);
		$quad->setSize(3.3, 2.5);

		// Favorite quad
		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_StateFavourite);
		$quad->setPosition($width / 2 - 4, -1.5, -0.5);
		$quad->setSize(3, 3);
		$quad->setManialink('maniacontrol?favorite=' . urlencode($this->maniaControl->getServer()->login));

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
		//Restore Siege Progression Layer
		$this->maniaControl->getClient()->triggerModeScriptEvent('Siege_SetProgressionLayerPosition', array("160.", "90.", "0."));

		$this->closeWidget(self::MLID_CLOCK_WIDGET);
		$this->closeWidget(self::MLID_SERVERINFO_WIDGET);
		$this->closeWidget(self::MLID_MAP_WIDGET);
		$this->closeWidget(self::MLID_NEXTMAP_WIDGET);
	}

	/**
	 * Close a Widget
	 *
	 * @param string $widgetId
	 */
	public function closeWidget($widgetId) {
		$this->maniaControl->getManialinkManager()->hideManialink($widgetId);
	}

	/**
	 * Handle Begin Map Callback
	 */
	public function handleOnBeginMap() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget();
		}
		$this->closeWidget(self::MLID_NEXTMAP_WIDGET);
	}

	/**
	 * Handle End Map Callback
	 */
	public function handleOnEndMap() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED)) {
			$this->displayNextMapWidget();
		}
	}

	/**
	 * Display the Next Map (Only at the end of the Map)
	 *
	 * @param string $login
	 */
	public function displayNextMapWidget($login = null) {
		$posX         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NEXTMAP_WIDGET_POSX);
		$posY         = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NEXTMAP_WIDGET_POSY);
		$width        = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NEXTMAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_NEXTMAP_WIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Check if the Next Map is a queued Map
		$queuedMap = $this->maniaControl->getMapManager()->getMapQueue()->getNextMap();

		/**
		 * @var Player $requester
		 */
		$requester = null;
		// if the nextmap is not a queued map, get it from map info
		if (!$queuedMap) {
			$map    = $this->maniaControl->getClient()->getNextMapInfo();
			$name   = Formatter::stripDirtyCodes($map->name);
			$author = $map->author;
		} else {
			$requester = $queuedMap[0];
			$map       = $queuedMap[1];
			$name      = $map->name;
			$author    = $map->authorLogin;
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, $height / 2 - 2.3, 0.2);
		$label->setTextSize(1);
		$label->setText('Next Map');
		$label->setTextColor('fff');
		$label->setStyle($labelStyle);

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, $height / 2 - 5.5, 0.2);
		$label->setTextSize(1.3);
		$label->setText($name);
		$label->setTextColor('fff');

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, -$height / 2 + 4);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($author);
		$label->setTextColor('fff');

		if ($requester) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setPosition(0, -$height / 2 + 2, 0.2);
			$label->setTextSize(1);
			$label->setScale(0.7);
			$label->setText($author);
			$label->setTextColor('f80');
			$label->setText('Requested by ' . $requester->getEscapedNickname());
		}

		// Send manialink
		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $login);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Display Map Widget
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget($player->login);
		}
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget($player->login);
		}
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}

	/**
	 * Update Widgets on Setting Changes
	 *
	 * @param Setting $setting
	 */
	public function updateSettings(Setting $setting){
		if($setting->belongsToClass($this)){
			$this->displayWIdgets();
		}
	}

	/**
	 * Update Widget on certain callbacks
	 */
	public function updateWidgets() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}
}
