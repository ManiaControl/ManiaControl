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
	 * Protected Properties
	 */
	/** @var Entry $entry */
	protected $entry = null;
	protected $url = null;

	/**
	 * Construct a new Entry Submit Feature
	 *
	 * @param Entry  $entry (optional) Entry Control
	 * @param string $url   (optional) Submit Url
	 */
	public function __construct(Entry $entry = null, $url = null) {
		$this->setEntry($entry);
		$this->setUrl($url);
	}

	/**
	 * Set the Entry
	 *
	 * @param Entry $entry Entry Control
	 * @return \FML\Script\Features\EntrySubmit
	 */
	public function setEntry(Entry $entry) {
		$entry->checkId();
		$entry->setScriptEvents(true);
		$this->entry = $entry;
		return $this;
	}

	/**
	 * Set the Submit Url
	 *
	 * @param string $url Submit Url
	 * @return \FML\Script\Features\EntrySubmit
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
		$script->appendGenericScriptLabel(ScriptLabel::ENTRYSUBMIT, $this->getScriptText());
		return $this;
	}

	/**
	 * Get the Script Text
	 *
	 * @return string
	 */
	protected function getScriptText() {
		$controlId  = $this->entry->getId(true);
		$url        = $this->buildCompatibleUrl();
		$entryName  = Builder::escapeText($this->entry->getName());
		$scriptText = "
if (Event.Control.ControlId == \"{$controlId}\") {
	declare Entry <=> (Event.Control as CMlEntry);
	declare Value = TextLib::URLEncode(Entry.Value);
	OpenLink(\"{$url}{$entryName}=\"^Value, CMlScript::LinkType::Goto);
}";
		return $scriptText;
	}

	/**
	 * Build the Submit Url compatible for the Entry Parameter
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
