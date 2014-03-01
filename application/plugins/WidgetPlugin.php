<?php
use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;
use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\TimerListener;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Maps\MapManager;
use ManiaControl\Maps\Map;

/**
 * ManiaControl Widget Plugin
 *
 * @author kremsy
 */
class WidgetPlugin implements CallbackListener, TimerListener, Plugin {

	/**
	 * Constants
	 */
	const PLUGIN_ID      = 1;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME    = 'WidgetPlugin';
	const PLUGIN_AUTHOR  = 'kremsy';

	// MapWidget Properties
	const MLID_MAPWIDGET               = 'WidgetPlugin.MapWidget';
	const SETTING_MAP_WIDGET_ACTIVATED = 'Map-Widget Activated';
	const SETTING_MAP_WIDGET_POSX      = 'Map-Widget-Position: X';
	const SETTING_MAP_WIDGET_POSY      = 'Map-Widget-Position: Y';
	const SETTING_MAP_WIDGET_WIDTH     = 'Map-Widget-Size: Width';
	const SETTING_MAP_WIDGET_HEIGHT    = 'Map-Widget-Size: Height';

	// ClockWidget Properties
	const MLID_CLOCKWIDGET               = 'WidgetPlugin.ClockWidget';
	const SETTING_CLOCK_WIDGET_ACTIVATED = 'Clock-Widget Activated';
	const SETTING_CLOCK_WIDGET_POSX      = 'Clock-Widget-Position: X';
	const SETTING_CLOCK_WIDGET_POSY      = 'Clock-Widget-Position: Y';
	const SETTING_CLOCK_WIDGET_WIDTH     = 'Clock-Widget-Size: Width';
	const SETTING_CLOCK_WIDGET_HEIGHT    = 'Clock-Widget-Size: Height';

	// NextMapWidget Properties
	const MLID_NEXTMAPWIDGET               = 'WidgetPlugin.NextMapWidget';
	const SETTING_NEXTMAP_WIDGET_ACTIVATED = 'Nextmap-Widget Activated';
	const SETTING_NEXTMAP_WIDGET_POSX      = 'Nextmap-Widget-Position: X';
	const SETTING_NEXTMAP_WIDGET_POSY      = 'Nextmap-Widget-Position: Y';
	const SETTING_NEXTMAP_WIDGET_WIDTH     = 'Nextmap-Widget-Size: Width';
	const SETTING_NEXTMAP_WIDGET_HEIGHT    = 'Nextmap-Widget-Size: Height';

	// ServerInfoWidget Properties
	const MLID_SERVERINFOWIDGET               = 'WidgetPlugin.ServerInfoWidget';
	const SETTING_SERVERINFO_WIDGET_ACTIVATED = 'ServerInfo-Widget Activated';
	const SETTING_SERVERINFO_WIDGET_POSX      = 'ServerInfo-Widget-Position: X';
	const SETTING_SERVERINFO_WIDGET_POSY      = 'ServerInfo-Widget-Position: Y';
	const SETTING_SERVERINFO_WIDGET_WIDTH     = 'ServerInfo-Widget-Size: Width';
	const SETTING_SERVERINFO_WIDGET_HEIGHT    = 'ServerInfo-Widget-Size: Height';

