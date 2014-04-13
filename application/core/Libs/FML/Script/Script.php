<?php

namespace FML\Script;

use FML\Controls\Control;
use FML\Controls\Label;
use FML\Types\Scriptable;
use FML\Types\Actionable;

/**
 * Class representing the ManiaLink Script
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Script {
	/*
	 * Constants
	 */
	const CLASS_TOOLTIP = 'FML_Tooltip';
	const CLASS_MENU = 'FML_Menu';
	const CLASS_MENUBUTTON = 'FML_MenuButton';
	const CLASS_PAGE = 'FML_Page';
	const CLASS_PAGER = 'FML_Pager';
	const CLASS_PAGELABEL = 'FML_PageLabel';
	const CLASS_PROFILE = 'FML_Profile';
	const CLASS_MAPINFO = 'FML_MapInfo';
	const CLASS_SOUND = 'FML_Sound';
	const CLASS_TOGGLE = 'FML_Toggle';
	const CLASS_SPECTATE = 'FML_Spectate';
	const CLASS_PAGEACTION = 'FML_PageAction';
	const CLASS_TIME = 'FML_Time';
	const OPTION_TOOLTIP_STAYONCLICK = 'FML_StayOnClick_Tooltip';
	const OPTION_TOOLTIP_INVERT = 'FML_Invert_Tooltip';
	const OPTION_TOOLTIP_TEXT = 'FML_Text_Tooltip';
	const OPTION_TOGGLE_SHOW = 'FML_Show_Toggle';
	const OPTION_TOGGLE_HIDE = 'FML_Hide_Toggle';
	const OPTION_PROFILE_OWN = 'FML_Own_Profile';
	const OPTION_TIME_HIDESECONDS = 'FML_HideSeconds_Time';
	const OPTION_TIME_FULLDATE = 'FML_FullDate_Time';
	const LABEL_ONINIT = 'OnInit';
	const LABEL_LOOP = 'Loop';
	const LABEL_TICK = 'Tick';
	const LABEL_ENTRYSUBMIT = 'EntrySubmit';
	const LABEL_KEYPRESS = 'KeyPress';
	const LABEL_MOUSECLICK = 'MouseClick';
	const LABEL_MOUSEOUT = 'MouseOut';
	const LABEL_MOUSEOVER = 'MouseOver';
	const CONSTANT_TOOLTIPTEXTS = 'C_FML_TooltipTexts';
	const FUNCTION_GETTOOLTIPCONTROLID = 'FML_GetTooltipControlId';
	const FUNCTION_SETTOOLTIPTEXT = 'FML_SetTooltipText';
	const FUNCTION_TOGGLE = 'FML_Toggle';
	
	/*
	 * Protected Properties
	 */
	protected $tagName = 'script';
	protected $includes = array();
	protected $constants = array();
	protected $functions = array();
	protected $tooltips = false;
	protected $tooltipTexts = array();
	protected $menus = false;
	protected $pages = false;
	protected $profile = false;
	protected $mapInfo = false;
	protected $sounds = array();
	protected $toggles = false;
	protected $spectate = false;
	protected $pageActions = false;
	protected $times = false;

	/**
	 * Create a new Script Object
	 *
	 * @return \FML\Script\Script
	 */
	public static function create() {
		$script = new Script();
		return $script;
	}

	/**
	 * Construct a new Script Object
	 */
	public function __construct() {
	}

	/**
	 * Set an Include of the Script
	 *
	 * @param string $namespace Namespace used for the Include
	 * @param string $file Included File Url
	 * @return \FML\Script\Script
	 */
	public function setInclude($namespace, $file) {
		$this->includes[$namespace] = $file;
		return $this;
	}

	/**
	 * Set a Constant of the Script
	 *
	 * @param string $name Variable Name of the Constant
	 * @param string $value Constant Value
	 * @return \FML\Script\Script
	 */
	public function setConstant($name, $value) {
		$this->constants[$name] = $value;
		return $this;
	}

	/**
	 * Set a Function of the Script
	 *
	 * @param string $name Function Name
	 * @param string $coding Complete Function Implementation including Declaration
	 * @return \FML\Script\Script
	 */
	public function setFunction($name, $coding) {
		$this->functions[$name] = $coding;
		return $this;
	}

	/**
	 * Add a Tooltip Behavior
	 *
	 * @param Control $hoverControl The Control that shows the Tooltip
	 * @param Control $tooltipControl The Tooltip to display
	 * @param string $options,... (optional) Unlimited Number of Tooltip Options
	 * @return \FML\Script\Script
	 */
	public function addTooltip(Control $hoverControl, Control $tooltipControl) {
		if (!($hoverControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as HoverControl for Tooltips!');
			return $this;
		}
		$tooltipControl->checkId();
		$tooltipControl->setVisible(false);
		$hoverControl->checkId();
		$hoverControl->setScriptEvents(true);
		$hoverControl->addClass(self::CLASS_TOOLTIP);
		$hoverControl->addClass(self::CLASS_TOOLTIP . '-' . $tooltipControl->getId());
		$options = $this->spliceParameters(func_get_args(), 2);
		foreach ($options as $option => $value) {
			if ($option == self::OPTION_TOOLTIP_TEXT) {
				if (!($tooltipControl instanceof Label)) {
					trigger_error('Label needed for Tooltip Text Option!');
					continue;
				}
				$hoverId = $hoverControl->getId();
				$tooltipId = $tooltipControl->getId();
				if (!isset($this->tooltipTexts[$tooltipId])) {
					$this->tooltipTexts[$tooltipId] = array();
				}
				$this->tooltipTexts[$tooltipId][$hoverId] = $value;
				continue;
			}
			if ($option == self::OPTION_TOOLTIP_INVERT) {
				$tooltipControl->setVisible(true);
			}
			$hoverControl->addClass($option);
		}
		$this->tooltips = true;
		return $this;
	}

	/**
	 * Add a Menu Behavior
	 *
	 * @param Control $clickControl The Control showing the Menu
	 * @param Control $menuControl The Menu to show
	 * @param string $menuId (optional) An identifier to specify the Menu Group
	 * @return \FML\Script\Script
	 */
	public function addMenu(Control $clickControl, Control $menuControl, $menuId = null) {
		if (!($clickControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Menus!');
			return $this;
		}
		if (!$menuId) $menuId = '_';
		$menuControl->checkId();
		$menuControl->addClass(self::CLASS_MENU);
		$menuControl->addClass($menuId);
		$clickControl->setScriptEvents(true);
		$clickControl->addClass(self::CLASS_MENUBUTTON);
		$clickControl->addClass($menuId . '-' . $menuControl->getId());
		$this->menus = true;
		return $this;
	}

	/**
	 * Add a Page for a Paging Behavior
	 *
	 * @param Control $pageControl The Page to display
	 * @param int $pageNumber The Number of the Page
	 * @param string $pagesId (optional) An identifier to specify the Pages Group
	 * @return \FML\Script\Script
	 */
	public function addPage(Control $pageControl, $pageNumber, $pagesId = null) {
		$pageNumber = (int) $pageNumber;
		if (!$pagesId) $pagesId = '_';
		$pageControl->addClass(self::CLASS_PAGE);
		$pageControl->addClass($pagesId);
		$pageControl->addClass(self::CLASS_PAGE . '-P' . $pageNumber);
		return $this;
	}

	/**
	 * Add a Pager Button for a Paging Behavior
	 *
	 * @param Control $pagerControl The Control to leaf through the Pages
	 * @param int $pagingAction The Number of Pages the Pager leafs
	 * @param string $pagesId (optional) An identifier to specify the Pages Group
	 * @return \FML\Script\Script
	 */
	public function addPager(Control $pagerControl, $pagingAction, $pagesId = null) {
		if (!($pagerControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as PagerControl for Pages!');
			return $this;
		}
		$pagingAction = (int) $pagingAction;
		if (!$pagesId) $pagesId = '_';
		$pagerControl->setScriptEvents(true);
		$pagerControl->addClass(self::CLASS_PAGER);
		$pagerControl->addClass(self::CLASS_PAGER . '-I' . $pagesId);
		$pagerControl->addClass(self::CLASS_PAGER . '-A' . $pagingAction);
		$this->pages = true;
		return $this;
	}

	/**
	 * Add a Label that shows the current Page Number
	 *
	 * @param Label $pageLabel The Label showing the Number of the currently displayed Page
	 * @param string $pagesId
	 * @return \FML\Script\Script
	 */
	public function addPageLabel(Label $pageLabel, $pagesId = null) {
		if (!$pagesId) $pagesId = '_';
		$pageLabel->addClass(self::CLASS_PAGELABEL);
		$pageLabel->addClass($pagesId);
		return $this;
	}

	/**
	 * Add a Button Behavior that will open the Built-In Player Profile
	 * (Works only for Server ManiaLinks)
	 *
	 * @param Control $profileControl The Control opening a Profile
	 * @param string $playerLogin The Player Login
	 * @param string $options,... (optional) Unlimited Number of Profile Options
	 * @return \FML\Script\Script
	 */
	public function addProfileButton(Control $profileControl, $playerLogin) {
		if (!($profileControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Profiles!');
			return $this;
		}
		$profileControl->setScriptEvents(true);
		$profileControl->addClass(self::CLASS_PROFILE);
		$playerLogin = (string) $playerLogin;
		$profileControl->addClass(self::CLASS_PROFILE . '-' . $playerLogin);
		$options = $this->spliceParameters(func_get_args(), 2);
		foreach ($options as $option => $value) {
			$profileControl->addClass($option);
		}
		$this->profile = true;
		return $this;
	}

	/**
	 * Add a Button Behavior that will open the Built-In Map Info
	 * (Works only on a Server)
	 *
	 * @param Control $mapInfoControl The Control opening the Map Info
	 * @return \FML\Script\Script
	 */
	public function addMapInfoButton(Control $mapInfoControl) {
		if (!($mapInfoControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Map Info!');
			return $this;
		}
		$mapInfoControl->setScriptEvents(true);
		$mapInfoControl->addClass(self::CLASS_MAPINFO);
		$this->mapInfo = true;
		return $this;
	}

	/**
	 * Add a Sound Playing for the Control
	 * (Works only for Server ManiaLinks)
	 *
	 * @param Control $control The Control playing a Sound
	 * @param string $soundName The Sound to play
	 * @param int $soundVariant (optional) Sound Variant
	 * @param float $soundVolume (optional) Sound Volume
	 * @param string $eventLabel (optional) The Event Label on which the Sound should be played
	 * @return \FML\Script\Script
	 */
	public function addSound(Control $control, $soundName, $soundVariant = 0, $soundVolume = 1., $eventLabel = self::LABEL_MOUSECLICK) {
		if (!($control instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Sounds!');
			return $this;
		}
		$control->setScriptEvents(true);
		$control->checkId();
		$control->addClass(self::CLASS_SOUND);
		$soundData = array();
		$soundData['soundName'] = $soundName;
		$soundData['soundVariant'] = $soundVariant;
		$soundData['soundVolume'] = $soundVolume;
		$soundData['controlId'] = $control->getId();
		$soundData['eventLabel'] = $eventLabel;
		array_push($this->sounds, $soundData);
		return $this;
	}

	/**
	 * Add a Toggling Behavior
	 *
	 * @param Control $clickControl The Control that toggles another Control on Click
	 * @param Control $toggleControl The Control to toggle
	 * @param string $mode (optional) Whether the Visibility should be toggled or only en-/disabled
	 * @return \FML\Script\Script
	 */
	public function addToggle(Control $clickControl, Control $toggleControl, $option = null) {
		if (!($clickControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Toggles!');
			return $this;
		}
		$toggleControl->checkId();
		if ($option == self::OPTION_TOGGLE_HIDE) {
			$toggleControl->setVisible(true);
			$clickControl->addClass($option);
		}
		else if ($option == self::OPTION_TOGGLE_SHOW) {
			$toggleControl->setVisible(false);
			$clickControl->addClass($option);
		}
		$clickControl->setScriptEvents(true);
		$clickControl->addClass(self::CLASS_TOGGLE);
		$clickControl->addClass(self::CLASS_TOGGLE . '-' . $toggleControl->getId());
		$this->toggles = true;
		return $this;
	}

	/**
	 * Add a Spectate Button Behavior
	 *
	 * @param Control $clickControl The Control that works as Spectate Button
	 * @param string $spectateTargetLogin The Login of the Player to Spectate
	 * @return \FML\Script\Script
	 */
	public function addSpectateButton(Control $clickControl, $spectateTargetLogin) {
		// FIXME: current implementation doesn't support logins with dots in them ('nick.name')
		if (!($clickControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ClickControl for Spectating!');
			return $this;
		}
		$clickControl->setScriptEvents(true);
		$clickControl->addClass(self::CLASS_SPECTATE);
		$spectateTargetLogin = (string) $spectateTargetLogin;
		$clickControl->addClass(self::CLASS_SPECTATE . '-' . $spectateTargetLogin);
		$this->spectate = true;
		return $this;
	}

	/**
	 * Trigger an Action on Control Click
	 *
	 * @param Control $actionControl The Control triggering the Action
	 * @param string $action (optional) The Action to trigger (if empty the Action of the Control will be triggered)
	 * @return \FML\Script\Script
	 */
	public function addPageActionTrigger(Control $actionControl, $action = null) {
		if (!($actionControl instanceof Scriptable)) {
			trigger_error('Scriptable Control needed as ActionControl for PageActions!');
			return $this;
		}
		$action = (string) $action;
		if (strlen($action) <= 0) {
			if (!($actionControl instanceof Actionable)) {
				trigger_error('Either Action or Actionable Control needed for PageActions!');
				return $this;
			}
			$action = $actionControl->getAction();
		}
		$actionControl->setScriptEvents(true);
		$actionControl->setAction('');
		$actionControl->addClass(self::CLASS_PAGEACTION);
		$actionControl->addClass(self::CLASS_PAGEACTION . '-' . $action);
		$this->pageActions = true;
		return $this;
	}

	/**
	 * Add a Label showing the current Time
	 *
	 * @param Label $timeLabel The Label showing the current Time
	 * @param bool $hideSeconds Whether the seconds should be hidden
	 * @param bool $showDate Whether to show the full Date Text
	 * @return \FML\Script\Script
	 */
	public function addTimeLabel(Label $timeLabel, $hideSeconds = false, $showDate = false) {
		$timeLabel->addClass(self::CLASS_TIME);
		if ($hideSeconds) {
			$timeLabel->addClass(self::OPTION_TIME_HIDESECONDS);
		}
		if ($showDate) {
			$timeLabel->addClass(self::OPTION_TIME_FULLDATE);
		}
		$this->times = true;
		return $this;
	}

	/**
	 * Create the Script XML Tag
	 *
	 * @param \DOMDocument $domDocument DOMDocument for which the XML Element should be created
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$scriptXml = $domDocument->createElement($this->tagName);
		$scriptText = $this->buildScriptText();
		$scriptComment = $domDocument->createComment($scriptText);
		$scriptXml->appendChild($scriptComment);
		return $scriptXml;
	}

	/**
	 * Build the complete Script Text
	 *
	 * @return string
	 */
	private function buildScriptText() {
		$mainFunction = $this->getMainFunction();
		$labels = $this->getLabels();
		$functions = $this->getFunctions();
		$constants = $this->getConstants();
		$includes = $this->getIncludes();
		$headerComment = $this->getHeaderComment();
		
		$scriptText = PHP_EOL;
		$scriptText .= $headerComment;
		$scriptText .= $includes;
		$scriptText .= $constants;
		$scriptText .= $functions;
		$scriptText .= $labels;
		$scriptText .= $mainFunction;
		
		return $scriptText;
	}

	/**
	 * Get the Header Comment
	 *
	 * @return string
	 */
	private function getHeaderComment() {
		$headerComment = file_get_contents(__DIR__ . '/Parts/Header.txt');
		return $headerComment;
	}

	/**
	 * Get the Includes
	 *
	 * @return string
	 */
	private function getIncludes() {
		$includesText = PHP_EOL;
		foreach ($this->includes as $namespace => $file) {
			$includesText .= "#Include \"{$file}\" as {$namespace}" . PHP_EOL;
		}
		return $includesText;
	}

	/**
	 * Get the Constants
	 *
	 * @return string
	 */
	private function getConstants() {
		$this->buildTooltipConstants();
		$constantsText = PHP_EOL;
		foreach ($this->constants as $name => $value) {
			$constantsText .= "#Const {$name} {$value}" . PHP_EOL;
		}
		return $constantsText;
	}

	/**
	 * Build the Constants needed for tooltips
	 */
	private function buildTooltipConstants() {
		if (!$this->tooltips) return;
		$constantText = '[';
		$index = 0;
		$count = count($this->tooltipTexts);
		if ($count > 0) {
			foreach ($this->tooltipTexts as $tooltipId => $tooltipTexts) {
				$constantText .= '"' . Builder::escapeText($tooltipId) . '" => [';
				$subIndex = 0;
				$subCount = count($tooltipTexts);
				if ($subCount > 0) {
					foreach ($tooltipTexts as $hoverId => $text) {
						$constantText .= '"' . Builder::escapeText($hoverId) . '" => "' . Builder::escapeText($text) . '"';
						if ($subIndex < $subCount - 1) $constantText .= ', ';
						$subIndex++;
					}
				}
				else {
					$constantText .= '""';
				}
				$constantText .= ']';
				if ($index < $count - 1) $constantText .= ', ';
				$index++;
			}
		}
		else {
			$constantText .= '"" => ["" => ""]';
		}
		$constantText .= ']';
		$this->setConstant(self::CONSTANT_TOOLTIPTEXTS, $constantText);
	}

	/**
	 * Get the Functions
	 *
	 * @return string
	 */
	private function getFunctions() {
		$this->buildTooltipFunctions();
		$functionsText = PHP_EOL;
		foreach ($this->functions as $name => $coding) {
			$functionsText .= $coding;
		}
		return $functionsText;
	}

	/**
	 * Build the Functions needed for Tooltips
	 */
	private function buildTooltipFunctions() {
		if (!$this->tooltips) return;
		$this->setInclude('TextLib', 'TextLib');
		$setFunctionText = "
Void " . self::FUNCTION_SETTOOLTIPTEXT . "(CMlControl _TooltipControl, CMlControl _HoverControl) {
	if (!_TooltipControl.Visible) return;
	declare TooltipId = _TooltipControl.ControlId;
	declare HoverId = _HoverControl.ControlId;
	if (!" . self::CONSTANT_TOOLTIPTEXTS . ".existskey(TooltipId)) return;
	if (!" . self::CONSTANT_TOOLTIPTEXTS . "[TooltipId].existskey(HoverId)) return;
	declare Label = (_TooltipControl as CMlLabel);
	Label.Value = " . self::CONSTANT_TOOLTIPTEXTS . "[TooltipId][HoverId];
}";
		$this->setFunction(self::FUNCTION_SETTOOLTIPTEXT, $setFunctionText);
		$getFunctionText = "
Text " . self::FUNCTION_GETTOOLTIPCONTROLID . "(Text _ControlClass) {
	declare ClassParts = TextLib::Split(\"-\", _ControlClass);
	if (ClassParts.count < 2) return \"\";
	if (ClassParts[0] != \"" . self::CLASS_TOOLTIP . "\") return \"\";
	return ClassParts[1];
}";
		$this->setFunction(self::FUNCTION_GETTOOLTIPCONTROLID, $getFunctionText);
	}

	/**
	 * Get Labels
	 *
	 * @return string
	 */
	private function getLabels() {
		$labelsText = PHP_EOL;
		$labelsText .= $this->getTooltipLabels();
		$labelsText .= $this->getMenuLabels();
		$labelsText .= $this->getPagesLabels();
		$labelsText .= $this->getProfileLabels();
		$labelsText .= $this->getMapInfoLabels();
		$labelsText .= $this->getSoundLabels();
		$labelsText .= $this->getToggleLabels();
		$labelsText .= $this->getSpectateLabels();
		$labelsText .= $this->getTimeLabels();
		return $labelsText;
	}

	/**
	 * Get the Tooltip Labels
	 *
	 * @return string
	 */
	private function getTooltipLabels() {
		if (!$this->tooltips) return '';
		$mouseOverScript = "
if (Event.Control.HasClass(\"" . self::CLASS_TOOLTIP . "\")) {
	declare Invert = Event.Control.HasClass(\"" . self::OPTION_TOOLTIP_INVERT . "\");
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ControlId = " . self::FUNCTION_GETTOOLTIPCONTROLID . "(ControlClass);
		if (ControlId == \"\") continue;
		declare TooltipControl <=> Page.GetFirstChild(ControlId);
		if (TooltipControl == Null) continue;
		TooltipControl.Visible = !Invert;
		" . self::FUNCTION_SETTOOLTIPTEXT . "(TooltipControl, Event.Control);
	}
}";
		$mouseOutScript = "
if (Event.Control.HasClass(\"" . self::CLASS_TOOLTIP . "\")) {
	declare FML_Clicked for Event.Control = False;
	declare StayOnClick = Event.Control.HasClass(\"" . self::OPTION_TOOLTIP_STAYONCLICK . "\");
	if (!StayOnClick || !FML_Clicked) {
		declare Invert = Event.Control.HasClass(\"" . self::OPTION_TOOLTIP_INVERT . "\");
		foreach (ControlClass in Event.Control.ControlClasses) {
			declare ControlId = " . self::FUNCTION_GETTOOLTIPCONTROLID . "(ControlClass);
			if (ControlId == \"\") continue;
			declare TooltipControl <=> Page.GetFirstChild(ControlId);
			if (TooltipControl == Null) continue;
			TooltipControl.Visible = Invert;
			" . self::FUNCTION_SETTOOLTIPTEXT . "(TooltipControl, Event.Control);
		}
	}
}";
		$mouseClickScript = "
if (Event.Control.HasClass(\"" . self::CLASS_TOOLTIP . "\")) {
	declare Handle = True;
	declare Show = False;
	declare StayOnClick = Event.Control.HasClass(\"" . self::OPTION_TOOLTIP_STAYONCLICK . "\");
	if (StayOnClick) {
		declare FML_Clicked for Event.Control = False;
		FML_Clicked = !FML_Clicked;
		if (FML_Clicked) {
			Handle = False;
		} else {
			Show = False;
		}
	} else {
		Handle = False;
	}
	if (Handle) {
		declare Invert = Event.Control.HasClass(\"" . self::OPTION_TOOLTIP_INVERT . "\");
		foreach (ControlClass in Event.Control.ControlClasses) {
			declare ControlId = " . self::FUNCTION_GETTOOLTIPCONTROLID . "(ControlClass);
			if (ControlId == \"\") continue;
			declare TooltipControl <=> Page.GetFirstChild(ControlId);
			if (TooltipControl == Null) continue;
			TooltipControl.Visible = Show && !Invert;
			" . self::FUNCTION_SETTOOLTIPTEXT . "(TooltipControl, Event.Control);
		}
	}
}";
		$tooltipsLabels = Builder::getLabelImplementationBlock(self::LABEL_MOUSEOVER, $mouseOverScript);
		$tooltipsLabels .= Builder::getLabelImplementationBlock(self::LABEL_MOUSEOUT, $mouseOutScript);
		$tooltipsLabels .= Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $mouseClickScript);
		return $tooltipsLabels;
	}

	/**
	 * Get the Menu Labels
	 *
	 * @return string
	 */
	private function getMenuLabels() {
		if (!$this->menus) return '';
		$this->setInclude('TextLib', 'TextLib');
		$mouseClickScript = "
if (Event.Control.HasClass(\"" . self::CLASS_MENUBUTTON . "\")) {
	declare Text MenuIdClass;
	declare Text MenuControlId;
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ClassParts = TextLib::Split(\"-\", ControlClass);
		if (ClassParts.count < 2) continue;
		MenuIdClass = ClassParts[0];
		MenuControlId = ClassParts[1];
		break;
	}
	Page.GetClassChildren(MenuIdClass, Page.MainFrame, True);
	foreach (MenuControl in Page.GetClassChildren_Result) {
		if (!MenuControl.HasClass(\"" . self::CLASS_MENU . "\")) continue;
		MenuControl.Visible = (MenuControlId == MenuControl.ControlId);
	}
}";
		$menuLabels = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $mouseClickScript);
		return $menuLabels;
	}

	/**
	 * Get the Pages Labels
	 *
	 * @return string
	 */
	private function getPagesLabels() {
		if (!$this->pages) return "";
		$this->setInclude('TextLib', 'TextLib');
		$pagesNumberPrefix = self::CLASS_PAGE . '-P';
		$pagesNumberPrefixLength = strlen($pagesNumberPrefix);
		$pagesScript = "
if (Event.Control.HasClass(\"" . self::CLASS_PAGER . "\")) {
	declare Text PagesId;
	declare Integer PagingAction;
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ClassParts = TextLib::Split(\"-\", ControlClass);
		if (ClassParts.count < 2) continue;
		if (ClassParts[0] != \"" . self::CLASS_PAGER . "\") continue;
		switch (TextLib::SubText(ClassParts[1], 0, 1)) {
			case \"I\": {
				PagesId = TextLib::SubText(ClassParts[1], 1, TextLib::Length(ClassParts[1]));
			}
			case \"A\": {
				PagingAction = TextLib::ToInteger(TextLib::SubText(ClassParts[1], 1, TextLib::Length(ClassParts[1])));
			}
		}
	}
	declare FML_PagesLastScriptStart for This = FML_ScriptStart;
	declare FML_MinPageNumber for This = Integer[Text];
	declare FML_MaxPageNumber for This = Integer[Text];
	declare FML_PageNumber for This = Integer[Text];
	if (FML_PagesLastScriptStart != FML_ScriptStart || !FML_PageNumber.existskey(PagesId) || !FML_MinPageNumber.existskey(PagesId) || !FML_MaxPageNumber.existskey(PagesId)) {
		Page.GetClassChildren(PagesId, Page.MainFrame, True);
		foreach (PageControl in Page.GetClassChildren_Result) {
			if (!PageControl.HasClass(\"" . self::CLASS_PAGE . "\")) continue;
			foreach (ControlClass in PageControl.ControlClasses) {
				if (TextLib::SubText(ControlClass, 0, {$pagesNumberPrefixLength}) != \"{$pagesNumberPrefix}\") continue;
				declare PageNumber = TextLib::ToInteger(TextLib::SubText(ControlClass, {$pagesNumberPrefixLength}, TextLib::Length(ControlClass)));
				if (!FML_MinPageNumber.existskey(PagesId) || PageNumber < FML_MinPageNumber[PagesId]) {
					FML_MinPageNumber[PagesId] = PageNumber;
				}
				if (!FML_MaxPageNumber.existskey(PagesId) || PageNumber > FML_MaxPageNumber[PagesId]) {
					FML_MaxPageNumber[PagesId] = PageNumber;
				}
				break;
			}
		}
		FML_PageNumber[PagesId] = FML_MinPageNumber[PagesId];
	}
	FML_PageNumber[PagesId] += PagingAction;
	if (FML_PageNumber[PagesId] < FML_MinPageNumber[PagesId]) {
		FML_PageNumber[PagesId] = FML_MinPageNumber[PagesId];
	}
	if (FML_PageNumber[PagesId] > FML_MaxPageNumber[PagesId]) {
		FML_PageNumber[PagesId] = FML_MaxPageNumber[PagesId];
	}
	FML_PagesLastScriptStart = FML_ScriptStart;
	Page.GetClassChildren(PagesId, Page.MainFrame, True);
	foreach (PageControl in Page.GetClassChildren_Result) {
		if (!PageControl.HasClass(\"" . self::CLASS_PAGE . "\")) continue;
		declare PageNumber = -1;
		foreach (ControlClass in PageControl.ControlClasses) {
			if (TextLib::SubText(ControlClass, 0, {$pagesNumberPrefixLength}) != \"{$pagesNumberPrefix}\") continue;
			PageNumber = TextLib::ToInteger(TextLib::SubText(ControlClass, {$pagesNumberPrefixLength}, TextLib::Length(ControlClass)));
			break;
		}
		PageControl.Visible = (PageNumber == FML_PageNumber[PagesId]);
	}
	Page.GetClassChildren(\"" . self::CLASS_PAGELABEL . "\", Page.MainFrame, True);
	foreach (PageControl in Page.GetClassChildren_Result) {
		if (!PageControl.HasClass(PagesId)) continue;
		declare PageLabel <=> (PageControl as CMlLabel);
		PageLabel.Value = FML_PageNumber[PagesId]^\"/\"^FML_MaxPageNumber[PagesId];
	}
}";
		$pagesLabels = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $pagesScript);
		return $pagesLabels;
	}

	/**
	 * Get the Profile Labels
	 *
	 * @return string
	 */
	private function getProfileLabels() {
		if (!$this->profile) return "";
		$this->setInclude('TextLib', 'TextLib');
		$prefixLength = strlen(self::CLASS_PROFILE) + 1;
		$profileScript = "
if (Event.Control.HasClass(\"" . self::CLASS_PROFILE . "\")) {
	declare Login = LocalUser.Login;
	if (!Event.Control.HasClass(\"" . self::OPTION_PROFILE_OWN . "\")) {
		foreach (ControlClass in Event.Control.ControlClasses) {
			declare ClassParts = TextLib::Split(\"-\", ControlClass);
			if (ClassParts.count < 2) continue;
			if (ClassParts[0] != \"" . self::CLASS_PROFILE . "\") continue;
			Login = TextLib::SubText(ControlClass, {$prefixLength}, TextLib::Length(ControlClass));
			break;
		}
	}
	ShowProfile(Login);
}";
		$profileLabels = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $profileScript);
		return $profileLabels;
	}

	/**
	 * Get the Map Info Labels
	 *
	 * @return string
	 */
	private function getMapInfoLabels() {
		if (!$this->mapInfo) return "";
		$mapInfoScript = "
if (Event.Control.HasClass(\"" . self::CLASS_MAPINFO . "\")) {
	ShowCurChallengeCard();
}";
		$mapInfoLabels = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $mapInfoScript);
		return $mapInfoLabels;
	}

	/**
	 * Get the Sound Labels
	 *
	 * @return string
	 */
	private function getSoundLabels() {
		if (!$this->sounds) return '';
		$labelScripts = array();
		foreach ($this->sounds as $soundData) {
			$volume = Builder::getReal($soundData['soundVolume']);
			$labelScript = "
		case \"{$soundData['controlId']}\": {
			PlayUiSound(CMlScriptIngame::EUISound::{$soundData['soundName']}, {$soundData['soundVariant']}, {$volume});
		}";
			if (!isset($labelScripts[$soundData['eventLabel']])) {
				$labelScripts[$soundData['eventLabel']] = '';
			}
			$labelScripts[$soundData['eventLabel']] .= $labelScript;
		}
		
		$soundScript = '';
		foreach ($labelScripts as $label => $scriptPart) {
			$labelScript = "
if (Event.Control.HasClass(\"" . self::CLASS_SOUND . "\")) {
	switch (Event.Control.ControlId) {
		{$scriptPart}
	}
}";
			$soundScript .= Builder::getLabelImplementationBlock($label, $labelScript);
		}
		return $soundScript;
	}

	/**
	 * Get the Toggle Labels
	 *
	 * @return string
	 */
	private function getToggleLabels() {
		if (!$this->toggles) return '';
		$this->setInclude('TextLib', 'TextLib');
		$toggleScript = "
if (Event.Control.HasClass(\"" . self::CLASS_TOGGLE . "\")) {
	declare HasShow = Event.Control.HasClass(\"" . self::OPTION_TOGGLE_SHOW . "\");
	declare HasHide = Event.Control.HasClass(\"" . self::OPTION_TOGGLE_HIDE . "\");
	declare Toggle = True;
	declare Show = True;
	if (HasShow || HasHide) {
		Toggle = False;
		Show = HasShow;
	}
	declare PrefixLength = TextLib::Length(\"" . self::CLASS_TOGGLE . "\");
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ClassParts = TextLib::Split(\"-\", ControlClass);
		if (ClassParts.count < 2) continue;
		if (ClassParts[0] != \"" . self::CLASS_TOGGLE . "\") continue;
		declare ToggleControl <=> Page.GetFirstChild(ClassParts[1]);
		if (ToggleControl == Null) continue;
		if (Toggle) {
			ToggleControl.Visible = !ToggleControl.Visible;
		} else {
			ToggleControl.Visible = Show;
		}
	}
}";
		$toggleScript = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $toggleScript);
		return $toggleScript;
	}

	/**
	 * Get the Spectate labels
	 *
	 * @return string
	 */
	private function getSpectateLabels() {
		if (!$this->spectate) return '';
		$this->setInclude('TextLib', 'TextLib');
		$prefixLength = strlen(self::CLASS_SPECTATE) + 1;
		$spectateScript = "
if (Event.Control.HasClass(\"" . self::CLASS_SPECTATE . "\")) {
	declare Login = \"\";
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ClassParts = TextLib::Split(\"-\", ControlClass);
		if (ClassParts.count < 2) continue;
		if (ClassParts[0] != \"" . self::CLASS_SPECTATE . "\") continue;
		Login = TextLib::SubText(ControlClass, {$prefixLength}, TextLib::Length(ControlClass));
		break;
	}
	if (Login != \"\") {
		SetSpectateTarget(Login);
	}
}";
		$spectateScript = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $spectateScript);
		return $spectateScript;
	}

	/**
	 * Get the Page Actions Labels
	 *
	 * @return string
	 */
	private function getPageActionLabels() {
		if (!$this->pageActions) return '';
		$this->setInclude('TextLib', 'TextLib');
		$prefixLength = strlen(self::CLASS_PAGEACTION) + 1;
		$pageActionScript = "
if (Event.Control.HasClass(\"" . self::CLASS_PAGEACTION . "\")) {
	declare Action = \"\";
	foreach (ControlClass in Event.Control.ControlClasses) {
		declare ClassParts = TextLib::Split(\"-\", ControlClass);
		if (ClassParts.count < 2) continue;
		if (ClassParts[0] != \"" . self::CLASS_PAGEACTION . "\") continue;
		Action = TextLib::SubText(ControlClass, {$prefixLength}, TextLib::Length(ControlClass));
		break;
	}
	if (Action != \"\") {
		TriggerPageAction(Action);
	}
}";
		$pageActionScript = Builder::getLabelImplementationBlock(self::LABEL_MOUSECLICK, $pageActionScript);
		return $pageActionScript;
	}

	/**
	 * Get the Time Labels
	 *
	 * @return string
	 */
	private function getTimeLabels() {
		if (!$this->times) return '';
		$this->setInclude('TextLib', 'TextLib');
		$timesScript = "
Page.GetClassChildren(\"" . self::CLASS_TIME . "\", Page.MainFrame, True);
foreach (TimeLabelControl in Page.GetClassChildren_Result) {
	declare TimeLabel = (TimeLabelControl as CMlLabel);
	declare HideSeconds = TimeLabel.HasClass(\"" . self::OPTION_TIME_HIDESECONDS . "\");
	declare ShowDate = TimeLabel.HasClass(\"" . self::OPTION_TIME_FULLDATE . "\");
	declare TimeText = CurrentLocalDateText;
	if (HideSeconds) {
		TimeText = TextLib::SubText(TimeText, 0, 16);
	}
	if (!ShowDate) {
		TimeText = TextLib::SubText(TimeText, 11, 9);
	}
	TimeLabel.Value = TimeText;
}";
		$timesScript = Builder::getLabelImplementationBlock(self::LABEL_TICK, $timesScript);
		return $timesScript;
	}

	/**
	 * Get the Main Function
	 *
	 * @return string
	 */
	private function getMainFunction() {
		$mainFunction = file_get_contents(__DIR__ . '/Parts/Main.txt');
		return $mainFunction;
	}

	/**
	 * Return the Array of additional optional Parameters
	 *
	 * @param array $args The Array of Function Parameters
	 * @param int $offset The Number of obligatory Parameters
	 * @return array
	 */
	private function spliceParameters(array $params, $offset) {
		$args = array_splice($params, $offset);
		if (!$args) return $args;
		$parameters = array();
		foreach ($args as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key => $value) {
					$parameters[$key] = $value;
				}
			}
			else {
				$parameters[$arg] = true;
			}
		}
		return $parameters;
	}
}
