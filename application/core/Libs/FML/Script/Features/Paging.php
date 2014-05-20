<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature realising a Mechanism for browsing through Pages
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
	 * Protected Properties
	 */
	protected $pages = array();
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
	 * @param Label $label (optional) Page Number Label
	 */
	public function __construct(Label $label = null) {
		if ($label) {
			$this->setLabel($label);
		}
	}

	/**
	 * Add a new Page Control
	 *
	 * @param Control $pageControl Page Control
	 * @param string  $pageNumber  (optional) Page Number
	 * @return \FML\Script\Features\Paging
	 */
	public function addPage(Control $pageControl, $pageNumber = null) {
		if ($pageNumber === null) {
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
	 * @return \FML\Script\Features\Paging
	 */
	public function appendPage(PagingPage $page) {
		array_push($this->pages, $page);
		return $this;
	}

	/**
	 * Add a new Button to browse through the Pages
	 *
	 * @param Control $buttonControl Button used for Browsing
	 * @param int     $browseAction  (optional) Number of browsed Pages per Click
	 * @return \FML\Script\Features\Paging
	 */
	public function addButton(Control $buttonControl, $browseAction = null) {
		if ($browseAction === null) {
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
	 * @return \FML\Script\Features\Paging
	 */
	public function appendButton(PagingButton $button) {
		array_push($this->buttons, $button);
		return $this;
	}

	/**
	 * Set the Label showing the Page Number
	 *
	 * @param Label $label Page Number Label
	 * @return \FML\Script\Features\Paging
	 */
	public function setLabel(Label $label) {
		$label->checkId();
		$this->label = $label;
		return $this;
	}

	/**
	 * Set the Start Page Number
	 *
	 * @param int $startPageNumber Page Number to start with
	 * @return \FML\Script\Features\Paging
	 */
	public function setStartPageNumber($startPageNumber) {
		$this->startPageNumber = (int)$startPageNumber;
	}

	/**
	 * Set a custom Maximum Page Number for using Chunks
	 *
	 * @param int $maxPageNumber Custom Maximum Page Number
	 * @return \FML\Script\Features\Paging
	 */
	public function setCustomMaxPageNumber($maxPageNumber) {
		$this->customMaxPageNumber = (int)$maxPageNumber;
		return $this;
	}

	/**
	 * Set the Action triggered when the previous Chunk is needed
	 *
	 * @param string $previousChunkAction Triggered Action
	 * @return \FML\Script\Features\Paging
	 */
	public function setPreviousChunkAction($previousChunkAction) {
		$this->previousChunkAction = (string)$previousChunkAction;
		return $this;
	}

	/**
	 * Set the Action triggered when the next Chunk is needed
	 *
	 * @param string $nextChunkAction Triggered Action
	 * @return \FML\Script\Features\Paging
	 */
	public function setNextChunkAction($nextChunkAction) {
		$this->nextChunkAction = (string)$nextChunkAction;
		return $this;
	}

	/**
	 * Set the Actions triggered when another Chunk is needed
	 *
	 * @param string $chunkAction Triggered Action
	 * @return \FML\Script\Features\Paging
	 */
	public function setChunkActions($chunkAction) {
		$this->setNextChunkAction($chunkAction);
		$this->setPreviousChunkAction($chunkAction);
		return $this;
	}

	/**
	 * Set if the Chunk Action should get the needed Page Number appended
	 *
	 * @param bool $appendPageNumber Whether to append the needed Page Number
	 * @return \FML\Script\Features\Paging
	 */
	public function setChunkActionAppendsPageNumber($appendPageNumber) {
		$this->chunkActionAppendsPageNumber = (bool)$appendPageNumber;
		return $this;
	}

	/**
	 * @see \FML\Script\Features\ScriptFeature::prepare()
	 */
	public function prepare(Script $script) {
		if (!$this->pages) {
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

		$pagingId    = $maxPage->getControl()->getId(true);
		$pageLabelId = '';
		if ($this->label) {
			$pageLabelId = $this->label->getId(true);
		}
		$pagesArrayText       = $this->getPagesArrayText();
		$pageButtonsArrayText = $this->getPageButtonsArrayText();

		$previousChunkAction          = Builder::escapeText($this->previousChunkAction);
		$nextChunkAction              = Builder::escapeText($this->nextChunkAction);
		$chunkActionAppendsPageNumber = Builder::getBoolean($this->chunkActionAppendsPageNumber);

		// Init
		$initScriptText = "
declare {$currentPageVariable} for This = Integer[Text];
{$currentPageVariable}[\"{$pagingId}\"] = {$startPageNumber};
{$updatePageFunction}(\"{$pagingId}\", \"{$pageLabelId}\", 0, {$minPageNumber}, {$maxPageNumber}, {$pagesArrayText}, \"{$previousChunkAction}\", \"{$nextChunkAction}\", {$chunkActionAppendsPageNumber});";
		$script->appendGenericScriptLabel(ScriptLabel::ONINIT, $initScriptText, true);

		// MouseClick
		$clickScriptText = "
declare PageButtons = {$pageButtonsArrayText};
if (PageButtons.existskey(Event.Control.ControlId)) {
	declare BrowseAction = PageButtons[Event.Control.ControlId];
	{$updatePageFunction}(\"{$pagingId}\", \"{$pageLabelId}\", BrowseAction, {$minPageNumber}, {$maxPageNumber}, {$pagesArrayText}, \"{$previousChunkAction}\", \"{$nextChunkAction}\", {$chunkActionAppendsPageNumber});
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
	if (_PageLabelId == \"\") return;
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
			/** @var PagingPage $page */
			$pageNumber = $page->getPageNumber();
			if ($minPageNumber === null || $pageNumber < $minPageNumber) {
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
			/** @var PagingPage $page */
			$pageNumber = $page->getPageNumber();
			if ($maxPageNumber === null || $pageNumber > $maxPageNumber) {
				$maxPageNumber = $pageNumber;
				$maxPage       = $page;
			}
		}
		return $maxPage;
	}

	/**
	 * Build the Array Text for the Pages
	 *
	 * @return string
	 */
	protected function getPagesArrayText() {
		$pages = array();
		foreach ($this->pages as $page) {
			/** @var PagingPage $page */
			$pageNumber         = $page->getPageNumber();
			$pages[$pageNumber] = $page->getControl()->getId();
		}
		return Builder::getArray($pages, true);
	}

	/**
	 * Build the Array Text for the Page Buttons
	 *
	 * @return string
	 */
	protected function getPageButtonsArrayText() {
		if (!$this->buttons) {
			return Builder::getArray(array("" => 0), true);
		}
		$pageButtons = array();
		foreach ($this->buttons as $pageButton) {
			/** @var PagingButton $pageButton */
			$pageButtonId               = $pageButton->getControl()->getId();
			$pageButtons[$pageButtonId] = $pageButton->getBrowseAction();
		}
		return Builder::getArray($pageButtons, true);
	}
}
