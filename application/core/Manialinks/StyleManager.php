<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Bgs1InRace;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Features\Paging;
use FML\Script\Script;
use ManiaControl\ManiaControl;

/**
 * Class managing default Control Styles
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class StyleManager {
	/*
	 * Constants
	 */
	const SETTING_LABEL_DEFAULT_STYLE   = 'Default Label Style';
	const SETTING_QUAD_DEFAULT_STYLE    = 'Default Quad Style';
	const SETTING_QUAD_DEFAULT_SUBSTYLE = 'Default Quad SubStyle';

	const SETTING_MAIN_WIDGET_DEFAULT_STYLE    = 'Main Widget Default Quad Style';
	const SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE = 'Main Widget Default Quad SubStyle';
	const SETTING_LIST_WIDGETS_WIDTH           = 'List Widgets Width';
	const SETTING_LIST_WIDGETS_HEIGHT          = 'List Widgets Height';

	const SETTING_ICON_DEFAULT_OFFSET_SM = 'Default Icon Offset in ShootMania';

	/*
	 * Private properties
	 */
	private $maniaControl = null;

	/**
	 * Create a new style manager instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LABEL_DEFAULT_STYLE, Label_Text::STYLE_TextTitle1);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_QUAD_DEFAULT_STYLE, Quad_Bgs1InRace::STYLE);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_QUAD_DEFAULT_SUBSTYLE, Quad_Bgs1InRace::SUBSTYLE_BgTitleShadow);

		// Main Widget
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_STYLE, Quad_BgRaceScore2::STYLE);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE, Quad_BgRaceScore2::SUBSTYLE_HandleSelectable);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LIST_WIDGETS_WIDTH, 150.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LIST_WIDGETS_HEIGHT, 80.);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_ICON_DEFAULT_OFFSET_SM, 20.);
	}

	/**
	 * Get the default Icon Offset for shootmania
	 *
	 * @return float
	 */
	public function getDefaultIconOffsetSM() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_ICON_DEFAULT_OFFSET_SM);
	}

	/**
	 * Get the default label style
	 *
	 * @return string
	 */
	public function getDefaultLabelStyle() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_LABEL_DEFAULT_STYLE);
	}

	/**
	 * Get the default quad style
	 *
	 * @return string
	 */
	public function getDefaultQuadStyle() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_QUAD_DEFAULT_STYLE);
	}

	/**
	 * Get the default quad substyle
	 *
	 * @return string
	 */
	public function getDefaultQuadSubstyle() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_QUAD_DEFAULT_SUBSTYLE);
	}

	/**
	 * Gets the Default Description Label
	 *
	 * @return \FML\Controls\Label
	 */
	public function getDefaultDescriptionLabel() {
		$width  = $this->getListWidgetsWidth();
		$height = $this->getListWidgetsHeight();

		// Predefine Description Label
		$descriptionLabel = new Label();
		$descriptionLabel->setAlign($descriptionLabel::LEFT, $descriptionLabel::TOP)
		                 ->setPosition($width * -0.5 + 10, $height * -0.5 + 5)
		                 ->setSize($width * 0.7, 4)
		                 ->setTextSize(2)
		                 ->setVisible(false);

		return $descriptionLabel;
	}

	/**
	 * Get the Default List Widgets Width
	 *
	 * @return float
	 */
	public function getListWidgetsWidth() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_LIST_WIDGETS_WIDTH);
	}

	/**
	 * Get the default list widget height
	 *
	 * @return float
	 */
	public function getListWidgetsHeight() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_LIST_WIDGETS_HEIGHT);
	}

	/**
	 * Builds the Default List Frame
	 *
	 * @param mixed $script
	 * @param mixed $paging
	 * @return \FML\Controls\Frame
	 */
	public function getDefaultListFrame($script = null, $paging = null) {
		$args   = func_get_args();
		$script = null;
		$paging = null;
		foreach ($args as $arg) {
			if ($arg instanceof Script) {
				$script = $arg;
			}
			if ($arg instanceof Paging) {
				$paging = $arg;
			}
		}

		$width        = $this->getListWidgetsWidth();
		$height       = $this->getListWidgetsHeight();
		$quadStyle    = $this->getDefaultMainWindowStyle();
		$quadSubstyle = $this->getDefaultMainWindowSubStyle();

		// mainframe
		$frame = new Frame();
		$frame->setSize($width, $height)
		      ->setZ(35); //TODO place before scoreboards

		// Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setZ(-2)
		               ->setSize($width, $height)
		               ->setStyles($quadStyle, $quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3)
		          ->setSize(6, 6)
		          ->setSubStyle($closeQuad::SUBSTYLE_QuitRace)
		          ->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		if ($script) {
			$pagerSize = 6.;
			$pagerPrev = new Quad_Icons64x64_1();
			$frame->add($pagerPrev);
			$pagerPrev->setPosition($width * 0.42, $height * -0.44, 2)
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
			               ->setPosition($width * 0.40, $height * -0.44, 1)
			               ->setStyle($pageCountLabel::STYLE_TextTitle1)
			               ->setTextSize(1.3);

			if ($paging) {
				$paging->addButton($pagerNext)
				       ->addButton($pagerPrev)
				       ->setLabel($pageCountLabel);
			}
		}

		return $frame;
	}

	/**
	 * Get the default main window style
	 *
	 * @return string
	 */
	public function getDefaultMainWindowStyle() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MAIN_WIDGET_DEFAULT_STYLE);
	}

	/**
	 * Get the default main window substyle
	 *
	 * @return string
	 */
	public function getDefaultMainWindowSubStyle() {
		return $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE);
	}
}
