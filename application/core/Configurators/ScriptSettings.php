<?php

namespace ManiaControl\Configurators;

use FML\Script\Script;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Formatter;
use ManiaControl\ManiaControl;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Script\Pages;
use FML\Script\Tooltips;
use FML\Controls\Control;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Entry;
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
	const ACTION_PREFIX_SETTING = 'ScriptSetting.';
	const ACTION_SETTING_BOOL = 'ScriptSetting.ActionBoolSetting.';
	const CB_SCRIPTSETTING_CHANGED = 'ScriptSettings.SettingChanged';
	
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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 
				'handleManialinkPageAnswer');
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
		$frame = new Frame();
		
		$this->maniaControl->client->query('GetModeScriptInfo');
		$scriptInfo = $this->maniaControl->client->getResponse();
		if (isset($scriptInfo['faultCode'])) {
			// Not in script mode
			$label = new Label();
			$frame->add($label);
			$label->setText($scriptInfo['faultString']);
			return $frame;
		}
		$scriptParams = $scriptInfo['ParamDescs'];
		
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();
		
		// Config
		$pagerSize = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount = 13;
		
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
		$y = 0.;
		foreach ($scriptParams as $index => $scriptParam) {
			$settingName = $scriptParam['Name'];
			
			if (!isset($scriptSettings[$settingName])) continue;
			
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
			}
			else if ($settingValue === true) {
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
			}
			else {
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setHAlign(Control::CENTER);
				$entry->setX($width / 2 * 0.55);
				$entry->setTextSize(1);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setName(self::ACTION_PREFIX_SETTING . $settingName);
				$entry->setDefault($settingValue);
			}
			
			$descriptionLabel = new Label();
			$pageFrame->add($descriptionLabel);
			$descriptionLabel->setHAlign(Control::LEFT);
			$descriptionLabel->setPosition($width * -0.45, $height * -0.44);
			$descriptionLabel->setSize($width * 0.7, $settingHeight);
			$descriptionLabel->setTextSize($labelTextSize);
			$descriptionLabel->setTranslate(true);
			// $descriptionLabel->setTextPrefix('Desc: ');
			$descriptionLabel->setText($scriptParam['Desc']);
			$script->addTooltip($nameLabel, $descriptionLabel);
			
			$y -= $settingHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
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
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();
		
		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);
		
		$newSettings = array();
		foreach ($configData[3] as $setting) {
			if (substr($setting['Name'], 0, $prefixLength) != self::ACTION_PREFIX_SETTING) continue;
			
			$settingName = substr($setting['Name'], $prefixLength);
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
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		$boolSetting = (strpos($actionId, self::ACTION_SETTING_BOOL) === 0);
		if (!$boolSetting) return;
		
		$actionArray = explode(".", $actionId);
		$setting = $actionArray[2];
		
		$login = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);
		
		$this->toggleBooleanSetting($setting, $player);
	}

	/**
	 * Toogle a Boolean Setting
	 *
	 * @param Player $player
	 * @param $setting
	 */
	public function toggleBooleanSetting($setting, Player $player) {
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();
		
		if (!isset($scriptSettings[$setting])) {
			var_dump('no setting ' . $setting);
			return;
		}
		
		$newSettings = array();
		$newSettings[$setting] = ($scriptSettings[$setting] ? false : true);
		
		$this->applyNewScriptSettings($newSettings, $player);
	}

	/**
	 * Apply the Array of new Script Settings
	 *
	 * @param array $newSettings
	 * @param Player $player
	 */
	private function applyNewScriptSettings(array $newSettings, Player $player) {
		if (!$newSettings) return;
		$success = $this->maniaControl->client->query('SetModeScriptSettings', $newSettings);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}
		
		// Notifications
		foreach ($newSettings as $setting => $value) {
			$title = $this->maniaControl->authenticationManager->getAuthLevelName($player->authLevel);
			$chatMessage = '$ff0' . $title . ' $<' . $player->nickname . '$> set ScriptSetting ';
			$chatMessage .= '$<' . '$fff' . preg_replace('/^S_/', '', $setting) . '$z$s$ff0 ';
			$chatMessage .= 'to $fff' . $this->parseSettingValue($value) . '$>!';
			
			$this->maniaControl->chat->sendInformation($chatMessage);
			$this->maniaControl->log(Formatter::stripCodes($chatMessage));
			
			// Trigger own callback
			$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTING_CHANGED, 
					array(self::CB_SCRIPTSETTING_CHANGED, $setting, $value));
		}
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
		return (string) $value;
	}
}
