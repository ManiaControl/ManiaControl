<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Quads\Quad_BgRaceScore2;
use ManiaControl\ManiaControl;

/**
 * Class managing default control styles
 *
 * @author steeffeen & kremsy
 */
class StyleManager {
	/**
	 * Constants
	 */
	const SETTING_LABEL_DEFAULT_STYLE = 'Default Label Style';
	const SETTING_QUAD_DEFAULT_STYLE = 'Default Quad Style';
	const SETTING_QUAD_DEFAULT_SUBSTYLE = 'Default Quad SubStyle';

	const SETTING_MAIN_WIDGET_DEFAULT_STYLE = 'Main Widget Default Quad Style';
	const SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE = 'Main Widget Default Quad SubStyle';
	const SETTING_LIST_WIDGETS_WIDTH = 'List Widgets Width';
	const SETTING_LIST_WIDGETS_HEIGHT = 'List Widgets Height';
	/**
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
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LABEL_DEFAULT_STYLE, 'TextTitle1');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_QUAD_DEFAULT_STYLE, 'Bgs1InRace');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_QUAD_DEFAULT_SUBSTYLE, 'BgTitleShadow');

		//Main Widget
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_STYLE, Quad_BgRaceScore2::STYLE);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE, Quad_BgRaceScore2::SUBSTYLE_HandleSelectable);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LIST_WIDGETS_WIDTH, '150');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LIST_WIDGETS_HEIGHT, '80');
	}

	/**
	 * Get the default label style
	 *
	 * @return string
	 */
	public function getDefaultLabelStyle() {
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_LABEL_DEFAULT_STYLE);
	}

	/**
	 * Get the default quad style
	 *
	 * @return string
	 */
	public function getDefaultQuadStyle() {
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_QUAD_DEFAULT_STYLE);
	}

	/**
	 * Get the default quad substyle
	 *
	 * @return string
	 */
	public function getDefaultQuadSubstyle() {
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_QUAD_DEFAULT_SUBSTYLE);
	}

	/**
	 * Get the default main window style
	 *
	 * @return string
	 */
	public function getDefaultMainWindowStyle(){
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_STYLE);
	}

	/**
	 * Get the default main window substyle
	 *
	 * @return string
	 */
	public function getDefaultMainWindowSubStyle(){
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAIN_WIDGET_DEFAULT_SUBSTYLE);
	}

	/**
	 * Get the default list widget width
	 *
	 * @return string
	 */
	public function getListWidgetsWidth(){
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_LIST_WIDGETS_WIDTH);
	}

	/**
	 * Get the default list widget height
	 *
	 * @return string
	 */
	public function getListWidgetsHeight(){
		return $this->maniaControl->settingManager->getSetting($this, self::SETTING_LIST_WIDGETS_HEIGHT);
	}

}
