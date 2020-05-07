<?php

namespace ManiaControl\Manialinks;

use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\ManiaControl;
use ManiaControl\ManiaExchange\MXMapInfo;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Maps\Map;
use ManiaControl\Players\Player;
use ManiaControl\Utils\ColorUtil;

/**
 * ElementBuilder Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ElementBuilder {
	/**
	 * Constants
	 */
	const COLUMN_ACTION_WIDTH     = 4;
	const COLUMN_ACTION_WIDTH_MIN = 20;
	const COLUMN_ENTRY_WIDTH      = 4;
	const FOOTER_BUTTON_HEIGHT    = 4;
	const FOOTER_HEIGHT           = 5;
	const FOOTER_SPACING          = 2;
	const FOOTER_SPACING_BOTTOM   = 10;
	const HEADER_HEIGHT           = 5;
	const HEADER_SPACING          = 2;
	const HEADER_WIDTH_FACTOR     = 0.9;
	const ROW_LINE_HEIGHT         = 4;
	const ROW_SPACING_BOTTOM      = 5;
	const ROW_SPACING_SIDES       = 2;
	const ROW_SPACING_TOP         = 5;

	const DEFAULT_KARMA_PLUGIN = 'MCTeam\KarmaPlugin';
	
	/**
	 * Private Properties
	 */
	/** @var array $actions */
	private $actions = array();
	/** @var array $columns */
	private $columns = array();
	/** @var callable $entryCurrent */
	private $entryCurrent = null;
	/** @var array $footerButtons */
	private $footerButtons = array();
	/** @var Frame $headerFrame */
	private $headerFrame = null;
	/** @var array $rowData */
	private $rowData = array();

	/**
	 * Construct a new ElementBuilder instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Adds actions to list entry
	 * @param string $icon
	 * @param bool $confirmation
	 * @param callable $tooltipFunction
	 */
	public function addAction($icon, $confirmation, $tooltipFunction) {
		array_push($this->actions, array($icon, $confirmation, $tooltipFunction));
	}

	/**
	 * Adds a column to the list
	 * @param string $name
	 * @param float $widthFactor
	 * @param callable $dataFunction
	 */
	public function addColumn($name, $widthFactor, $dataFunction) {
		array_push($this->columns, array($name, $widthFactor, $dataFunction));
	}

	/**
	 * Adds a arrow to point to the current/personal entry
	 * @param callable $function
	 */
	public function addEntryCurrent($function) {
		$this->entryCurrent = $function;
	}

	/**
	 * Adds a footer button to the list
	 * @param string $description
	 * @param string $action
	 * @param callable $tooltipFunction
	 */
	public function addFooterButton($description, $action, $tooltipFunction = null) {
		array_push($this->footerButtons, array($description, $action, $tooltipFunction));
	}

	/**
	 * Adds a header frame to the list
	 * @param Frame $headerFrame
	 */
	public function addHeaderFrame(Frame $headerFrame) {
		$this->headerFrame = $headerFrame;
		$this->headerFrame->setHeight(self::HEADER_HEIGHT);
		$this->headerFrame->setWidth($this->getHeaderFrameWidth());
	}

	/**
	 * Adds rows to the list
	 * @param array $rows
	 */
	public function addRows(array $rows) {
		$this->rowData = array_merge($this->rowData, $rows);
	}

	/**
	 * Build Round Text Button
	 * @param string $description
	 * @param float $width
	 * @param float $height
	 * @param string $action
	 * @param string $logoUrl
	 */
	public function buildRoundTextButton($description, $width, $height, $action, $logoUrl = null) {
		$frame = new Frame();

		$quad = new Quad_BgsPlayerCard();
		$frame->addChild($quad);
		$quad->setAction($action);
		$quad->setSize($width, $height);
		$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
		$quad->setZ(-1);

		$label = new Label_Button();
		$frame->addChild($label);
		$label->setText($description);
		$label->setTextSize($height / 4);
		$label->setZ(1);

		if ($logoUrl) {
			$logoQuad = new Quad();
			$frame->addChild($logoQuad);
			$logoQuad->setImageUrl($logoUrl);
			$logoQuad->setSize(0.75*$height, 0.75*$height);
			$logoQuad->setX(-$width/2 + 0.75*$height);
			$logoQuad->setZ(1);

			$label->setHorizontalAlign($label::RIGHT);
			$label->setX($width/2 - 0.5*$height);
		}

		return $frame;
	}

	/**
	 * Build Karma Gauge
	 * @param Map|MXMapInfo $map
	 * @param float $width
	 * @param float $height
	 * @param float $textSize
	 */
	public function buildKarmaGauge($map, $width, $height, $textSize = 0.9) {
		$karmaPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_KARMA_PLUGIN);
		if (!$karmaPlugin) {
			return null;
		}

		// default elements
		$frame = new Frame();

		$karmaGauge = new Gauge();
		$frame->addChild($karmaGauge);
		$karmaGauge->setDrawBackground(false);
		$karmaGauge->setSize($width, $height);
		$karmaGauge->setZ(-1);

		$karmaLabel = new Label_Text();
		$frame->addChild($karmaLabel);
		$karmaLabel->setSize($width/2, $height * $textSize);
		$karmaLabel->setStyle($this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultLabelStyle());
		$karmaLabel->setTextColor('fff');
		$karmaLabel->setTextSize($textSize);
		$karmaLabel->setY(-$height/50);
		$karmaLabel->setZ(1);
		
		// fetch MX Karma
		$displayMxKarma = $this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_WIDGET_DISPLAY_MX);
		$karma = null;
		$votes = null;
		$mxInfo = null;
		if ($map instanceof Map && isset($map->mx)) {
			$mxInfo = $map->mx;
		} elseif ($map instanceof MXMapInfo) {
			$mxInfo = $map;
		}

		if ($displayMxKarma && $mxInfo) {
			$karma = $mxInfo->ratingVoteAverage / 100;
			$votes = array("count" => $mxInfo->ratingVoteCount);
		} elseif ($map instanceof Map) {
			$karma = $karmaPlugin->getMapKarma($map);
			$votes = $karmaPlugin->getMapVotes($map);
		}

		if (!is_numeric($karma) || $votes['count'] <= 0) {
			// No Karma
			$karmaGauge->setColor('00fb');
			$karmaGauge->setRatio(0.);

			$karmaLabel->setText('-');
			return $frame;
		}

		// Karma available
		$karma = floatval($karma);
		$karmaText = '';
		if ($this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA)) {
			$karmaText = '  ' . round($karma * 100.) . '% (' . $votes['count'] . ')';
		} else {
			$minus = 0;
			$plus  = 0;
			foreach ($votes as $vote) {
				if (!isset($vote->vote) || $vote->vote === 0.5) {
					continue;
				}

				if ($vote->vote < 0.5) {
					$minus += $vote->count;
				} else {
					$plus += $vote->count;
				}
			}
			$endKarma  = $plus - $minus;
			$karmaText = '  ' . $endKarma . ' (' . $votes['count'] . 'x / ' . round($karma * 100.) . '%)';
		}

		$karmaColor = ColorUtil::floatToStatusColor($karma);
		$karmaGauge->setColor($karmaColor . '8');
		$karmaGauge->setRatio(0.15 + 0.85*$karma);

		$karmaLabel->setText($karmaText);

		return $frame;
	}

	/**
	 * Returns the width of the optional header frame
	 * 
	 * @return int
	 */
	public function getHeaderFrameWidth() {
		return (int) (self::HEADER_WIDTH_FACTOR * $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth());
	}

	public function renderList(Player $player) {
		$nbActions = count($this->actions);
		$nbFooterButtons = count($this->footerButtons);

		$hasActions = $nbActions > 0;
		$hasEntryCurrent = $this->entryCurrent !== null;
		$hasFooter = $nbFooterButtons > 0;
		$hasHeader = $this->headerFrame !== null;

		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$width = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();

		$heightRows = $height - self::ROW_SPACING_TOP - self::ROW_SPACING_BOTTOM;
		$posYRows = $height/2 - self::ROW_SPACING_TOP - self::ROW_LINE_HEIGHT/2;

		// Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Header frame
		if ($hasHeader) {
			$frame->addChild($this->headerFrame);
			$this->headerFrame->setY($height/2 - self::HEADER_SPACING - self::HEADER_HEIGHT/2);
			
			$headerSize = 2*self::HEADER_SPACING + self::HEADER_HEIGHT;
			$heightRows -= $headerSize;
			if ($posYRows > $height/2 - $headerSize)
				$posYRows = $height/2 - $headerSize;
		}

		// Footer
		if ($hasFooter) {
			$nbFooterSections = 3*$nbFooterButtons + 1;
			$buttonSpacing = (1. / $nbFooterSections) * $width;
			$buttonWidth = $buttonSpacing * 2;
			for ($i = 0; $i < $nbFooterButtons; $i++) {
				list($description, $action, $tooltipFunction) = $this->footerButtons[$i];

				$label = new Label_Button();
				$frame->addChild($label);
				$label->setHeight(self::FOOTER_HEIGHT);
				$label->setText($description);
				$label->setTextSize(1);
				$label->setWidth($buttonWidth);
				$label->setX(-$width/2 + ($i+1)*$buttonSpacing + $i*$buttonWidth + $buttonWidth/2);
				$label->setY(-$height/2 + self::FOOTER_SPACING_BOTTOM);
				$label->setZ(0.1);

				$quad = new Quad_BgsPlayerCard();
				$frame->addChild($quad);
				$quad->setAction($action);
				$quad->setHeight(self::FOOTER_HEIGHT);
				$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
				$quad->setWidth($buttonWidth);
				$quad->setX(-$width/2 + ($i+1)*$buttonSpacing + $i*$buttonWidth + $buttonWidth/2);
				$quad->setY(-$height/2 + self::FOOTER_SPACING_BOTTOM);
				$quad->setZ(0.01);
			}

			$footerSize = self::FOOTER_SPACING_BOTTOM + self::FOOTER_HEIGHT + self::FOOTER_SPACING;
			$heightRows -= $footerSize;
		}

		$actionsWidth = max(self::COLUMN_ACTION_WIDTH_MIN, $nbActions * self::COLUMN_ACTION_WIDTH);
		$baseColumnPosX = -$width / 2 + self::ROW_SPACING_SIDES + self::COLUMN_ENTRY_WIDTH;
		$baseRowPosY = $posYRows;
		$baseColumnWidth = $width - 2*self::ROW_SPACING_SIDES - self::COLUMN_ENTRY_WIDTH - $actionsWidth;
		$nbRows = (int) ($heightRows / self::ROW_LINE_HEIGHT) - 1;

		// Description Row
		$descriptionFrame = new Frame();
		$frame->addChild($descriptionFrame);
		$descriptionFrame->setX($baseColumnPosX);
		$descriptionFrame->setY($baseRowPosY);

		$labelLine = new LabelLine($descriptionFrame);
		$columnPosX = self::ROW_SPACING_SIDES;
		if ($hasEntryCurrent) {
			$columnPosX += self::COLUMN_ENTRY_WIDTH;
		}

		foreach ($this->columns as $column) {
			list($name, $widthFactor, $dataFunction) = $column;
			$labelLine->addLabelEntryText($name, $columnPosX);
			$columnPosX += $widthFactor * $baseColumnWidth;
		}
		$labelLine->addLabelEntryText('Actions', $columnPosX);
		$labelLine->render();

		// Data Rows
		$pageFrame = null;
		$columnPosX = self::ROW_SPACING_SIDES;
		$baseRowPosY -= self::ROW_LINE_HEIGHT;
		$rowPosY = $baseRowPosY;
		$pageNumber = 1;
		for ($i = 0; $i < count($this->rowData); $i++) {
			$data = $this->rowData[$i];

			if ($i % $nbRows === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);

				$paging->addPageControl($pageFrame, $pageNumber);
				$pageNumber++;
				$rowPosY = $baseRowPosY;
			}

			$playerFrame = new Frame();
			$pageFrame->addChild($playerFrame);
			$playerFrame->setX($baseColumnPosX);
			$playerFrame->setY($rowPosY);

			if ($i % 2 === 1) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->addChild($lineQuad);
				$lineQuad->setSize($width, self::ROW_LINE_HEIGHT);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-1);
			}

			if ($hasEntryCurrent) {
				
			}

			$rowPosY -= self::ROW_LINE_HEIGHT;
		}

		$this->maniaControl->getManialinkManager()->sendManialink($maniaLink, $player);
		$this->reset();
	}

	/**
	 * Resets the internal builder data
	 */
	private function reset() {
		$this->actions       = array();
		$this->columns       = array();
		$this->entryCurrent  = null;
		$this->footerButtons = array();
		$this->headerFrame   = null;
		$this->rowData       = array();
	}
}
