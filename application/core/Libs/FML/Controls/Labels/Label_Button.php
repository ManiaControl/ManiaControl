<?php

namespace FML\Controls\Labels;

use FML\Controls\Label;

/**
 * Label Class for Button Styles
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Label_Button extends Label {
	/*
	 * Constants
	 */
	const STYLE_CardButtonMedium = 'CardButtonMedium';
	const STYLE_CardButtonMediumL = 'CardButtonMediumL';
	const STYLE_CardButtonMediumS = 'CardButtonMediumS';
	const STYLE_CardButtonMediumWide = 'CardButtonMediumWide';
	const STYLE_CardButtonMediumXL = 'CardButtonMediumXL';
	const STYLE_CardButtonMediumXS = 'CardButtonMediumXS';
	const STYLE_CardButtonMediumXXL = 'CardButtonMediumXXL';
	const STYLE_CardButtonMediumXXXL = 'CardButtonMediumXXXL';
	const STYLE_CardButtonSmall = 'CardButtonSmall';
	const STYLE_CardButtonSmallL = 'CardButtonSmallL';
	const STYLE_CardButtonSmallS = 'CardButtonSmallS';
	const STYLE_CardButtonSmallWide = 'CardButtonSmallWide';
	const STYLE_CardButtonSmallXL = 'CardButtonSmallXL';
	const STYLE_CardButtonSmallXS = 'CardButtonSmallXS';
	const STYLE_CardButtonSmallXXL = 'CardButtonSmallXXL';
	const STYLE_CardButtonSmallXXXL = 'CardButtonSmallXXXL';
	const STYLE_CardMain_Quit = 'CardMain_Quit';
	const STYLE_CardMain_Tool = 'CardMain_Tool';

	/**
	 * Create a new Label_Button Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\Labels\Label_Button
	 */
	public static function create($id = null) {
		$labelButton = new Label_Button($id);
		return $labelButton;
	}

	/**
	 * Construct a new Label_Button Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
	}
}
