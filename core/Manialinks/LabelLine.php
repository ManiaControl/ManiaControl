<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

/**
 * Class providing easy labels in a line
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class LabelLine implements UsageInformationAble {
	use UsageInformationTrait;

	private $frame;
	private $entries = array();

	private $horizontalAlign = Label::LEFT;
	private $style           = Label_Text::STYLE_TextCardSmall;
	private $textSize        = 1.5;
	private $textColor       = 'FFF';
	private $posZ            = 0;
	private $prefix          = '';
	private $posY            = 0;


	public function __construct(Frame $frame) {
		$this->frame = $frame;
	}


	/**
	 * Create a new text label
	 *
	 * @param        $labelText
	 * @param        $posX
	 * @param int    $width
	 * @param string $action
	 */
	public function addLabelEntryText($labelText, $posX, $width = 0, $action = '') {
		$label = new Label_Text();
		$label->setText($labelText);
		$label->setX($posX);
		if ($action) {
			$label->setAction($action);
		}

		if ($width) {
			$label->setWidth($width);
			$label->setHeight(0);
		}
		$this->addLabel($label);
	}

	/**
	 * Adds a label to private attribute
	 *
	 * @param \FML\Controls\Labels\Label_Text $label
	 */
	public function addLabel(Label_Text $label) {
		array_push($this->entries, $label);
	}

	/**
	 *  Adds the labels to your frame
	 */

	public function render() {
		/** @var Label $entry */
		foreach ($this->entries as $entry) {
			$entry->setHorizontalAlign($this->horizontalAlign);
			$entry->setStyle($this->style);
			$entry->setTextSize($this->textSize);
			$entry->setTextColor($this->textColor);
			$entry->setZ($this->posZ);
			$entry->setY($this->posY);
			$entry->setTextPrefix($this->prefix);

			$this->frame->addChild($entry);
		}
	}

	/**
	 * @return string
	 */
	public function getHorizontalAlign() {
		return $this->horizontalAlign;
	}

	/**
	 * @param string $horizontalAlign
	 */
	public function setHorizontalAlign($horizontalAlign) {
		$this->horizontalAlign = $horizontalAlign;
	}

	/**
	 * @return string
	 */
	public function getStyle() {
		return $this->style;
	}

	/**
	 * @param string $style
	 */
	public function setStyle($style) {
		$this->style = $style;
	}

	/**
	 * @return float
	 */
	public function getTextSize() {
		return $this->textSize;
	}

	/**
	 * @param float $textSize
	 */
	public function setTextSize($textSize) {
		$this->textSize = $textSize;
	}

	/**
	 * @return string
	 */
	public function getTextColor() {
		return $this->textColor;
	}

	/**
	 * @param string $textColor
	 */
	public function setTextColor($textColor) {
		$this->textColor = $textColor;
	}

	/**
	 * @return int
	 */
	public function getZ() {
		return $this->posZ;
	}

	/**
	 * @param int $posZ
	 */
	public function setZ($posZ) {
		$this->posZ = $posZ;
	}

	/**
	 * @return int
	 */
	public function getY() {
		return $this->posY;
	}

	/**
	 * @param int $posY
	 */
	public function setY($posY) {
		$this->posY = $posY;
	}

	/**
	 * @return \FML\Controls\Labels\Label_Text[]
	 */
	public function getEntries() {
		return $this->entries;
	}

	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @param string $prefix
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}
}