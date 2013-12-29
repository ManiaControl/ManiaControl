<?php


use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\ManiaLink;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Callbacks\CallbackManager;

class WidgetPlugin implements CallbackListener, Plugin {
	/**
	 * Constants
	 */
	const PLUGIN_ID = 8;
	const PLUGIN_VERSION = 0.1;
	const PLUGIN_NAME = 'WidgetPlugin';
	const PLUGIN_AUTHOR = 'kremsy';
	const MLID_MAPWIDGET = 'WidgetPlugin.MapWidget';

	//MapWidget Properties
	const SETTING_MAP_WIDGET_POSX = 'Map-Widget-Position: X';
	const SETTING_MAP_WIDGET_POSY = 'Map-Widget-Position: Y';
	const SETTING_MAP_WIDGET_WIDTH = 'Map-Widget-Size: Width';
	const SETTING_MAP_WIDGET_HEIGHT = 'Map-Widget-Size: Height';
	/**
	 * Private properties
	 */
	private $maniaControl = null;

 	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl){
		$this->maniaControl = $maniaControl;
		// TODO: Implement load() method.


		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ONINIT, $this, 'handleOnInit');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSX, 160 - 20);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_POSY, 90 - 4.5);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_WIDTH, 40);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAP_WIDGET_HEIGHT, 9.);



		return true;
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload(){
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		unset($this->maniaControl);
	}


	/**
	 * Displays the Map Widget
	 * @param $login
	 */
	public function displayMapWidget($login = false){

		$xml = "<manialinks><manialink id='0'><quad></quad></manialink>
				<custom_ui>
				<challenge_info visible='false'/>
				</custom_ui>
				</manialinks>";

		$this->maniaControl->manialinkManager->sendManialink($xml);


		$pos_x = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSX);
		$pos_y = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_POSY);
		$width = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_WIDTH);
		$height = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAP_WIDGET_HEIGHT);
		$quadStyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadStyle();
		$quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultQuadSubstyle();

		$maniaLink = new ManiaLink(self::MLID_MAPWIDGET);

		// Create script and features
		/*$script = new Script();
		$maniaLink->setScript($script);

		$tooltips = new Tooltips();
		$script->addFeature($tooltips); */

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($width,$height);
		$frame->setPosition($pos_x, $pos_y);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($width,$height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		$map = $this->maniaControl->mapManager->getCurrentMap();

		$label = new Label_Text();
		$frame->add($label);
		$label->setY(1.3);
		$label->setX(0);
		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1.3);
		$label->setText($map->name);
		$label->setTextColor("FFF");

		$label = new Label_Text();
		$frame->add($label);
		$label->setX(0);
		$label->setY(-1.3);

		$label->setAlign(Control::CENTER,Control::CENTER);
		$label->setZ(0.2);
		$label->setTextSize(1);
		$label->setText($map->authorLogin);
		$label->setTextColor("FFF");


		//Send manialink
		$manialinkText = $maniaLink->render()->saveXML();
		$this->maniaControl->manialinkManager->sendManialink($manialinkText, $login);
	}


	/**
	 * Handle ManiaControl OnInit callback
	 *
	 * @param array $callback
	 */
	public function handleOnInit(array $callback) {
		$this->displayMapWidget();
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
//		$login = $callback[1][0];
//		$player = $this->maniaControl->playerManager->getPlayer($login);
	//	if (!$player) {
		//	return;
		//}
	//	$this->queryManialinkUpdateFor($player);
	}


	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId(){
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName(){
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float,,
	 */
	public static function getVersion(){
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor(){
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription(){
		return null;
	}
}