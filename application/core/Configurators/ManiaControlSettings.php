<?php
/**
 * Class offering a Configurator for Script Settings
 *
 * @author steeffeen & kremsy
 */
namespace ManiaControl\Configurators;


use FML\Controls\Control;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;

class ManiaControlSettings implements ConfiguratorMenu, CallbackListener {
	/**
	 * Constants
	 */
	const TITLE                                 = 'ManiaControl Settings';
	const ACTION_PREFIX_SETTING                 = 'ManiaControlSettings';
	const ACTION_SETTING_BOOL                   = 'ManiaControlSettings.ActionBoolSetting.';
	const SETTING_PERMISSION_CHANGE_MC_SETTINGS = 'Change ManiaControl Settings';

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
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->authenticationManager->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_MC_SETTINGS, AuthenticationManager::AUTH_LEVEL_SUPERADMIN);
	}

	/**
	 * Get the Menu Title
	 *
	 * @return string
	 */
	public function getTitle() {
		return self::TITLE;
	}

	/**
	 * Get the Configurator Menu Frame
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @return \FML\Controls\Frame
	 */
	public function getMenu($width, $height, Script $script) {
		$pagesId = 'ManiaControlSettingsPages';
		$frame   = new Frame();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount  = 13;

		//Pagers
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


		/** @var  ManiaControl/SettingManager $this->maniaControl->settingManager */
		$settings = $this->maniaControl->settingManager->getSettings();

		$pluginClasses = $this->maniaControl->pluginManager->getPluginClasses();

		$pageFrames = array();
		$y          = 0;
		$index      = 1;
		$prevClass  = '';
		foreach($settings as $id => $setting) {
			//Don't display Plugin Settings
			if (array_search($setting->class, $pluginClasses) !== FALSE) {
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
				$script->addPage($pageFrame, count($pageFrames), $pagesId);
			}

			$settingFrame = new Frame();
			$pageFrame->add($settingFrame);
			$settingFrame->setY($y);

			//Headline Label
			if ($prevClass != $setting->class) {
				$headLabel = new Label_Text();
				$settingFrame->add($headLabel);
				$headLabel->setHAlign(Control::LEFT);
				$headLabel->setX($width * -0.46);
				$headLabel->setSize($width * 0.6, $settingHeight);
				$headLabel->setStyle($headLabel::STYLE_TextCardSmall);
				$headLabel->setTextSize($labelTextSize);
				$headLabel->setText($setting->class);
				$headLabel->setTextColor("F00");

				$y -= $settingHeight;


				if ($index % $pageMaxCount == $pageMaxCount - 1) {
					$pageFrame = new Frame();
					$frame->add($pageFrame);
					if (!empty($pageFrames)) {
						$pageFrame->setVisible(false);
					}
					array_push($pageFrames, $pageFrame);
					$y = $height * 0.41;
					$script->addPage($pageFrame, count($pageFrames), $pagesId);
				}

				$index++;

				$settingFrame = new Frame();
				$pageFrame->add($settingFrame);
				$settingFrame->setY($y);
			} //Headline Label finished

			$nameLabel = new Label_Text();
			$settingFrame->add($nameLabel);
			$nameLabel->setHAlign(Control::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.6, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($setting->setting);
			$nameLabel->setTextColor("FFF");

			$substyle = '';


			$entry = new Entry();
			$settingFrame->add($entry);
			$entry->setStyle(Label_Text::STYLE_TextValueSmall);
			$entry->setHAlign(Control::CENTER);
			$entry->setX($width / 2 * 0.65);
			$entry->setTextSize(1);
			$entry->setSize($width * 0.3, $settingHeight * 0.9);
			$entry->setName(self::ACTION_PREFIX_SETTING . '.' . $setting->index);
			$entry->setDefault($setting->value);


			if ($setting->type == "bool") {
				if ($setting->value == "0") {
					$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlRed;
				} else if ($setting->value == "1") {
					$substyle = Quad_Icons64x64_1::SUBSTYLE_LvlGreen;
				}

				$quad = new Quad_Icons64x64_1();
				$settingFrame->add($quad);
				$quad->setX($width / 2 * 0.6);
				$quad->setZ(-0.01);
				$quad->setSubStyle($substyle);
				$quad->setSize(4, 4);
				$quad->setHAlign(Control::CENTER2);
				$quad->setAction(self::ACTION_SETTING_BOOL . $setting->index);
				$entry->setVisible(false);
			}


			$prevClass = $setting->class;

			$y -= $settingHeight;
			if ($index % $pageMaxCount == $pageMaxCount - 1) {
				unset($pageFrame);
			}

			$index++;
		}

		return $frame;
	}

	/**
	 * Save the Config Data
	 *
	 * @param array  $configData
	 * @param Player $player
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MC_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$prefix = explode(".", $configData[3][0]['Name']);
		if ($prefix[0] != self::ACTION_PREFIX_SETTING) {
			return;
		}

		$maniaControlSettings = $this->maniaControl->settingManager->getSettings();

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		foreach($configData[3] as $setting) {
			$settingName = substr($setting['Name'], $prefixLength + 1);

			$oldSetting = $maniaControlSettings[$settingName];
			if ($setting['Value'] == $oldSetting->value || $oldSetting->type == 'bool') {
				continue;
			}

			$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, $setting['Value']);
		}

		//Reopen the Menu
		$menuId = $this->maniaControl->configurator->getMenuId($this->getTitle());
		$this->maniaControl->configurator->reopenMenu($player, $menuId);
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

		$actionArray = explode(".", $actionId);
		$setting     = $actionArray[2];

		$login  = $callback[1][1];
		$player = $this->maniaControl->playerManager->getPlayer($login);

		// Toggle the Boolean Setting
		$this->toggleBooleanSetting($setting, $player);

		// Save all Changes
		$this->saveConfigData($callback[1], $player);
	}


	/**
	 * Toggles a Boolean Value
	 *
	 * @param        $setting
	 * @param Player $player
	 */
	public function toggleBooleanSetting($setting, Player $player) {
		if (!$this->maniaControl->authenticationManager->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MC_SETTINGS)) {
			$this->maniaControl->authenticationManager->sendNotAllowed($player);
			return;
		}

		$oldSetting = $this->maniaControl->settingManager->getSettingByIndex($setting);

		if (!isset($oldSetting)) {
			var_dump('no setting ' . $setting);
			return;
		}

		//Toggle value
		if ($oldSetting->value == "1") {
			$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, "0");
		} else {
			$this->maniaControl->settingManager->setSetting($oldSetting->class, $oldSetting->setting, "1");
		}
	}

}