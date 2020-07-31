<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature realizing a mechanism for browsing through Pages
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Paging extends ScriptFeature
{

    /*
     * Constants
     */
    const VAR_CURRENT_PAGE             = "FML_Paging_CurrentPage";
    const FUNCTION_UPDATE_CURRENT_PAGE = "FML_UpdateCurrentPage";

    /**
     * @var Label $label Page number Label
     */
    protected $label = null;

    /**
     * @var PagingPage[] $pages Pages
     */
    protected $pages = array();

    /**
     * @var PagingButton[] $buttons Paging Buttons
     */
    protected $buttons = array();

    /**
     * @var int $startPageNumber Start Page number
     */
    protected $startPageNumber = null;

    /**
     * @var int $customMaxPageNumber Custom maximum page number
     */
    protected $customMaxPageNumber = null;

    /**
     * @var string $previousChunkAction Previous chunk action name
     */
    protected $previousChunkAction = null;

    /**
     * @var string $nextChunkAction Next chunk action name
     */
    protected $nextChunkAction = null;

    /**
     * @var bool $chunkActionAppendsPageNumber Chunk action appended with Page number
     */
    protected $chunkActionAppendsPageNumber = null;

    /**
     * Construct a new Paging
     *
     * @api
     * @param Label          $label   (optional) Page number Label
     * @param PagingPage[]   $pages   (optional) Pages
     * @param PagingButton[] $buttons (optional) Pageing Buttons
     */
    public function __construct(Label $label = null, array $pages = null, array $buttons = null)
    {
        if ($label) {
            $this->setLabel($label);
        }
        if ($pages) {
            $this->setPages($pages);
        }
        if ($buttons) {
            $this->setButtons($buttons);
        }
    }

    /**
     * Get the Label showing the Page number
     *
     * @api
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the Label showing the Page number
     *
     * @api
     * @param Label $label Page number Label
     * @return static
     */
    public function setLabel(Label $label)
    {
        $label->checkId();
        $this->label = $label;
        return $this;
    }

    /**
     * Get the Pages
     *
     * @api
     * @return PagingPage[]
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Add a new Page Control
     *
     * @api
     * @param Control $pageControl Page Control
     * @param string  $pageNumber  (optional) Page number
     * @return static
     */
    public function addPageControl(Control $pageControl, $pageNumber = null)
    {
        if ($pageNumber === null) {
            $pageNumber = count($this->pages) + 1;
        }
        $page = new PagingPage($pageControl, $pageNumber);
        return $this->addPage($page);
    }

    /**
     * Add a new Page
     *
     * @api
     * @param PagingPage $page Page
     * @return static
     */
    public function addPage(PagingPage $page)
    {
        if (!in_array($page, $this->pages, true)) {
            array_push($this->pages, $page);
        }
        return $this;
    }

    /**
     * Add new Pages
     *
     * @api
     * @param PagingPage[] $pages Pages
     * @return static
     */
    public function setPages(array $pages)
    {
        $this->pages = array();
        foreach ($pages as $page) {
            $this->addPage($page);
        }
        return $this;
    }

    /**
     * Get the Buttons
     *
     * @api
     * @return PagingButton[]
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Add a new Button Control to browse through the Pages
     *
     * @api
     * @param Control $buttonControl Button used for browsing
     * @param int     $browseAction  (optional) Number of browsed Pages per click
     * @return static
     */
    public function addButtonControl(Control $buttonControl, $browseAction = null)
    {
        if ($browseAction === null) {
            $buttonCount = count($this->buttons);
            if ($buttonCount % 2 === 0) {
                $browseAction = $buttonCount / 2 + 1;
            } else {
                $browseAction = $buttonCount / -2 - 1;
            }
        }
        $button = new PagingButton($buttonControl, $browseAction);
        return $this->addButton($button);
    }

    /**
     * Add a new Button to browse through Pages
     *
     * @api
     * @param PagingButton $button Paging Button
     * @return static
     */
    public function addButton(PagingButton $button)
    {
        if (!in_array($button, $this->buttons, true)) {
            array_push($this->buttons, $button);
        }
        return $this;
    }

    /**
     * Set the Paging Buttons
     *
     * @api
     * @param PagingButton[] $buttons Paging Buttons
     * @return static
     */
    public function setButtons(array $buttons)
    {
        $this->buttons = array();
        foreach ($buttons as $button) {
            $this->addButton($button);
        }
        return $this;
    }

    /**
     * Get the start Page number
     *
     * @api
     * @return int
     */
    public function getStartPageNumber()
    {
        return $this->startPageNumber;
    }

    /**
     * Set the start Page number
     *
     * @api
     * @param int $startPageNumber Page number to start with
     * @return static
     */
    public function setStartPageNumber($startPageNumber)
    {
        $this->startPageNumber = (int)$startPageNumber;
        return $this;
    }

    /**
     * Get a custom maximum Page number for using chunks
     *
     * @api
     * @return int
     */
    public function getCustomMaxPageNumber()
    {
        return $this->customMaxPageNumber;
    }

    /**
     * Set a custom maximum Page number for using chunks
     *
     * @api
     * @param int $maxPageNumber Custom maximum Page number
     * @return static
     */
    public function setCustomMaxPageNumber($maxPageNumber)
    {
        $this->customMaxPageNumber = (int)$maxPageNumber;
        return $this;
    }

    /**
     * Get the action triggered when the previous chunk is needed
     *
     * @api
     * @return string
     */
    public function getPreviousChunkAction()
    {
        return $this->previousChunkAction;
    }

    /**
     * Set the action triggered when the previous chunk is needed
     *
     * @api
     * @param string $previousChunkAction Triggered action
     * @return static
     */
    public function setPreviousChunkAction($previousChunkAction)
    {
        $this->previousChunkAction = (string)$previousChunkAction;
        return $this;
    }

    /**
     * Get the action triggered when the next chunk is needed
     *
     * @api
     * @return string
     */
    public function getNextChunkAction()
    {
        return $this->nextChunkAction;
    }

    /**
     * Set the action triggered when the next chunk is needed
     *
     * @api
     * @param string $nextChunkAction Triggered action
     * @return static
     */
    public function setNextChunkAction($nextChunkAction)
    {
        $this->nextChunkAction = (string)$nextChunkAction;
        return $this;
    }

    /**
     * Set the actions triggered when another chunk is needed
     *
     * @api
     * @param string $chunkAction Triggered action
     * @return static
     */
    public function setChunkActions($chunkAction)
    {
        return $this->setNextChunkAction($chunkAction)
                    ->setPreviousChunkAction($chunkAction);
    }

    /**
     * Get if the chunk action should append the needed Page number
     *
     * @api
     * @return bool
     */
    public function getChunkActionAppendsPageNumber()
    {
        return $this->chunkActionAppendsPageNumber;
    }

    /**
     * Set if the chunk action should append the needed Page number
     *
     * @api
     * @param bool $appendPageNumber Append the needed Page number
     * @return static
     */
    public function setChunkActionAppendsPageNumber($appendPageNumber)
    {
        $this->chunkActionAppendsPageNumber = (bool)$appendPageNumber;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
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
	    
        $pagingId    = Builder::escapeText($maxPage->getControl()
                                                   ->getId());
        $pageLabelId = Builder::EMPTY_STRING;
        if ($this->label) {
            $pageLabelId = Builder::escapeText($this->label->getId());
        }

        $pagesArrayText       = $this->getPagesArrayText();
        $pageButtonsArrayText = $this->getPageButtonsArrayText();

        $previousChunkAction          = Builder::escapeText($this->previousChunkAction);
        $nextChunkAction              = Builder::escapeText($this->nextChunkAction);
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
	declare NewPageNumber = {$currentPageVariable}[_PagingId] + _BrowseAction;
	if (NewPageNumber < _MinPageNumber) {
		NewPageNumber = _MinPageNumber;
	} else if (NewPageNumber > _MaxPageNumber) {
		NewPageNumber = _MaxPageNumber;
	}
	{$currentPageVariable}[_PagingId] = NewPageNumber;
	if (_Pages.existskey(NewPageNumber)) {
        foreach (PageNumber => PageId in _Pages) {
            declare PageControl <=> Page.GetFirstChild(PageId);
            PageControl.Visible = (PageNumber == NewPageNumber);
        }
        if (_PageLabelId != \"\") {
            declare PageLabel <=> (Page.GetFirstChild(_PageLabelId) as CMlLabel);
            PageLabel.Value = NewPageNumber^\"/\"^_MaxPageNumber;
        }
	} else {
		declare Text ChunkAction;
		if (_BrowseAction < 0) {
			ChunkAction = _PreviousChunkAction;
		} else {
			ChunkAction = _NextChunkAction;
		}
		if (_ChunkActionAppendPageNumber) {
			ChunkAction ^= NewPageNumber;
		}
		TriggerPageAction(ChunkAction);
	}
}";
        $script->addScriptFunction($updatePageFunction, $functionText);
        return $this;
    }

    /**
     * Get the minimum Page
     *
     * @return PagingPage
     */
    protected function getMinPage()
    {
        $minPageNumber = null;
        $minPage       = null;
        foreach ($this->pages as $page) {
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
     * @return PagingPage
     */
    protected function getMaxPage()
    {
        $maxPageNumber = null;
        $maxPage       = null;
        foreach ($this->pages as $page) {
            $pageNumber = $page->getPageNumber();
            if ($maxPageNumber === null || $pageNumber > $maxPageNumber) {
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
    protected function getPagesArrayText()
    {
        if (empty($this->pages)) {
            return Builder::getArray(array(0 => ''), true);
        }
        $pages = array();
        foreach ($this->pages as $page) {
            $pages[$page->getPageNumber()] = $page->getControl()
                                                  ->getId();
        }
        return Builder::getArray($pages, true);
    }

    /**
     * Build the array text for the Page Buttons
     *
     * @return string
     */
    protected function getPageButtonsArrayText()
    {
        if (empty($this->buttons)) {
            return Builder::getArray(array('' => 0), true);
        }
        $pageButtons = array();
        foreach ($this->buttons as $pageButton) {
            $pageButtons[$pageButton->getControl()
                                    ->getId()] = $pageButton->getPagingCount();
        }
        return Builder::getArray($pageButtons, true);
    }

}
