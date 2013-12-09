<?php

namespace FML\Controls;

use FML\Types\Scriptable;

/**
 * Class representing frame3d elements (CMlFrame)
 *
 * @author steeffeen
 */
class Frame3d extends Frame implements Scriptable {
	/**
	 * Protected properties
	 */
	protected $style3d = '';
	protected $scriptEvents = 0;

	/**
	 * Construct a new frame3d control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'frame3d';
	}

	/**
	 * Set style3d
	 *
	 * @param string $style3d        	
	 * @return \FML\Controls\Frame3d
	 */
	public function setStyle3d($style3d) {
		$this->style3d = $style3d;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 * @return \FML\Controls\Frame3d
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Controls\Frame::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		if ($this->style3d) {
			$xml->setAttribute('style3d', $this->style3d);
		}
		if ($this->scriptEvents) {
			$xml->setAttribute('scriptevents', $this->scriptEvents);
		}
		return $xml;
	}
}
