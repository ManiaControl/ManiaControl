<?php

namespace ManiaControl\Configurators;

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
 * Class offering a configurator for current script settings
 *
 * @author steeffeen & kremsy
 */
class ScriptSettings implements ConfiguratorMenu,CallbackListener {
	/**
	 * Constants
	 */
	const ACTION_PREFIX_SETTING = 'ScriptSetting.';
	const ACTION_SETTING_BOOL = 'ScriptSetting.ActionBoolSetting';

	const CB_SCRIPTSETTINGS_CHANGED =  'ScriptSettings.SettingsChanged';

	/**
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new script settings instance
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
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
	public function getMenu($width, $height, Pages $pages, Tooltips $tooltips) {
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
		
		$pageCountLabel = new Label();
		$frame->add($pageCountLabel);
		$pageCountLabel->setHAlign(Control::RIGHT);
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44, 1);
		$pageCountLabel->setStyle('TextTitle1');
		$pageCountLabel->setTextSize(2);
		
		// Setting pages
		$pageFrames = array();
		$y = 0.;
		foreach ($scriptParams as $index => $scriptParam) {
			$settingName = $scriptParam['Name'];

			if (!isset($scriptSettings[$settingName])) continue;

			if (!isset($pageFrame)) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				array_push($pageFrames, $pageFrame);
				$y = $height * 0.41;
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
			$action = '';
			if($settingValue === false){
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
			}else if($settingValue === true){
				$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
			}

			if($substyle != ''){
				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.545);
				$quad->setZ(-0.01);
				$quad->setSubStyle($substyle);
				$quad->setSize(4, 4);
				$quad->setHAlign(Control::CENTER);
				$quad->setAction(self::ACTION_SETTING_BOOL . "." . $settingName);
			}else{
				$entry = new Entry();
				$settingFrame->add($entry);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setHAlign(Control::CENTER);
				$entry->setX($width /2 * 0.55);
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
			$descriptionLabel->setTranslate(true);
			$descriptionLabel->setTextPrefix('Desc: ');
			$descriptionLabel->setText($scriptParam['Desc']);
			$tooltips->add($nameLabel, $descriptionLabel);
			
			$y -= $settingHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}
		}
		
		$pages->add(array(-1 => $pagerPrev, 1 => $pagerNext), $pageFrames, $pageCountLabel);
		
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

		$chatMessage = '';
		$newSettings = array();
		foreach ($configData[3] as $setting) {
			if (substr($setting['Name'], 0, $prefixLength) != self::ACTION_PREFIX_SETTING) continue;

			$settingName = substr($setting['Name'], $prefixLength);

			foreach($scriptSettings as $key => $value){
				if($key == $settingName){
					//Check if something has been changed
					if($setting["Value"] != $value){
						$chatMessage .= '$FFF'.$settingName.'$z$s$FF0 to $FFF' . $setting["Value"].', ';
					}
					//Setting found, cast type, break the inner loop
					settype($setting["Value"], gettype($value));
					break;
				}
			}
			$newSettings[$settingName] = $setting["Value"];
		}

		$success = $this->maniaControl->client->query('SetModeScriptSettings', $newSettings);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}


		$chatMessage = substr($chatMessage, 0, strlen($chatMessage)-2);
		$chatMessage = str_replace("S_","",$chatMessage);

		$title = $this->maniaControl->authenticationManager->getAuthLevelName($player->authLevel);

		if($chatMessage != ''){
			$this->maniaControl->chat->sendInformation('$ff0' . $title . ' $<' . $player->nickname . '$> set Scriptsettings $<' . $chatMessage . '$>!');
		}

		// log console message
		$this->maniaControl->log(Formatter::stripCodes($title . ' ' . $player->nickname . ' set Scriptsettings ' . $chatMessage . '!'));

		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED, array(self::CB_SCRIPTSETTINGS_CHANGED));
	}

	/**
	 * Called on ManialinkPageAnswer
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback){
		$actionId = $callback[1][2];
		$boolSetting = (strpos($actionId, self::ACTION_SETTING_BOOL) === 0);

		if(!$boolSetting)
			return;

		$actionArray = explode(".", $actionId);

		$player = $this->maniaControl->playerManager->getPlayer($callback[1][1]);
		$this->setCheckboxSetting($player, $actionArray[2]);
	}

	/**
	 * Toogle a boolean value setting
	 * @param Player $player
	 * @param        $setting
	 */
	public function setCheckboxSetting(Player $player, $setting){
		$this->maniaControl->client->query('GetModeScriptSettings');
		$scriptSettings = $this->maniaControl->client->getResponse();

		$newSetting = array();
		foreach($scriptSettings as $key => $value){
			if($key == $setting){ //Setting found
				$newSetting[$key] = $value == true ? false : true;  //toggle setting
				break;
			}
		}

		$success = $this->maniaControl->client->query('SetModeScriptSettings', $newSetting);
		if (!$success) {
			$this->maniaControl->chat->sendError('Error occurred: ' . $this->maniaControl->getClientErrorText(), $player->login);
			return;
		}

		$valString = ($newSetting[$setting]) ? 'true' : 'false';
		$chatMessage = '$FFF'.$setting.'$z$s$FF0 to $FFF' . $valString;
		$chatMessage = str_replace("S_","",$chatMessage);

		$title = $this->maniaControl->authenticationManager->getAuthLevelName($player->authLevel);
		$this->maniaControl->chat->sendInformation('$ff0' . $title . ' $<' . $player->nickname . '$> set Scriptsetting $<' . $chatMessage . '$>!');

		// log console message
		$this->maniaControl->log(Formatter::stripCodes($title . ' ' . $player->nickname . ' set Scriptsettings ' . $chatMessage . '!'));
		
		// Trigger own callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_SCRIPTSETTINGS_CHANGED, array(self::CB_SCRIPTSETTINGS_CHANGED));
	}
}
