<?php

namespace FML\Script\Features;

use FML\Controls\Entry;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature for submitting an Entry
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class EntrySubmit extends ScriptFeature {
	/*
	 * Protected properties
	 */
	/** @var Entry $entry */
	protected $entry = null;
	protected $url = null;

	/**
	 * Construct a new Entry Submit Feature
	 *
	 * @param Entry  $entry (optional) Entry Control
	 * @param string $url   (optional) Submit url
	 */
	public function __construct(Entry $entry = null, $url = null) {
		if (!is_null($entry)) {
			$this->setEntry($entry);
		}
		$this->setUrl($url);
	}

	/**
	 * Set the Entry
	 *
	 * @param Entry $entry Entry Control
	 * @return \FML\Script\Features\EntrySubmit|static
	 */
	public function setEntry(Entry $entry) {
		$this->entry = $entry->checkId()->setScriptEvents(true);
		return $this;
	}

	/**
	 * Set the submit url
	 *
	 * @param string $url Submit url
	 * @return \FML\Script\Features\EntrySubmit|static
	 */
	public function setUrl($url) {
		$this->url = (string)$url;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		$script->setScriptInclude(ScriptInclude::TEXTLIB);
		$controlScript = new ControlScript($this->entry, $this->getScriptText(), ScriptLabel::ENTRYSUBMIT);
		$controlScript->prepare($script);
		return $this;
	}

	/**
	 * Get the script text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$url       = $this->buildCompatibleUrl();
		$entryName = $this->entry->getName();
		$link      = Builder::escapeText($entryName . $url . '=', true);
		return "
declare Value = TextLib::URLEncode(Entry.Value);
OpenLink({$link}^Value, CMlScript::LinkType::Goto);
";
	}

	/**
	 * Build the submit url compatible for the Entry parameter
	 *
	 * @return string
	 */
	protected function buildCompatibleUrl() {
		$url         = $this->url;
		$paramsBegin = stripos($url, '?');
		if (!is_int($paramsBegin) || $paramsBegin < 0) {
			$url .= '?';
		} else {
			$url .= '&';
		}
		return $url;
	}
}
