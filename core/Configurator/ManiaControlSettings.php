<?php

namespace ManiaControl\Configurator;

use FML\Components\CheckBox;
use FML\Components\ValuePicker;
use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Settings\Setting;

/**
 * Class offering a Configurator for ManiaControl Settings
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaControlSettings implements ConfiguratorMenu, CallbackListener {
	/*
	 * Constants
	 */
	const TITLE                                 = 'ManiaControl Settings';
	const ACTION_PREFIX_SETTING                 = 'MCSetting.';
	const ACTION_PREFIX_SETTINGCLASS            = 'MCSettingClass.';
	const ACTION_SETTINGCLASS_BACK              = 'MCSettingClassBack';
	const SETTING_PERMISSION_CHANGE_MC_SETTINGS = 'Change ManiaControl Settings';
	const CACHE_CLASS_OPENED                    = 'ClassOpened';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new ManiaControl Settings instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');

		// Permissions
		$this->maniaControl->getAuthenticationManager()->definePermissionLevel(self::SETTING_PERMISSION_CHANGE_MC_SETTINGS, AuthenticationManager::AUTH_LEVEL_ADMIN);
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getTitle()
	 */
	public static function getTitle() {
		return self::TITLE;
	}

	/**
	 * @see \ManiaControl\Configurator\ConfiguratorMenu::getMenu()
	 */
	public function getMenu($width, $height, Script $script, Player $player) {
		$openedClass = $player->getCache($this, self::CACHE_CLASS_OPENED);
		if ($openedClass) {
			return $this->getMenuSettingsForClass($openedClass, $width, $height, $script, $player);
		}
		return $this->getMenuSettingClasses($width, $height, $script, $player);
	}

	/**
	 * Get the Menu showing the Settings for the given Class
	 *
	 * @param string $settingClass
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	private function getMenuSettingsForClass($settingClass, $width, $height, Script $script, Player $player) {
		$settings = $this->maniaControl->getSettingManager()->getSettingsByClass($settingClass);

		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$labelTextSize = 2;
		$pageMaxCount  = 10;

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
		$pageCountLabel->setPosition($width * 0.35, $height * -0.44);
		$pageCountLabel->setStyle($pageCountLabel::STYLE_TextTitle1);
		$pageCountLabel->setTextSize(2);

		$paging->setLabel($pageCountLabel);

		$backLabel = new Label_Button();
		$frame->addChild($backLabel);
		$backLabel->setStyle($backLabel::STYLE_CardMain_Quit);
		$backLabel->setPosition(-$width / 2 + 5, -$height / 2 + 5);
		$backLabel->setHorizontalAlign($backLabel::LEFT);
		$backLabel->setScale(0.5);
		$backLabel->setTextSize(2);
		$backLabel->setText('Back');
		$backLabel->setAction(self::ACTION_SETTINGCLASS_BACK);

		$headLabel = new Label_Text();
		$frame->addChild($headLabel);
		$headLabel->setHorizontalAlign($headLabel::LEFT);
		$headLabel->setPosition($width * -0.46, $height * 0.41);
		$headLabel->setSize($width * 0.6, $settingHeight);
		$headLabel->setStyle($headLabel::STYLE_TextCardSmall);
		$headLabel->setTextSize(3);
		$headLabel->setText($settingClass);
		$headLabel->setTextColor('ff0');

		$pageFrame = null;
		$index     = 0;
		$posY      = 0;
		foreach ($settings as $setting) {
			if ($index % $pageMaxCount === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$paging->addPageControl($pageFrame);
				$posY = $height * 0.41 - $settingHeight * 1.5;
			}

			$settingFrame = new Frame();
			$pageFrame->addChild($settingFrame);
			$settingFrame->setY($posY);

			$nameLabel = new Label_Text();
			$settingFrame->addChild($nameLabel);
			$nameLabel->setHorizontalAlign($nameLabel::LEFT);
			$nameLabel->setX($width * -0.46);
			$nameLabel->setSize($width * 0.6, $settingHeight);
			$nameLabel->setStyle($nameLabel::STYLE_TextCardSmall);
			$nameLabel->setTextSize($labelTextSize);
			$nameLabel->setText($setting->setting);
			$nameLabel->setTextColor('fff');

			$descriptionLabel = new Label_Text();
			$pageFrame->addChild($descriptionLabel);
			$descriptionLabel->setHorizontalAlign($descriptionLabel::LEFT);
			$descriptionLabel->setPosition(-0.45 * $width, -0.35 * $height);
			$descriptionLabel->setSize(0.9 * $width, $settingHeight);
			$descriptionLabel->setTextSize($labelTextSize);
			$descriptionLabel->setTranslate(true);
			$nameLabel->addTooltipLabelFeature($descriptionLabel, $setting->description);

			$settingName = self::ACTION_PREFIX_SETTING . $setting->index;
			if ($setting->type === Setting::TYPE_BOOL) {
				// Boolean checkbox
				$quad = new Quad();
				$quad->setPosition($width * 0.33, 0, -0.01);
				$quad->setSize(4, 4);
				$checkBox = new CheckBox($settingName, $setting->value, $quad);
				$settingFrame->addChild($checkBox);
			} else if ($setting->type === Setting::TYPE_SET) {
				// SET value picker
				$label = new Label_Text();
				$label->setX($width * 0.33);
				$label->setSize($width * 0.3, $settingHeight * 0.9);
				$label->setStyle($label::STYLE_TextValueSmall);
				$label->setTextSize(1);
				$valuePicker = new ValuePicker($settingName, $setting->set, $setting->value, $label);
				$settingFrame->addChild($valuePicker);
			} else {
				// Standard entry
				$entry = new Entry();
				$settingFrame->addChild($entry);
				$entry->setX($width * 0.33);
				$entry->setSize($width * 0.3, $settingHeight * 0.9);
				$entry->setStyle(Label_Text::STYLE_TextValueSmall);
				$entry->setTextSize(1);
				$entry->setName($settingName);
				$entry->setDefault($setting->value);
			}

			$posY -= $settingHeight;
			$index++;
		}

		return $frame;
	}

	/**
	 * Get the Menu showing all possible Classes
	 *
	 * @param float  $width
	 * @param float  $height
	 * @param Script $script
	 * @param Player $player
	 * @return \FML\Controls\Frame
	 */
	private function getMenuSettingClasses($width, $height, Script $script, Player $player) {
		$settingClasses = $this->maniaControl->getSettingManager()->getSettingClasses(true);

		$paging = new Paging();
		$script->addFeature($paging);
		$frame = new Frame();

		// Config
		$pagerSize     = 9.;
		$settingHeight = 5.;
		$pageMaxCount  = 13;
		$posY          = 0;

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

		$pageFrame = null;
		$index     = 0;
		foreach ($settingClasses as $settingClass) {
			if ($index % $pageMaxCount === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height * 0.41;
				$paging->addPageControl($pageFrame);
			}

			$classLabel = new Label_Text();

			$settingClassArray = explode('\\', $settingClass);
			$className         = '';
			for ($i = 1; $i < count($settingClassArray); $i++) {
				$className .= $settingClassArray[$i] . ' - ';
			}
			$className = substr($className, 0, -3);

			$pageFrame->addChild($classLabel);
			$classLabel->setHorizontalAlign($classLabel::LEFT);
			$classLabel->setPosition($width * -0.45, $posY);
			$classLabel->setSize($width * 0.9, $settingHeight * 0.9);
			$classLabel->setStyle($classLabel::STYLE_TextCardSmall);
			$classLabel->setTextSize(2);
			$classLabel->setText($className);
			$classLabel->setTextColor('fff');
			$classLabel->setAction(self::ACTION_PREFIX_SETTINGCLASS . $settingClass);

			$posY -= $settingHeight;
			$index++;
		}

		return $frame;
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId = $callback[1][2];
		if ($actionId === self::ACTION_SETTINGCLASS_BACK) {
			// Back to classes list
			$login  = $callback[1][1];
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			$player->destroyCache($this, self::CACHE_CLASS_OPENED);
			$menuId = $this->maniaControl->getConfigurator()->getMenuId($this);
			$this->maniaControl->getConfigurator()->showMenu($player, $menuId);
		} else if (strpos($actionId, self::ACTION_PREFIX_SETTINGCLASS) === 0) {
			// Setting class selected
			$settingClass = substr($actionId, strlen(self::ACTION_PREFIX_SETTINGCLASS));

			$login  = $callback[1][1];
			$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
			$player->setCache($this, self::CACHE_CLASS_OPENED, $settingClass);

			$menuId = $this->maniaControl->getConfigurator()->getMenuId($this);
			$this->maniaControl->getConfigurator()->showMenu($player, $menuId);
		}
	}

	/**
	 * @see \ManiaControl\Configurators\ConfiguratorMenu::saveConfigData()
	 */
	public function saveConfigData(array $configData, Player $player) {
		if (!$this->maniaControl->getAuthenticationManager()->checkPermission($player, self::SETTING_PERMISSION_CHANGE_MC_SETTINGS)
		) {
			$this->maniaControl->getAuthenticationManager()->sendNotAllowed($player);
			return;
		}
		if (!$configData[3] || strpos($configData[3][0]['Name'], self::ACTION_PREFIX_SETTING) !== 0) {
			return;
		}

		$prefixLength = strlen(self::ACTION_PREFIX_SETTING);

		foreach ($configData[3] as $settingData) {
			$settingIndex  = (int)substr($settingData['Name'], $prefixLength);
			$settingObject = $this->maniaControl->getSettingManager()->getSettingObjectByIndex($settingIndex);
			if (!$settingObject) {
				continue;
			}

			if (!$settingData || $settingData['Value'] == $settingObject->value) {
				continue;
			}

			$settingObject->value = $settingData['Value'];
			$this->maniaControl->getSettingManager()->saveSetting($settingObject);
		}

		$this->maniaControl->getChat()->sendSuccess('Settings saved!', $player);

		// Reopen the Menu
		$this->maniaControl->getConfigurator()->showMenu($player, $this);
	}
}
