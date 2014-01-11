<?php

namespace FML\Controls;

use FML\Types\Scriptable;

/**
 * Frame3d Element
 * (CMlFrame)
 *
 * @author steeffeen
 */
class Frame3d extends Frame implements Scriptable {
	/**
	 * Protected Properties
	 */
	protected $style3d = '';
	protected $scriptEvents = 0;

	/**
	 * Construct a new Frame3d Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'frame3d';
	}

	/**
	 * Set style3d
	 *
	 * @param string $style3d 3D Style
	 * @return \FML\Controls\Frame3d
	 */
	public function setStyle3d($style3d) {
		$this->style3d = (string) $style3d;
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
		$xmlElement = parent::render($domDocument);
		if ($this->style3d) {
			$xmlElement->setAttribute('style3d', $this->style3d);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		return $xmlElement;
	}
}
