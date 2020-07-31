<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons128x128_1;
use FML\Controls\Quads\Quad_Icons128x32_1;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Elements\SimpleScript;
use FML\ManiaLink;
use FML\Script\Script;
use FML\XmlRpc\TMUIProperties;
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
use Maniaplanet\DedicatedServer\Xmlrpc\FaultException;

/**
 * ManiaControl Widget Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class WidgetPlugin implements CallbackListener, TimerListener, Plugin {
	/*
	 * Constants
	 */
	const PLUGIN_ID      = 1;
	const PLUGIN_VERSION = 0.13;
	const PLUGIN_NAME    = 'WidgetPlugin';
	const PLUGIN_AUTHOR  = 'MCTeam';

	// MapWidget Properties
	const MLID_MAP_WIDGET                = 'WidgetPlugin.MapWidget';
	const SETTING_MAP_WIDGET_ACTIVATED   = 'Map-Widget Activated';
	const SETTING_MAP_WIDGET_NICKNAME    = 'Map-Widget display Author Nickname instead of Login';
	const SETTING_MAP_WIDGET_POSX        = 'Map-Widget-Position: X';
	const SETTING_MAP_WIDGET_POSY        = 'Map-Widget-Position: Y';
	const SETTING_MAP_WIDGET_HEIGHT      = 'Map-Widget-Size: Height';
	const SETTING_MAP_WIDGET_WIDTH       = 'Map-Widget-Size: Width';
	const SETTING_MAP_WIDGET_TIME_AUTHOR = 'Map-Widget-Time: Show Author';
	const SETTING_MAP_WIDGET_TIME_GOLD   = 'Map-Widget-Time: Show Gold';
	const SETTING_MAP_WIDGET_TIME_SILVER = 'Map-Widget-Time: Show Silver';
	const SETTING_MAP_WIDGET_TIME_BRONZE = 'Map-Widget-Time: Show Bronze';

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

	// Nadeo Widget Properties
	const SETTING_TM_LIVE_INFO_WIDGET_POSX   = "Nadeo LiveInfo-Widget-Position: X";
	const SETTING_TM_LIVE_INFO_WIDGET_POSY   = "Nadeo LiveInfo-Widget-Position: Y";
	const SETTING_TM_ROUND_SCORE_WIDGET_POSX = "Nadeo RoundScore-Widget-Position: X";
	const SETTING_TM_ROUND_SCORE_WIDGET_POSY = "Nadeo RoundScore-Widget-Position: Y";
	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl         = null;
	private $lastWidgetUpdateTime = 0;

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

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'handleOnBeginMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::MP_PODIUMSTART, $this, 'handleOnEndMap');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECT, $this, 'updateWidgets');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(PlayerManager::CB_PLAYERINFOSCHANGED, $this, 'updateWidgets');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(SettingManager::CB_SETTING_CHANGED, $this, 'updateSettings');

		// Settings
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_ACTIVATED, true);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_NICKNAME, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_POSX, 160 - 20);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_POSY, 90 - 4.5);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_HEIGHT, 9.);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_WIDTH, 40);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_TIME_AUTHOR, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_TIME_GOLD, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_TIME_SILVER, false);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_MAP_WIDGET_TIME_BRONZE, false);

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

		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSX, -122);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSY, 84);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSX, 104);
		$this->maniaControl->getSettingManager()->initSetting($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSY, 65);


		$this->displayWidgets();


		// Set CustomUI Setting
		$this->maniaControl->getManialinkManager()->getCustomUIManager()->setChallengeInfoVisible(false); //TODO verify if still needed

		//Trackmania Nadeo Widgets
		$uiProperties = new TMUIProperties();
		//Map Info Widget
		$uiProperties->setMapInfoVisible(false);
		$livePosX = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSX);
		$livePosY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSY);
		$uiProperties->setLiveInfoPosition($livePosX, $livePosY, 5);

		//Rounds Scoretable
		$roundScorePosX = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSX);
		$roundScorePosY = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSY);
		$uiProperties->setRoundScoresPosition($roundScorePosX, $roundScorePosY, 5);

		//MultiLap and Chase Info Widget top Right
		$uiProperties->setMultiLapInfoPosition(140, 65, 5);

		$this->maniaControl->getModeScriptEventManager()->setTrackmaniaUIProperties((string) $uiProperties);

		return true;
	}

	/**
	 * Display the Widgets
	 */
	private function displayWidgets() {
		// Display Map Widget
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_ACTIVATED)) {
			$this->maniaControl->getModeScriptEventManager()->setSiegeProgressionUIPosition("160.", "-67.", "0.");
			$this->displayMapWidget();
		} else {
			$this->closeWidget(self::MLID_MAP_WIDGET);
		}

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_CLOCK_WIDGET_ACTIVATED)) {
			$this->displayClockWidget();
		} else {
			$this->closeWidget(self::MLID_CLOCK_WIDGET);
		}

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$this->displayServerInfoWidget();
		} else {
			$this->closeWidget(self::MLID_SERVERINFO_WIDGET);
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
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);
		//$backgroundQuad->addMapInfoFeature();

		$map = $this->maniaControl->getMapManager()->getCurrentMap();

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, $height/2 - 3, 0.2);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($map->name));
		$label->setTextColor('fff');
		$label->setSize($width - 5, $height);

		$label = new Label_Text('author_label');
		$frame->addChild($label);
		$label->setPosition(0, $height/2 - 6, 0.2);
		$label->setScale(0.8);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1);

		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_NICKNAME)) {
			$nicknameScript = array(
				'declare CMlLabel Author_Label <=> (Page.GetFirstChild("author_label") as CMlLabel);',
				'if (Map != Null) {',
				'	Author_Label.SetText(Map.AuthorNickName);',
				'}'
			);
			$simpleScript = new SimpleScript();
			$simpleScript->setText(implode(PHP_EOL, $nicknameScript));
			$frame->addChild($simpleScript);
		} else {
			$label->setText($map->authorLogin);
			$label->setTextColor('fff');
		}

		$displayTimeAuthor = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_TIME_AUTHOR);
		$displayTimeGold   = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_TIME_GOLD  );
		$displayTimeSilver = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_TIME_SILVER);
		$displayTimeBronze = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_MAP_WIDGET_TIME_BRONZE);
		if ($displayTimeAuthor || $displayTimeGold || $displayTimeSilver || $displayTimeBronze) {
			$times = array();
			if ($displayTimeAuthor && $map->authorTime > 0) {
				array_push($times, '$8b4' . Formatter::formatTime($map->authorTime));
			}
			if ($displayTimeGold && $map->goldTime > 0) {
				array_push($times, '$dc5' . Formatter::formatTime($map->goldTime));
			}
			if ($displayTimeSilver && $map->silverTime > 0) {
				array_push($times, '$aaa' . Formatter::formatTime($map->silverTime));
			}
			if ($displayTimeBronze && $map->bronzeTime > 0) {
				array_push($times, '$c95' . Formatter::formatTime($map->bronzeTime));
			}
			$times = '$s' . implode('$s$fff // $s', $times);

			$quad = new Quad_Icons128x32_1();
			$frame->addChild($quad);
			$quad->setPosition(-$width/2 + 3, -$height/2 + 3, -0.5);
			$quad->setSize(3.5, 3.5);
			$quad->setSubStyle($quad::SUBSTYLE_RT_TimeAttack);

			$label = new Label_Text();
			$frame->addChild($label);
			$label->setPosition(0, -3, 0.2);
			$label->setScale(0.8);
			$label->setSize($width - 5, $height);
			$label->setText($times);
			$label->setTextColor('fff');
			$label->setTextSize(1);
		}

		if (isset($map->mx->pageurl)) {
			$quad = new Quad();
			$frame->addChild($quad);
			$quad->setImageFocusUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER));
			$quad->setImageUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON));
			$quad->setPosition($width/2 - 3, -$height/2 + 3, -0.5);
			$quad->setSize(3.5, 3.5);
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
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setVerticalAlign($label::TOP);
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
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$serverName = $this->maniaControl->getClient()->getServerName();

		$playerCount = $this->maniaControl->getPlayerManager()->getPlayerCount(true);
		$maxPlayers  = $this->maniaControl->getClient()->getMaxPlayers();

		$spectatorCount = $this->maniaControl->getPlayerManager()->getSpectatorCount();
		$maxSpectators  = $this->maniaControl->getClient()->getMaxSpectators();

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, 1.5, 0.2);
		$label->setSize($width - 5, $height);
		$label->setTextSize(1.3);
		$label->setText(Formatter::stripDirtyCodes($serverName));
		$label->setTextColor('fff');

		// Player Quad / Label
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(-$width / 2 + 9, -1.5, 0.2);
		$label->setHorizontalAlign($label::LEFT);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($playerCount . " / " . $maxPlayers['NextValue']);
		$label->setTextColor('fff');
		$label->setWidth($width / 2 - 8);

		$quad = new Quad_Icons128x128_1();
		$frame->addChild($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Multiplayer);
		$quad->setPosition(-$width / 2 + 7, -1.6, 0.2);
		$quad->setSize(2.5, 2.5);

		// Spectator Quad / Label
		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(3, -1.5, 0.2);
		$label->setHorizontalAlign($label::LEFT);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($spectatorCount . " / " . $maxSpectators['NextValue']);
		$label->setTextColor('fff');
		$label->setWidth($width / 2 - 8);

		$quad = new Quad_Icons64x64_1();
		$frame->addChild($quad);
		$quad->setSubStyle($quad::SUBSTYLE_Camera);
		$quad->setPosition(1, -1.6, 0.2);
		$quad->setSize(3.3, 2.5);

		// Favorite quad
		$quad = new Quad_Icons64x64_1();
		$frame->addChild($quad);
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
		$this->maniaControl->getModeScriptEventManager()->setSiegeProgressionUIPosition("160.", "90.", "0.");

		$this->closeWidget(self::MLID_CLOCK_WIDGET);
		$this->closeWidget(self::MLID_SERVERINFO_WIDGET);
		$this->closeWidget(self::MLID_MAP_WIDGET);
		$this->closeWidget(self::MLID_NEXTMAP_WIDGET);

		// Set CustomUI Setting
		$this->maniaControl->getManialinkManager()->getCustomUIManager()->setChallengeInfoVisible(true); //TODO verify if still needed

		//TrackMania (Set Back Nadeo Defaults)
		$uiProperties = new TMUIProperties();
		$uiProperties->setMapInfoVisible(true);
		$uiProperties->setLiveInfoPosition(-159, 84, 5);
		$uiProperties->setRoundScoresPosition(-158.5, 40, 5);
		$uiProperties->setMultiLapInfoPosition(140, 84, 5);

		$this->maniaControl->getModeScriptEventManager()->setTrackmaniaUIProperties((string) $uiProperties);
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
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition($posX, $posY);

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Check if the Next Map is a queued Map
		$queuedMap = $this->maniaControl->getMapManager()->getMapQueue()->getNextMap();

		/** @var Player $requester */
		$requester = null;
		$map       = null;
		$name      = '-';
		$author    = '-';
		// if the nextmap is not a queued map, get it from map info
		if ($queuedMap) {
			$requester = $queuedMap[0];
			$map       = $queuedMap[1];
			$name      = $map->name;
			$author    = $map->authorLogin;
		} else {
			try {
				$map    = $this->maniaControl->getClient()->getNextMapInfo();
				$name   = Formatter::stripDirtyCodes($map->name);
				$author = $map->author;
			} catch (FaultException $exception) {
				// TODO: replace by more specific exception as soon as it's available (No next map currently defined.)
			}
		}

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, $height / 2 - 2.3, 0.2);
		$label->setTextSize(1);
		$label->setText('Next Map');
		$label->setTextColor('fff');
		$label->setStyle($labelStyle);

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, $height / 2 - 5.5, 0.2);
		$label->setTextSize(1.3);
		$label->setText($name);
		$label->setTextColor('fff');
		$label->setSize($width - 5, $height);

		$label = new Label_Text();
		$frame->addChild($label);
		$label->setPosition(0, -$height / 2 + 4);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setScale(0.8);
		$label->setText($author);
		$label->setTextColor('fff');

		if ($requester) {
			$label = new Label_Text();
			$frame->addChild($label);
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
	public function updateSettings(Setting $setting) {
		if ($setting->belongsToClass($this)) {
			$this->displayWidgets();

			//Update Nadeo Default Widgets
			if ($setting->setting == self::SETTING_TM_LIVE_INFO_WIDGET_POSX || $setting->setting == self::SETTING_TM_LIVE_INFO_WIDGET_POSY) {
				$uiProperties = new TMUIProperties();
				$livePosX     = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSX);
				$livePosY     = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_LIVE_INFO_WIDGET_POSY);
				$uiProperties->setLiveInfoPosition($livePosX, $livePosY, 5);
				$this->maniaControl->getModeScriptEventManager()->setTrackmaniaUIProperties((string) $uiProperties);
			} elseif ($setting->setting == self::SETTING_TM_ROUND_SCORE_WIDGET_POSX || $setting->setting == self::SETTING_TM_ROUND_SCORE_WIDGET_POSY) {
				$uiProperties = new TMUIProperties();
				$roundScoreX  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSX);
				$roundScoreY  = $this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_TM_ROUND_SCORE_WIDGET_POSY);
				$uiProperties->setLiveInfoPosition($roundScoreX, $roundScoreY, 5);
				$this->maniaControl->getModeScriptEventManager()->setTrackmaniaUIProperties((string) $uiProperties);
			}
		}
	}

	/**
	 * Update Widget on certain callbacks
	 */
	public function updateWidgets() {
		if ($this->maniaControl->getSettingManager()->getSettingValue($this, self::SETTING_SERVERINFO_WIDGET_ACTIVATED)) {
			$time = time();
			//Update Max once per second
			//TODO the one time can be removed due the new PlayerInfosChanged Callback
			if ($this->lastWidgetUpdateTime < ($time - 1)) {
				$this->displayServerInfoWidget();
				$this->lastWidgetUpdateTime = $time;
			}
		}
	}
}