	/**
	 * Private Properties
	 */
	/**
	 * @var maniaControl $maniaControl
	 */
	private $maniaControl = null;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//do nothing
	}

	/**
	 * Load the plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Set CustomUI Setting
		$this->maniaControl->manialinkManager->customUIManager->setChallengeInfoVisible(false);

		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_BEGINMAP, $this, 'handleOnBeginMap');
		$this->maniaControl->callbackManager->registerCallbackListener(MapManager::CB_ENDMAP, $this, 'handleOnEndMap');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'handlePlayerDisconnect');

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSX, 140);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSY, 86.1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_WIDTH, 40);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_HEIGHT, 9);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSX, -143);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_POSY, 86);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_WIDTH, 35);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT, 9);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSX, 140);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_POSY, 64.7);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_WIDTH, 40);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT, 12);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED, true);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_POSX, 154.9);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_POSY, 78.5);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_WIDTH, 10);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_CLOCK_WIDGET_HEIGHT, 5.5);

		$this->displayWidgets();
		return true;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->closeWidget(self::MLID_CLOCKWIDGET);
		$this->closeWidget(self::MLID_SERVERINFOWIDGET);
		$this->closeWidget(self::MLID_MAPWIDGET);
		$this->closeWidget(self::MLID_NEXTMAPWIDGET);
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->timerManager->unregisterTimerListenings($this);
		unset($this->maniaControl);
	}

	/**
	 * Displays the Widgets onLoad
	 *
	 * @param array $callback
	 */
	private function displayWidgets() {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget();
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}

	/**
	 * Displays the Map Widget
	 *
	 * @param String $login
	 */
	public function displayMapWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_MAPWIDGET);
		$script    = new Script();
		$maniaLink->setScript($script);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		$script->addMapInfoButton($backgroundQuad);

		$map = $this->maniaControl->mapManager->getCurrentMap();

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(1.5);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($map->name));
		$label->setTextColor("FFF");

		$label = new Label_Text();
		$frame->add($label);
		$label->setX(0);
		$label->setY(-1.4);

		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($map->authorLogin);
		$label->setTextColor("FFF");

		if (isset($map->mx->pageurl)) {
			$quad = new Quad();
			$frame->add($quad);
			$quad->setImageFocus($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON_MOVER));
			$quad->setImage($this->maniaControl->manialinkManager->iconManager->getIcon(IconManager::MX_ICON));
			$quad->setPosition(-$width / 2 + 4, -1.5, -0.5);
			$quad->setSize(4, 4);
			$quad->setHAlign(Control::CENTER);
			$quad->setUrl($map->mx->pageurl);
		}

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}

	/**
	 * Displays the Clock Widget
	 *
	 * @param bool $login
	 */
	public function displayClockWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_CLOCKWIDGET);
		$script = $maniaLink->getScript();

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(1.5);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::TOP);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setTextColor("FFF");
		$script->addTimeLabel($label, true);

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}

	/**
	 * Displays the Server Info Widget
	 *
	 * @param String $login
	 */
	public function displayServerInfoWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_SERVERINFOWIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$maxPlayers = $this->maniaControl->client->getMaxPlayers();

		$maxSpectators = $this->maniaControl->client->getMaxSpectators();

		$serverName = $this->maniaControl->client->getServerName();

		$players        = $this->maniaControl->playerManager->getPlayers();
		$playerCount    = 0;
		$spectatorCount = 0;
		/**
		 * @var Player $player
		 */
		foreach($players as $player) {
			if ($player->isSpectator) {
				$spectatorCount++;
			} else {
				$playerCount++;
			}
		}

		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($serverName));
		$label->setTextColor("FFF");
		//$label->setAutoNewLine(true);
		// Player Quad / Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(-$width / 2 + 9, -1.5, 0.2);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($playerCount . " / " . $maxPlayers['NextValue']);
		$label->setTextColor("FFF");

		$quad = new Quad_Icons128x128_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Multiplayer);
		$quad->setPosition(-$width / 2 + 7, -1.6, 0.2);
		$quad->setSize(2.5, 2.5);
		$quad->setHAlign(Control::CENTER);

		// Spectator Quad / Label
		$label = new Label_Text();
		$frame->add($label);
		$label->setPosition(2, -1.5, 0.2);
		$label->setAlign(Control::LEFT, Control::CENTER);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($spectatorCount . " / " . $maxSpectators['NextValue']);
		$label->setTextColor("FFF");

		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Camera);
		$quad->setPosition(0, -1.6, 0.2);
		$quad->setSize(3.3, 2.5);
		$quad->setHAlign(Control::CENTER);

		// Favorite quad
		$quad = new Quad_Icons64x64_1();
		$frame->add($quad);
		$quad->setSubStyle($quad::SUBSTYLE_StateFavourite);
		$quad->setPosition($width / 2 - 4, -1.5, -0.5);
		$quad->setSize(3, 3);
		$quad->setHAlign(Control::CENTER);
		$quad->setManialink('mcontrol?favorite=' . urlencode($this->maniaControl->server->login));

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}

	/**
	 * Handle on Begin Map
	 *
	 * @param Map $map
	 */
	public function handleOnBeginMap(Map $map) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget();
		}
		$this->closeWidget(self::MLID_NEXTMAPWIDGET);
	}

	/**
	 * Closes a Widget
	 *
	 * @param $widgetId
	 */
	public function closeWidget($widgetId) {
		$emptyManialink = new ManiaLink($widgetId);
		$manialinkText  = $emptyManialink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText);
	}

	/**
	 * Handle on End Map
	 *
	 * @param Map $map
	 */
	public function handleOnEndMap(Map $map) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_ACTIVATED)) {
			$this->displayNextMapWidget();
		}
	}

	/**
	 * Displays the Next Map (Only at the end of the Map)
	 *
	 * @param bool $login
	 */
	public function displayNextMapWidget($login = false) {
		$pos_x        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_POSX);
		$pos_y        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_POSY);
		$width        = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_WIDTH);
		$height       = $this->maniaControl->settingManager->getSetting($this, self::SETTING_NEXTMAP_WIDGET_HEIGHT);
		$quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();
		$labelStyle   = $this->maniaControl->manialinkManager->styleManager->getDefaultLabelStyle();

		$maniaLink = new ManiaLink(self::MLID_NEXTMAPWIDGET);

		// mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($pos_x, $pos_y);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Check if the Next Map is a queued Map
		$queuedMap = $this->maniaControl->mapManager->mapQueue->getNextMap();

		/**
		 * @var Player $requester
		 */
		$requester = null;
		// if the nextmap is not a queued map, get it from map info
		if ($queuedMap == null) {
			$map    = $this->maniaControl->client->getNextMapInfo();
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
		$label->setY($height / 2 - 2.3);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setText("Next Map");
		$label->setTextColor("FFF");
		$label->setStyle($labelStyle);

		$label = new Label_Text();
		$frame->add($label);
		$label->setY($height / 2 - 5.5);
		$label->setX(0);
		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1.3);
		$label->setText($name);
		$label->setTextColor("FFF");

		$label = new Label_Text();
		$frame->add($label);
		$label->setX(0);
		$label->setY(-$height / 2 + 4);

		$label->setAlign(Control::CENTER, Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($author);
		$label->setTextColor("FFF");

		if ($requester != null) {
			$label = new Label_Text();
			$frame->add($label);
			$label->setX(0);
			$label->setY(-$height / 2 + 2);
			$label->setAlign(Control::CENTER, Control::CENTER);
			$label->setZ(0.2);
			$label->setTextSize(1);
			$label->setScale(0.7);
			$label->setText($author);
			$label->setTextColor("F80");
			$label->setText("Requested by " . $requester->nickname);
		}

		// Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		// Display Map Widget
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->displayMapWidget($player->login);
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget($player->login);
		}
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerDisconnect(Player $player) {
		if ($this->maniaControl->settingManager->getSetting($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		}
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float,,
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return 'Plugin offers some Widgets';
	}
}