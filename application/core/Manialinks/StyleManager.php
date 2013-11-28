<?php

namespace ManiaControl\Manialinks;

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
}

?>
