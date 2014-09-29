<?php

namespace FML\Controls;

use FML\Stylesheet\Style3d;
use FML\Types\Scriptable;

/**
 * Frame3d Control
 * (CMlFrame)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Frame3d extends Frame implements Scriptable {
	/*
	 * Constants
	 */
	const STYLE_BaseStation = 'BaseStation';
	const STYLE_BaseBoxCase = 'BaseBoxCase';
	const STYLE_Titlelogo   = 'Titlelogo';
	const STYLE_ButtonBack  = 'ButtonBack';
	const STYLE_ButtonNav   = 'ButtonNav';
	const STYLE_ButtonH     = 'ButtonH';
	const STYLE_Station3x3  = 'Station3x3';
	const STYLE_Title       = 'Title';
	const STYLE_TitleEditor = 'TitleEditor';
	const STYLE_Window      = 'Window';

	/*
	 * Protected properties
	 */
	protected $tagName = 'frame3d';
	protected $style3dId = null;
	/** @var Style3d $style3d */
	protected $style3d = null;
	protected $scriptEvents = null;

	/**
	 * Set Style3d id
	 *
	 * @param string $style3dId Style3d id
	 * @return static
	 */
	public function setStyle3dId($style3dId) {
		$this->style3dId = (string)$style3dId;
		$this->style3d   = null;
		return $this;
	}

	/**
	 * Set Style3d
	 *
	 * @param Style3d $style3d Style3d object
	 * @return static
	 */
	public function setStyle3d(Style3d $style3d) {
		$this->style3d   = $style3d;
		$this->style3dId = null;
		return $this;
	}

	/**
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->style3d) {
			$this->style3d->checkId();
			$xmlElement->setAttribute('style3d', $this->style3d->getId());
		} else if ($this->style3dId) {
			$xmlElement->setAttribute('style3d', $this->style3dId);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		return $xmlElement;
	}
}
