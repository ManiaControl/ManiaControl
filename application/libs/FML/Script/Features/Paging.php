<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature realising a mechanism for browsing through Pages
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Paging extends ScriptFeature {
	/*
	 * Constants
	 */
	const VAR_CURRENT_PAGE             = 'FML_Paging_CurrentPage';
	const FUNCTION_UPDATE_CURRENT_PAGE = 'FML_UpdateCurrentPage';

	/*
	 * Protected properties
	 */
	/** @var PagingPage[] $pages */
	protected $pages = array();
	/** @var PagingButton[] $buttons */
	protected $buttons = array();
	/** @var Label $label */
	protected $label = null;
	protected $startPageNumber = null;
	protected $customMaxPageNumber = null;
	protected $previousChunkAction = null;
	protected $nextChunkAction = null;
	protected $chunkActionAppendsPageNumber = null;

	/**
	 * Construct a new Paging Script Feature
	 *
	 * @param Label $label (optional) Page number Label
	 */
	public function __construct(Label $label = null) {
		if (!is_null($label)) {
			$this->setLabel($label);
		}
	}

	/**
	 * Add a new Page Control
	 *
	 * @param Control $pageControl Page Control
	 * @param string  $pageNumber  (optional) Page number
	 * @return static
	 */
	public function addPage(Control $pageControl, $pageNumber = null) {
		if (is_null($pageNumber)) {
			$pageNumber = count($this->pages) + 1;
		}
		$page = new PagingPage($pageControl, $pageNumber);
		$this->appendPage($page);
		return $this;
	}

	/**
	 * Append a Page
	 *
	 * @param PagingPage $page Paging Page
	 * @return static
	 */
	public function appendPage(PagingPage $page) {
		if (!in_array($page, $this->pages, true)) {
			array_push($this->pages, $page);
		}
		return $this;
	}

	/**
	 * Add a new Button to browse through the Pages
	 *
	 * @param Control $buttonControl Button used for browsing
	 * @param int     $browseAction  (optional) Number of browsed Pages per click
	 * @return static
	 */
	public function addButton(Control $buttonControl, $browseAction = null) {
		if (is_null($browseAction)) {
			$buttonCount = count($this->buttons);
			if ($buttonCount % 2 === 0) {
				$browseAction = $buttonCount / 2 + 1;
			} else {
				$browseAction = $buttonCount / -2 - 1;
			}
		}
		$button = new PagingButton($buttonControl, $browseAction);
		$this->appendButton($button);
		return $this;
	}

	/**
	 * Append a Button to browse through Pages
	 *
	 * @param PagingButton $button Paging Button
	 * @return static
	 */
	public function appendButton(PagingButton $button) {
		if (!in_array($button, $this->buttons, true)) {
			array_push($this->buttons, $button);
		}
		return $this;
	}

	/**
	 * Set the Label showing the Page number
	 *
	 * @param Label $label Page number Label
	 * @return static
	 */
	public function setLabel(Label $label) {
		$this->label = $label->checkId();
		return $this;
	}

	/**
	 * Set the Start Page number
	 *
	 * @param int $startPageNumber Page number to start with
	 * @return static
	 */
	public function setStartPageNumber($startPageNumber) {
		$this->startPageNumber = (int)$startPageNumber;
	}

	/**
	 * Set a custom maximum Page number for using chunks
	 *
	 * @param int $maxPageNumber Custom maximum Page number
	 * @return static
	 */
	public function setCustomMaxPageNumber($maxPageNumber) {
		$this->customMaxPageNumber = (int)$maxPageNumber;
		return $this;
	}

	/**
	 * Set the action triggered when the previous chunk is needed
	 *
	 * @param string $previousChunkAction Triggered action
	 * @return static
	 */
	public function setPreviousChunkAction($previousChunkAction) {
		$this->previousChunkAction = (string)$previousChunkAction;
		return $this;
	}

	/**
	 * Set the action triggered when the next chunk is needed
	 *
	 * @param string $nextChunkAction Triggered action
	 * @return static
	 */
	public function setNextChunkAction($nextChunkAction) {
		$this->nextChunkAction = (string)$nextChunkAction;
		return $this;
	}

	/**
	 * Set the actions triggered when another chunk is needed
	 *
	 * @param string $chunkAction Triggered action
	 * @return static
	 */
	public function setChunkActions($chunkAction) {
		$this->setNextChunkAction($chunkAction);
		$this->setPreviousChunkAction($chunkAction);
		return $this;
	}

	/**
	 * Set if the chunk action should get the needed Page number appended
	 *
	 * @param bool $appendPageNumber Whether to append the needed Page number
	 * @return static
	 */
	public function setChunkActionAppendsPageNumber($appendPageNumber) {
		$this->chunkActionAppendsPageNumber = (bool)$appendPageNumber;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		if (empty($this->pages)) {
			return $this;
		}
		$script->setScriptInclude(ScriptInclude::TEXTLIB);

		$currentPageVariable = self::VAR_CURRENT_PAGE;
		$updatePageFunction  = self::FUNCTION_UPDATE_CURRENT_PAGE;

		$minPageNumber   = 1;
		$startPageNumber = (is_int($this->startPageNumber) ? $this->startPageNumber : $minPageNumber);
		$maxPage         = $this->getMaxPage();
		$maxPageNumber   = $this->customMaxPageNumber;
		if (!is_int($maxPageNumber)) {
			$maxPageNumber = $maxPage->getPageNumber();
		}

		$pagingId    = $maxPage->getControl()->getId(true, true);
		$pageLabelId = '""';
		if ($this->label) {
			$pageLabelId = $this->label->getId(true, true);
		}
		$pagesArrayText       = $this->getPagesArrayText();
		$pageButtonsArrayText = $this->getPageButtonsArrayText();

		$previousChunkAction          = Builder::escapeText($this->previousChunkAction, true);
		$nextChunkAction              = Builder::escapeText($this->nextChunkAction, true);
		$chunkActionAppendsPageNumber = Builder::getBoolean($this->chunkActionAppendsPageNumber);

		// Init
		$initScriptText = "
declare {$currentPageVariable} for This = Integer[Text];
{$currentPageVariable}[{$pagingId}] = {$startPageNumber};
{$updatePageFunction}({$pagingId}, {$pageLabelId}, 0, {$minPageNumber}, {$maxPageNumber}, {$pagesArrayText}, {$previousChunkAction}, {$nextChunkAction}, {$chunkActionAppendsPageNumber});";
		$script->appendGenericScriptLabel(ScriptLabel::ONINIT, $initScriptText, true);

		// MouseClick
		$clickScriptText = "
declare PageButtons = {$pageButtonsArrayText};
if (PageButtons.existskey(Event.Control.ControlId)) {
	declare BrowseAction = PageButtons[Event.Control.ControlId];
	{$updatePageFunction}({$pagingId}, {$pageLabelId}, BrowseAction, {$minPageNumber}, {$maxPageNumber}, {$pagesArrayText}, {$previousChunkAction}, {$nextChunkAction}, {$chunkActionAppendsPageNumber});
}";
		$script->appendGenericScriptLabel(ScriptLabel::MOUSECLICK, $clickScriptText, true);

		// Update function
		$functionText = "
Void {$updatePageFunction}(Text _PagingId, Text _PageLabelId, Integer _BrowseAction, Integer _MinPageNumber, Integer _MaxPageNumber, Text[Integer] _Pages, Text _PreviousChunkAction, Text _NextChunkAction, Boolean _ChunkActionAppendPageNumber) {
	declare {$currentPageVariable} for This = Integer[Text];
	if (!{$currentPageVariable}.existskey(_PagingId)) return;
	declare CurrentPage = {$currentPageVariable}[_PagingId] + _BrowseAction;
	if (CurrentPage < _MinPageNumber) {
		CurrentPage = _MinPageNumber;
	} else if (CurrentPage > _MaxPageNumber) {
		CurrentPage = _MaxPageNumber;
	}
	{$currentPageVariable}[_PagingId] = CurrentPage;
	declare PageFound = False;
	foreach (PageNumber => PageId in _Pages) {
		declare PageControl <=> Page.GetFirstChild(PageId);
		PageControl.Visible = (CurrentPage == PageNumber);
		if (PageControl.Visible) {
			PageFound = True;
		}
	}
	if (!PageFound && _BrowseAction != 0) {
		declare Text ChunkAction;
		if (_BrowseAction < 0) {
			ChunkAction = _PreviousChunkAction;
		} else {
			ChunkAction = _NextChunkAction;
		}
		if (_ChunkActionAppendPageNumber) {
			ChunkAction ^= CurrentPage;
		}
		TriggerPageAction(ChunkAction);
	}
	if (_PageLabelId == " . Builder::EMPTY_STRING . ") return;
	declare PageLabel <=> (Page.GetFirstChild(_PageLabelId) as CMlLabel);
	if (PageLabel == Null) return;
	PageLabel.Value = CurrentPage^\"/\"^_MaxPageNumber;
}";
		$script->addScriptFunction($updatePageFunction, $functionText);
		return $this;
	}

	/**
	 * Get the minimum Page
	 *
	 * @return \FML\Script\Features\PagingPage
	 */
	protected function getMinPage() {
		$minPageNumber = null;
		$minPage       = null;
		foreach ($this->pages as $page) {
			$pageNumber = $page->getPageNumber();
			if (is_null($minPageNumber) || $pageNumber < $minPageNumber) {
				$minPageNumber = $pageNumber;
				$minPage       = $page;
			}
		}
		return $minPage;
	}

	/**
	 * Get the maximum Page
	 *
	 * @return \FML\Script\Features\PagingPage
	 */
	protected function getMaxPage() {
		$maxPageNumber = null;
		$maxPage       = null;
		foreach ($this->pages as $page) {
			$pageNumber = $page->getPageNumber();
			if (is_null($maxPageNumber) || $pageNumber > $maxPageNumber) {
				$maxPageNumber = $pageNumber;
				$maxPage       = $page;
			}
		}
		return $maxPage;
	}

	/**
	 * Build the array text for the Pages
	 *
	 * @return string
	 */
	protected function getPagesArrayText() {
		if (empty($this->pages)) {
			return Builder::getArray(array(0 => ''), true);
		}
		$pages = array();
		foreach ($this->pages as $page) {
			$pages[$page->getPageNumber()] = $page->getControl()->getId();
		}
		return Builder::getArray($pages, true);
	}

	/**
	 * Build the array text for the Page Buttons
	 *
	 * @return string
	 */
	protected function getPageButtonsArrayText() {
		if (empty($this->buttons)) {
			return Builder::getArray(array('' => 0), true);
		}
		$pageButtons = array();
		foreach ($this->buttons as $pageButton) {
			$pageButtons[$pageButton->getControl()->getId()] = $pageButton->getBrowseAction();
		}
		return Builder::getArray($pageButtons, true);
	}
}
