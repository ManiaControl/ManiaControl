<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use ManiaControl\General\UsageInformationAble;
use ManiaControl\General\UsageInformationTrait;

class LabelLine implements UsageInformationAble {
	use UsageInformationTrait;

	private $frame;
	private $entries = array();

	private $horizontalAlign = Label::LEFT;
	private $style           = Label_Text::STYLE_TextCardSmall;
	private $textSize        = 1.5;
	private $textColor       = 'FFF';
	private $posZ            = 0;


	public function __construct(Frame $frame) {
		$this->frame = $frame;
	}

	/**
	 * Create a new text label
	 *
	 * @param     $labelText
	 * @param     $posX
	 * @param int $width
	 */
	public function addLabelEntryText($labelText, $posX, $width = 0) {
		$label = new Label_Text();
		$label->setText($labelText);
		$label->setX($posX);
		if ($width) {
			$label->setWidth($width);
		}
		$this->addLabel($label);
	}

	/**
	 * Adds a label to private attribute
	 *
	 * @param \FML\Controls\Labels\Label_Text $label
	 */
	private function addLabel(Label_Text $label) {
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
	public function getPosZ() {
		return $this->posZ;
	}

	/**
	 * @param int $posZ
	 */
	public function setPosZ($posZ) {
		$this->posZ = $posZ;
	}

	/**
	 * @return \FML\Controls\Labels\Label_Text[]
	 */
	public function getEntries() {
		return $this->entries;
	}
}