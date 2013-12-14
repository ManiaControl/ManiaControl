<?php

namespace FML\Script;

use FML\Controls\Control;
use FML\Script\Sections\Constants;
use FML\Script\Sections\Labels;
use FML\Types\Scriptable;

/**
 * ScriptFeature class offering tooltip behaviors
 *
 * @author steeffeen
 */
class Tooltips implements Constants, Labels, ScriptFeature {
	/**
	 * Constants
	 */
	const C_TOOLTIPIDS = 'C_FML_TooltipIds';
	
	/**
	 * Protected properties
	 */
	protected $tooltips = array();

	/**
	 * Add a tooltip behavior showing the tooltipControl while hovering over the hoverControl
	 *
	 * @param Scriptable $hoverControl        	
	 * @param Control $tooltipControl        	
	 * @return \FML\Script\Tooltips
	 */
	public function add(Scriptable $hoverControl, Control $tooltipControl) {
		if ($hoverControl instanceof Control) {
			$hoverControl->assignId();
		}
		$hoverControl->setScriptEvents(true);
		$tooltipControl->assignId();
		$tooltipControl->setVisible(false);
		$this->tooltips[$hoverControl->getId()] = $tooltipControl->getId();
		return $this;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Constants::getConstants()
	 */
	public function getConstants() {
		$constant = '[';
		if (count($this->tooltips) <= 0) {
			$constant .= '"" => ""';
		}
		else {
			$index = 0;
			foreach ($this->tooltips as $hoverId => $tooltipId) {
				$constant .= '"' . $hoverId . '" => "' . $tooltipId . '"';
				if ($index < count($this->tooltips) - 1) {
					$constant .= ',';
				}
				$index++;
			}
		}
		$constant .= ']';
		$constants = array();
		$constants[self::C_TOOLTIPIDS] = $constant;
		return $constants;
	}

	/**
	 *
	 * @see \FML\Script\Sections\Labels::getLabels()
	 */
	public function getLabels() {
		$labels = array();
		$labelMouseOut = file_get_contents(__DIR__ . '/Templates/TooltipMouseOut.txt');
		$labels[Labels::MOUSEOUT] = $labelMouseOut;
		$labelMouseOver = file_get_contents(__DIR__ . '/Templates/TooltipMouseOver.txt');
		$labels[Labels::MOUSEOVER] = $labelMouseOver;
		return $labels;
	}
}
