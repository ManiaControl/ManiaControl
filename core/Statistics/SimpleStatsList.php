<?php

namespace ManiaControl\Statistics;

use FML\Controls\Frame;

use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;

use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\LabelLine;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Utils\Formatter;

/**
 * Simple Stats List Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class SimpleStatsList implements ManialinkPageAnswerListener, CallbackListener, CommandListener {
	/*
	 * Constants
	 */
	const ACTION_OPEN_STATSLIST = 'SimpleStatsList.OpenStatsList';
	const ACTION_SORT_STATS     = 'SimpleStatsList.SortStats';
	const ACTION_PAGING_CHUNKS  = 'SimpleStatsList.PagingChunk';
	const MAX_PLAYERS_PER_PAGE  = 15;
	const MAX_PAGES_PER_CHUNK   = 10;
	const CACHE_CURRENT_PAGE    = 'SimpleStatsList.CurrentPage';


	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	private $statArray    = array();
	private $statsWidth   = 0;

	/**
	 * Construct a new simple stats list instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::ONINIT, $this, 'handleOnInit');
	}

	/**
	 * Add the menu entry
	 */
	public function handleOnInit() {
		$this->maniaControl->getCommandManager()->registerCommandListener('stats', $this, 'command_ShowStatsList', false, 'Shows statistics.');

		// Action Open StatsList
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_OPEN_STATSLIST, $this, 'command_ShowStatsList');

		$itemQuad = new Quad_UIConstruction_Buttons();
		$itemQuad->setSubStyle($itemQuad::SUBSTYLE_Stats);
		$itemQuad->setAction(self::ACTION_OPEN_STATSLIST);
		$this->maniaControl->getActionsMenu()->addMenuItem($itemQuad, true, 14, 'Open Statistics');
		//TODO Chunking

		//TODO settings if a stat get shown
		$this->registerStat(PlayerManager::STAT_SERVERTIME, 10, "ST", 20, StatisticManager::STAT_TYPE_TIME);
		$this->registerStat(StatisticCollector::STAT_ARROW_HIT, 20, "H");
		$this->registerStat(StatisticCollector::STAT_ON_NEARMISS, 30, "NM");
		$this->registerStat(StatisticCollector::STAT_ON_KILL, 40, "K");
		$this->registerStat(StatisticCollector::STAT_ON_DEATH, 50, "D");
		$this->registerStat(StatisticCollector::STAT_ON_CAPTURE, 60, "C");

		$this->registerStat(StatisticManager::SPECIAL_STAT_KD_RATIO, 70, "K/D", 12, StatisticManager::STAT_TYPE_FLOAT);
		$this->registerStat(StatisticManager::SPECIAL_STAT_LASER_ACC, 80, "LAcc", 15, StatisticManager::STAT_TYPE_FLOAT);
		$this->registerStat(StatisticManager::SPECIAL_STAT_HITS_PH, 85, "H/h", 15, StatisticManager::STAT_TYPE_FLOAT);
	}

	/**
	 * Register a Certain Stat
	 *
	 * @param string $statName
	 * @param int    $order
	 * @param string $headShortCut
	 * @param int    $width
	 * @param string $format
	 */
	public function registerStat($statName, $order, $headShortCut, $width = 10, $format = StatisticManager::STAT_TYPE_INT) {
		// TODO: use own model class
		$this->statArray[$order]                 = array();
		$this->statArray[$order]["Name"]         = $statName;
		$this->statArray[$order]["HeadShortCut"] = '$o' . $headShortCut;
		$this->statArray[$order]["Width"]        = $width;
		$this->statArray[$order]["Format"]       = $format;
		$this->statsWidth                        += $width;
	}

	/**
	 * Show the stat List
	 *
	 * @param array  $callback
	 * @param Player $player
	 */
	public function command_ShowStatsList(array $callback, Player $player) {
		$this->showStatsList($player);
	}

	/**
	 * Show the StatsList Widget to the Player
	 *
	 * @param Player $player
	 * @param string $order
	 */
	public function showStatsList(Player $player, $order = PlayerManager::STAT_SERVERTIME, $pageIndex = -1) {
		$height       = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowSubStyle();
		$limit        = 2000;

		if ($pageIndex < 0) {
			$pageIndex = (int) $player->getCache($this, self::CACHE_CURRENT_PAGE);
		}

		$player->setCache($this, self::CACHE_CURRENT_PAGE, $pageIndex);

		$totalPlayersCount = $this->maniaControl->getStatisticManager()->getTotalStatsPlayerCount(-1);
		if ($totalPlayersCount > $limit) {
			$totalPlayersCount = $limit;
		}

		$chunkIndex       = $this->getChunkIndexFromPageNumber($pageIndex, $totalPlayersCount);
		$playerBeginIndex = $this->getChunkStatsBeginIndex($chunkIndex);
		$pagesCount       = ceil($totalPlayersCount / self::MAX_PLAYERS_PER_PAGE);

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$width     = $this->statsWidth + 60;
		//TODO handle size when stats are empty

		$script = $maniaLink->getScript();
		$paging = new Paging();
		$script->addFeature($paging);
		$paging->setCustomMaxPageNumber($pagesCount);
		$paging->setChunkActionAppendsPageNumber(true);
		$paging->setChunkActions(self::ACTION_PAGING_CHUNKS);
		$paging->setStartPageNumber($pageIndex + 1);

		// Main frame
		$frame = new Frame();
		$maniaLink->addChild($frame);
		$frame->setSize($width, $height);
		$frame->setPosition(0, 0, ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		// Background
		$backgroundQuad = new Quad();
		$frame->addChild($backgroundQuad);
		$backgroundQuad->setSize($width, $height);
		$backgroundQuad->setStyles($quadStyle, $quadSubstyle);

		// Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->addChild($closeQuad);
		$closeQuad->setPosition($width * 0.483, $height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(ManialinkManager::ACTION_CLOSEWIDGET);

		// Start offsets
		$xStart = -$width / 2;
		$posY   = $height / 2;

		// Predefine Description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($posY - 5);
		$headFrame->setZ(1);

		$labelLine = new LabelLine($headFrame);

		$posX = $xStart;
		$labelLine->addLabelEntryText('Id', $posX + 5);
		$labelLine->addLabelEntryText('Nickname', $posX + 14);

		// Headline
		$posX         = $xStart + 55;
		$statRankings = array();


		foreach ($this->statArray as $key => $stat) {
			$ranking = $this->maniaControl->getStatisticManager()->getStatsRanking($stat["Name"], -1, -1, $limit);
			if (!empty($ranking)) {
				$statRankings[$stat["Name"]] = $ranking;

				$label = new Label_Text();
				$label->setText($stat['HeadShortCut']);
				$label->setX($posX);
				$label->setSize($stat['Width'], 0);
				$label->setAction(self::ACTION_SORT_STATS . '.' . $stat["Name"]);
				$label->addTooltipLabelFeature($descriptionLabel, '$o ' . $stat["Name"]);
				$labelLine->addLabel($label);

				$posX += $stat["Width"];
			} else {
				unset($this->statArray[$key]);
			}
		}
		$labelLine->render();


		// define standard properties
		$index       = 1;
		$posY        -= 10;
		$pageFrame   = null;
		$playerIndex = 1 + $playerBeginIndex;

		if (!isset($statRankings[$order])) {
			return;
		}

		//Slice Array to chunk length
		$statRankings[$order] = array_slice($statRankings[$order], $playerBeginIndex, self::MAX_PAGES_PER_CHUNK * self::MAX_PLAYERS_PER_PAGE, true);
		$pageNumber           = 1 + $chunkIndex * self::MAX_PAGES_PER_CHUNK;
		foreach ($statRankings[$order] as $playerId => $value) {
			if ($index % self::MAX_PLAYERS_PER_PAGE === 1) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$pageFrame->setZ(1);

				$paging->addPageControl($pageFrame, $pageNumber);
				$pageNumber++;
				$posY = $height / 2 - 10;
			}

			$listPlayer = $this->maniaControl->getPlayerManager()->getPlayerByIndex($playerId);
			if (!$listPlayer) {
				continue;
			}

			$playerFrame = new Frame();
			$pageFrame->addChild($playerFrame);

			// Show current Player Arrow
			if ($playerId == $player->index) {
				$currentQuad = new Quad_Icons64x64_1();
				$playerFrame->addChild($currentQuad);
				$currentQuad->setX($xStart + 3.5);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			$labelLine = new LabelLine($playerFrame);
			$posX      = $xStart + 55;

			foreach ($this->statArray as $stat) {
				$statValue = 0;
				if (isset($statRankings[$stat['Name']][$playerId])) {
					$statValue = $statRankings[$stat['Name']][$playerId];
					if ($stat['Format'] == StatisticManager::STAT_TYPE_TIME) {
						$statValue = Formatter::formatTimeH($statValue);
					} else if ($stat['Format'] == StatisticManager::STAT_TYPE_FLOAT) {
						$statValue = round(floatval($statValue), 2);
					}
				}

				$label = new Label_Text();
				$label->setX($posX);
				$label->setText(strval($statValue));
				//$label->addTooltipLabelFeature($descriptionLabel, '$o ' . $stat['Name']);
				$labelLine->addLabel($label);

				$posX += $stat['Width'];

			}

			$labelLine->addLabelEntryText($playerIndex, $xStart + 5, 9);
			$labelLine->addLabelEntryText($listPlayer->nickname, $xStart + 14, 41);
			$labelLine->render();

			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(-0.5);
			}

			$index++;
			$playerIndex++;
			$posY -= 4;

		}

		$pagerSize = 6.;
		$pagerPrev = new Quad_Icons64x64_1();
		$frame->addChild($pagerPrev);
		$pagerPrev->setPosition($width * 0.42, $height * -0.44, 2)->setSize($pagerSize, $pagerSize)->setSubStyle($pagerPrev::SUBSTYLE_ArrowPrev);

		$pagerNext = new Quad_Icons64x64_1();
		$frame->addChild($pagerNext);
		$pagerNext->setPosition($width * 0.45, $height * -0.44, 2)->setSize($pagerSize, $pagerSize)->setSubStyle($pagerNext::SUBSTYLE_ArrowNext);

		$pageCountLabel = new Label_Text();
		$frame->addChild($pageCountLabel);
		$pageCountLabel->setHorizontalAlign($pageCountLabel::RIGHT)->setPosition($width * 0.40, $height * -0.44, 1)->setStyle($pageCountLabel::STYLE_TextTitle1)->setTextSize(1.3);

		$paging->addButtonControl($pagerNext)->addButtonControl($pagerPrev)->setLabel($pageCountLabel);

		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, 'SimpleStatsList');
	}


	/**
	 * Get the Chunk Index with the given Page Index
	 *
	 * @param int $pageIndex
	 * @return int
	 */
	private function getChunkIndexFromPageNumber($pageIndex, $totalPlayersCount) {
		$pagesCount = ceil($totalPlayersCount / self::MAX_PLAYERS_PER_PAGE);
		if ($pageIndex > $pagesCount - 1) {
			$pageIndex = $pagesCount - 1;
		}
		return floor($pageIndex / self::MAX_PAGES_PER_CHUNK);
	}

	/**
	 * Calculate the First Player Index to show for the given Chunk
	 *
	 * @param int $chunkIndex
	 * @return int
	 */
	private function getChunkStatsBeginIndex($chunkIndex) {
		return $chunkIndex * self::MAX_PAGES_PER_CHUNK * self::MAX_PLAYERS_PER_PAGE;
	}

	/**
	 * Called on ManialinkPageAnswer
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId, 3);
		if (count($actionArray) < 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];

		$playerLogin = $callback[1][1];
		$player      = $this->maniaControl->getPlayerManager()->getPlayer($playerLogin);
		switch ($action) {
			case self::ACTION_SORT_STATS:
				$this->showStatsList($player, $actionArray[2]);
				$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
				break;
			case ManialinkManager::ACTION_CLOSEWIDGET:
				$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
				break;
			default:
				if (substr($actionId, 0, strlen(self::ACTION_PAGING_CHUNKS)) === self::ACTION_PAGING_CHUNKS) {
					// Paging chunks
					$neededPage = (int) substr($actionId, strlen(self::ACTION_PAGING_CHUNKS));
					$this->showStatsList($player, PlayerManager::STAT_SERVERTIME, $neededPage - 1);
				}
		}
	}
}
