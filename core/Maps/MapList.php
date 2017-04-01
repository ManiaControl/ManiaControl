<?php

namespace ManiaControl\Maps;

use FML\Controls\Frame;
use FML\Controls\Gauge;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Button;
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
use ManiaControl\Logger;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\IconManager;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Utils\ColorUtil;
use ManiaControl\Utils\Formatter;
use Maniaplanet\DedicatedServer\Xmlrpc\ChangeInProgressException;
use Maniaplanet\DedicatedServer\Xmlrpc\FileException;
use Maniaplanet\DedicatedServer\Xmlrpc\NextMapException;
use Maniaplanet\DedicatedServer\Xmlrpc\NotInListException;
use MCTeam\CustomVotesPlugin;
use MCTeam\KarmaPlugin;

/**
 * MapList Widget Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class MapList implements ManialinkPageAnswerListener, CallbackListener {
	/*
	 * Constants
	 */
	const ACTION_UPDATE_MAP          = 'MapList.UpdateMap';
	const ACTION_REMOVE_MAP          = 'MapList.RemoveMap';
	const ACTION_SWITCH_MAP          = 'MapList.SwitchMap';
	const ACTION_START_SWITCH_VOTE   = 'MapList.StartMapSwitchVote';
	const ACTION_QUEUED_MAP          = 'MapList.QueueMap';
	const ACTION_UNQUEUE_MAP         = 'MapList.UnQueueMap';
	const ACTION_CHECK_UPDATE        = 'MapList.CheckUpdate';
	const ACTION_CLEAR_MAPQUEUE      = 'MapList.ClearMapQueue';
	const ACTION_PAGING_CHUNKS       = 'MapList.PagingChunk.';
	const MAX_MAPS_PER_PAGE          = 15;
	const MAX_PAGES_PER_CHUNK        = 2;
	const DEFAULT_KARMA_PLUGIN       = 'MCTeam\KarmaPlugin';
	const DEFAULT_CUSTOM_VOTE_PLUGIN = 'MCTeam\CustomVotesPlugin';
	const CACHE_CURRENT_PAGE         = 'CurrentPage';
	const WIDGET_NAME                = 'MapList';

	/*
	 * Private properties
	 */
	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;

	/**
	 * Construct a new map list instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		// Callbacks
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_CLOSED, $this, 'closeWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(ManialinkManager::CB_MAIN_WINDOW_OPENED, $this, 'handleWidgetOpened');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this, 'handleManialinkPageAnswer');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(MapQueue::CB_MAPQUEUE_CHANGED, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(MapManager::CB_MAPS_UPDATED, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(MapManager::CB_KARMA_UPDATED, $this, 'updateWidget');
		$this->maniaControl->getCallbackManager()->registerCallbackListener(Callbacks::BEGINMAP, $this, 'updateWidget');

		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_CHECK_UPDATE, $this, 'checkUpdates');
		$this->maniaControl->getManialinkManager()->registerManialinkPageAnswerListener(self::ACTION_CLEAR_MAPQUEUE, $this, 'clearMapQueue');
	}

	/**
	 * Clear the Map Queue
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function clearMapQueue(array $chatCallback, Player $player) {
		// Clears the Map Queue
		$this->maniaControl->getMapManager()->getMapQueue()->clearMapQueue($player);
	}

	/**
	 * Check for Map Updates
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function checkUpdates(array $chatCallback, Player $player) {
		// Update Mx Infos
		$this->maniaControl->getMapManager()->getMXManager()->fetchManiaExchangeMapInformation();

		// Reshow the Maplist
		$this->showMapList($player);
	}

	/**
	 * Display a MapList on the Screen
	 *
	 * @param Player $player
	 * @param Map[]  $mapList
	 * @param int    $pageIndex
	 */
	public function showMapList(Player $player, $mapList = null, $pageIndex = -1) {
		$width  = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$height = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsHeight();
		$buttonY = -$height / 2 + 9;

		if ($pageIndex < 0) {
			$pageIndex = (int) $player->getCache($this, self::CACHE_CURRENT_PAGE);
		}
		$player->setCache($this, self::CACHE_CURRENT_PAGE, $pageIndex);
		$queueBuffer = $this->maniaControl->getMapManager()->getMapQueue()->getQueueBuffer();

		$chunkIndex     = $this->getChunkIndexFromPageNumber($pageIndex);
		$mapsBeginIndex = $this->getChunkMapsBeginIndex($chunkIndex);

		// Get Maps
		if (!is_array($mapList)) {
			$mapList = $this->maniaControl->getMapManager()->getMaps();
		}
		$mapList = array_slice($mapList, $mapsBeginIndex, self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE);

		$totalMapsCount = $this->maniaControl->getMapManager()->getMapsCount();
		$pagesCount     = ceil($totalMapsCount / self::MAX_MAPS_PER_PAGE);

		// Create ManiaLink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);
		$paging->setCustomMaxPageNumber($pagesCount);
		$paging->setChunkActionAppendsPageNumber(true);
		$paging->setChunkActions(self::ACTION_PAGING_CHUNKS);

		// Main frame
		$frame = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultListFrame($script, $paging);
		$maniaLink->addChild($frame);

		// Admin Buttons
		if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapQueue::SETTING_PERMISSION_CLEAR_MAPQUEUE)
		) {
			// Clear Map-Queue
			$label = new Label_Button();
			$frame->addChild($label);
			$label->setText('Clear Map-Queue');
			$label->setTextSize(1);
			$label->setPosition($width / 2 - 8, $buttonY, 0.1);
			$label->setHorizontalAlign($label::RIGHT);

			$quad = new Quad_BgsPlayerCard();
			$frame->addChild($quad);
			$quad->setPosition($width / 2 - 5, $buttonY, 0.01);
			$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
			$quad->setHorizontalAlign($quad::RIGHT);
			$quad->setSize(29, 4);
			$quad->setAction(self::ACTION_CLEAR_MAPQUEUE);
		}

		if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_CHECK_UPDATE)
		) {
			$mxCheckForUpdatesX = $width / 2 - 37;
			$buttonWidth = 35;
			$iconSize = 3;
			// Check Update
			$label = new Label_Button();
			$frame->addChild($label);
			$label->setText('Check MX for Updates');
			$label->setPosition($mxCheckForUpdatesX - 1.5, $buttonY, 0.02);
			$label->setTextSize(1);
			$label->setWidth(30);
			$label->setHorizontalAlign($label::RIGHT);


			$quad = new Quad_BgsPlayerCard();
			$frame->addChild($quad);
			$quad->setPosition($mxCheckForUpdatesX, $buttonY, 0.01);
			$quad->setSubStyle($quad::SUBSTYLE_BgPlayerCardBig);
			$quad->setHorizontalAlign($quad::RIGHT);
			$quad->setSize($buttonWidth, 4);
			$quad->setAction(self::ACTION_CHECK_UPDATE);

			$mxQuad = new Quad();
			$frame->addChild($mxQuad);
			$mxQuad->setSize($iconSize, $iconSize);
			$mxQuad->setImageUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_GREEN));
			$mxQuad->setImageFocusUrl($this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_GREEN_MOVER));
			$mxQuad->setPosition($mxCheckForUpdatesX - $buttonWidth + 3, $buttonY);
			$mxQuad->setZ(0.02);
			$mxQuad->setAction(self::ACTION_CHECK_UPDATE);
		}

		if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)
		) {
			// Directory browser
			$browserButton = new Label_Button();
			$frame->addChild($browserButton);
			$browserButton->setPosition($width / -2 + 20, $buttonY, 0.01);
			$browserButton->setTextSize(1);
			$browserButton->setText('Directory Browser');

			$browserQuad = new Quad_BgsPlayerCard();
			$frame->addChild($browserQuad);
			$browserQuad->setPosition($width / -2 + 20, $buttonY, 0.01);
			$browserQuad->setSubStyle($browserQuad::SUBSTYLE_BgPlayerCardBig);
			$browserQuad->setSize(35, 4);
			$browserQuad->setAction(DirectoryBrowser::ACTION_SHOW);
		}

		// Headline
		$headFrame = new Frame();
		$frame->addChild($headFrame);
		$headFrame->setY($height / 2 - 5);
		$posX  = -$width / 2;
		$array = array('Id' => $posX + 5, 'Mx Id' => $posX + 10, 'Map Name' => $posX + 20, 'Author' => $posX + 68, 'Karma' => $posX + 115, 'Actions' => $width / 2 - 16);
		$this->maniaControl->getManialinkManager()->labelLine($headFrame, $array);

		// Predefine description Label
		$descriptionLabel = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultDescriptionLabel();
		$frame->addChild($descriptionLabel);

		$queuedMaps = $this->maniaControl->getMapManager()->getMapQueue()->getQueuedMapsRanking();
		/** @var KarmaPlugin $karmaPlugin */
		$karmaPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_KARMA_PLUGIN);

		$pageNumber = 1 + $chunkIndex * self::MAX_PAGES_PER_CHUNK;
		$paging->setStartPageNumber($pageIndex + 1);

		$index     = 0;
		$mapListId = 1 + $mapsBeginIndex;
		$posY      = $height / 2 - 10;
		$pageFrame = null;

		$currentMap       = $this->maniaControl->getMapManager()->getCurrentMap();
		$mxIcon           = $this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON);
		$mxIconHover      = $this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_MOVER);
		$mxIconGreen      = $this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_GREEN);
		$mxIconGreenHover = $this->maniaControl->getManialinkManager()->getIconManager()->getIcon(IconManager::MX_ICON_GREEN_MOVER);

		foreach ($mapList as $map) {
			/** @var Map $map */
			if ($index % self::MAX_MAPS_PER_PAGE === 0) {
				$pageFrame = new Frame();
				$frame->addChild($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPageControl($pageFrame, $pageNumber);
				$pageNumber++;
			}

			// Map Frame
			$mapFrame = new Frame();
			$pageFrame->addChild($mapFrame);
			$mapFrame->setY($posY);
			$mapFrame->setZ(0.1);

			if ($mapListId % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$mapFrame->addChild($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			if ($currentMap === $map) {
				$currentQuad = new Quad_Icons64x64_1();
				$mapFrame->addChild($currentQuad);
				$currentQuad->setX($posX + 3.5);
				$currentQuad->setZ(0.2);
				$currentQuad->setSize(4, 4);
				$currentQuad->setSubStyle($currentQuad::SUBSTYLE_ArrowBlue);
			}

			$mxId = '-';
			if (isset($map->mx->id)) {
				$mxId = $map->mx->id;

				$mxQuad = new Quad();
				$mapFrame->addChild($mxQuad);
				$mxQuad->setSize(3, 3);
				$mxQuad->setImageUrl($mxIcon);
				$mxQuad->setImageFocusUrl($mxIconHover);
				$mxQuad->setX($posX + 65);
				$mxQuad->setUrl($map->mx->pageurl);
				$mxQuad->setZ(0.01);
				$description = 'View ' . $map->getEscapedName() . ' on Mania-Exchange';
				$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);

				if ($map->updateAvailable()) {
					$mxQuad = new Quad();
					$mapFrame->addChild($mxQuad);
					$mxQuad->setSize(3, 3);
					$mxQuad->setImageUrl($mxIconGreen);
					$mxQuad->setImageFocusUrl($mxIconGreenHover);
					$mxQuad->setX($posX + 62);
					$mxQuad->setUrl($map->mx->pageurl);
					$mxQuad->setZ(0.01);
					$description = 'Update for ' . $map->getEscapedName() . ' available on Mania-Exchange!';
					$mxQuad->addTooltipLabelFeature($descriptionLabel, $description);

					// Update Button
					if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)
					) {
						$mxQuad->setAction(self::ACTION_UPDATE_MAP . '.' . $map->uid);
					}
				}
			}

			// Display Maps
			$array  = array($mapListId => $posX + 5, $mxId => $posX + 10, Formatter::stripDirtyCodes($map->name) => $posX + 20, $map->authorNick => $posX + 68);
			$labels = $this->maniaControl->getManialinkManager()->labelLine($mapFrame, $array);
			if (isset($labels[3])) {
				/** @var Label $label */
				$label       = $labels[3];
				$description = 'Click to checkout all maps by $<' . $map->authorLogin . '$>!';
				$label->setAction(MapCommands::ACTION_SHOW_AUTHOR . $map->authorLogin);
				$label->addTooltipLabelFeature($descriptionLabel, $description);
			}

			// TODO action detailed map info including mx info

			// Map-Queue-Map-Label
			if (isset($queuedMaps[$map->uid])) {
				$label = new Label_Text();
				$mapFrame->addChild($label);
				$label->setX($width / 2 - 13);
				$label->setZ(0.2);
				$label->setTextSize(1.5);
				$label->setText($queuedMaps[$map->uid]);
				$label->setTextColor('fff');

				// Checks if the Player who opened the Widget has queued the map
				$queuer = $this->maniaControl->getMapManager()->getMapQueue()->getQueuer($map->uid);
				if ($queuer && $queuer->login == $player->login) {
					$description = 'Remove ' . $map->getEscapedName() . ' from the Map Queue';
					$label->addTooltipLabelFeature($descriptionLabel, $description);
					$label->setAction(self::ACTION_UNQUEUE_MAP . '.' . $map->uid);
				} else {
					$description = $map->getEscapedName() . ' is on Map-Queue Position: ' . $queuedMaps[$map->uid];
					$label->addTooltipLabelFeature($descriptionLabel, $description);
				}
			} else {
				// Map-Queue-Map-Button
				$queueLabel = new Label_Button();
				$mapFrame->addChild($queueLabel);
				$queueLabel->setX($width / 2 - 13);
				$queueLabel->setZ(0.2);
				$queueLabel->setSize(3, 3);
				$queueLabel->setText('+');

				if (in_array($map->uid, $queueBuffer)) {
					if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapQueue::SETTING_PERMISSION_CLEAR_MAPQUEUE)
					) {
						$queueLabel->setAction(self::ACTION_QUEUED_MAP . '.' . $map->uid);
					}
					$queueLabel->setTextColor('f00');
					$description = $map->getEscapedName() . ' has recently been played!';
					$queueLabel->addTooltipLabelFeature($descriptionLabel, $description);
				} else {
					$queueLabel->setTextColor('09f');
					$queueLabel->setAction(self::ACTION_QUEUED_MAP . '.' . $map->uid);
					$description = 'Add ' . $map->getEscapedName() . ' to the Map Queue';
					$queueLabel->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}

			if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_REMOVE_MAP)
			) {
				// remove map button
				$removeButton = new Label_Button();
				$mapFrame->addChild($removeButton);
				$removeButton->setX($width / 2 - 5);
				$removeButton->setZ(0.2);
				$removeButton->setSize(3, 3);
				$removeButton->setTextSize(1);
				$removeButton->setText('x');
				$removeButton->setTextColor('a00');

				$confirmFrame = $this->buildConfirmFrame($maniaLink, $posY, $map->uid, true);
				$removeButton->addToggleFeature($confirmFrame);
				$description = 'Remove Map: ' . $map->getEscapedName();
				$removeButton->addTooltipLabelFeature($descriptionLabel, $description);
			}

			if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)
			) {
				// Switch to button
				$switchLabel = new Label_Button();
				$mapFrame->addChild($switchLabel);
				$switchLabel->setX($width / 2 - 9);
				$switchLabel->setZ(0.2);
				$switchLabel->setSize(3, 3);
				$switchLabel->setTextSize(2);
				$switchLabel->setText('»');
				$switchLabel->setTextColor('0f0');

				$confirmFrame = $this->buildConfirmFrame($maniaLink, $posY, $map->uid);
				$switchLabel->addToggleFeature($confirmFrame);

				$description = 'Switch Directly to Map: ' . $map->getEscapedName();
				$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
			}
			if ($this->maniaControl->getPluginManager()->isPluginActive(self::DEFAULT_CUSTOM_VOTE_PLUGIN)
			) {
				if ($this->maniaControl->getAuthenticationManager()->checkPermission($player, MapManager::SETTING_PERMISSION_ADD_MAP)
				) {
					// Switch Map Voting for Admins
					$switchQuad = new Quad_UIConstruction_Buttons();
					$mapFrame->addChild($switchQuad);
					$switchQuad->setX($width / 2 - 17);
					$switchQuad->setZ(0.2);
					$switchQuad->setSubStyle($switchQuad::SUBSTYLE_Validate_Step2);
					$switchQuad->setSize(3.8, 3.8);
					$switchQuad->setAction(self::ACTION_START_SWITCH_VOTE . '.' . $map->uid);
					$description = 'Start Map-Switch Vote: $<' . $map->name . '$>';
					$switchQuad->addTooltipLabelFeature($descriptionLabel, $description);
				} else {
					// Switch Map Voting for Player
					$switchLabel = new Label_Button();
					$mapFrame->addChild($switchLabel);
					$switchLabel->setX($width / 2 - 7);
					$switchLabel->setZ(0.2);
					$switchLabel->setSize(3, 3);
					$switchLabel->setTextSize(2);
					$switchLabel->setText('»');
					$switchLabel->setTextColor('0f0');
					$switchLabel->setAction(self::ACTION_START_SWITCH_VOTE . '.' . $map->uid);
					$description = 'Start Map-Switch Vote: ' . $map->getEscapedName();
					$switchLabel->addTooltipLabelFeature($descriptionLabel, $description);
				}
			}

			// Display Karma bar
			if ($karmaPlugin) {
				$displayMxKarma = $this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_WIDGET_DISPLAY_MX);

				//Display Mx Karma
				if ($displayMxKarma && $map->mx) {
					$karma = $map->mx->ratingVoteAverage / 100;
					$votes = array("count" => $map->mx->ratingVoteCount);

					//Display Local Karma
				} else {
					$karma = $karmaPlugin->getMapKarma($map);
					$votes = $karmaPlugin->getMapVotes($map);
				}

				if (is_numeric($karma) && $votes['count'] > 0) {
					if ($this->maniaControl->getSettingManager()->getSettingValue($karmaPlugin, $karmaPlugin::SETTING_NEWKARMA)
					) {
						$karmaText = '  ' . round($karma * 100.) . '% (' . $votes['count'] . ')';
					} else {
						$min  = 0;
						$plus = 0;
						foreach ($votes as $vote) {
							if (isset($vote->vote)) {
								if ($vote->vote !== 0.5) {
									if ($vote->vote < 0.5) {
										$min = $min + $vote->count;
									} else {
										$plus = $plus + $vote->count;
									}
								}
							}
						}
						$endKarma  = $plus - $min;
						$karmaText = '  ' . $endKarma . ' (' . $votes['count'] . 'x / ' . round($karma * 100.) . '%)';
					}

					$karmaGauge = new Gauge();
					$mapFrame->addChild($karmaGauge);
					$karmaGauge->setZ(2);
					$karmaGauge->setX($posX + 120);
					$karmaGauge->setSize(20, 9);
					$karmaGauge->setDrawBackground(false);
					$karma = floatval($karma);
					$karmaGauge->setRatio($karma + 0.15 - $karma * 0.15);
					$karmaColor = ColorUtil::floatToStatusColor($karma);
					$karmaGauge->setColor($karmaColor . '9');

					$karmaLabel = new Label();
					$mapFrame->addChild($karmaLabel);
					$karmaLabel->setZ(2);
					$karmaLabel->setX($posX + 120);
					$karmaLabel->setSize(20 * 0.9, 5);
					$karmaLabel->setTextSize(0.9);
					$karmaLabel->setTextColor('000');
					$karmaLabel->setText($karmaText);
				}
			}

			$posY -= 4;
			$mapListId++;
			$index++;
		}

		$this->maniaControl->getManialinkManager()->displayWidget($maniaLink, $player, self::WIDGET_NAME);
	}

	/**
	 * Get the Chunk Index with the given Page Index
	 *
	 * @param int $pageIndex
	 * @return int
	 */
	private function getChunkIndexFromPageNumber($pageIndex) {
		$mapsCount  = $this->maniaControl->getMapManager()->getMapsCount();
		$pagesCount = ceil($mapsCount / self::MAX_MAPS_PER_PAGE);
		if ($pageIndex > $pagesCount - 1) {
			$pageIndex = $pagesCount - 1;
		}
		return floor($pageIndex / self::MAX_PAGES_PER_CHUNK);
	}

	/**
	 * Calculate the First Map Index to show for the given Chunk
	 *
	 * @param int $chunkIndex
	 * @return int
	 */
	private function getChunkMapsBeginIndex($chunkIndex) {
		return $chunkIndex * self::MAX_PAGES_PER_CHUNK * self::MAX_MAPS_PER_PAGE;
	}

	/**
	 * Builds the confirmation frame
	 *
	 * @param ManiaLink $maniaLink
	 * @param float     $posY
	 * @param bool      $mapUid
	 * @param bool      $remove
	 * @return Frame
	 */
	public function buildConfirmFrame(Manialink $maniaLink, $posY, $mapUid, $remove = false) {
		// TODO: get rid of the confirm frame to decrease xml size & network usage
		// SUGGESTION: just send them as own manialink again on clicking?

		$width        = $this->maniaControl->getManialinkManager()->getStyleManager()->getListWidgetsWidth();
		$quadStyle    = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowStyle();
		$quadSubstyle = $this->maniaControl->getManialinkManager()->getStyleManager()->getDefaultMainWindowSubStyle();

		$confirmFrame = new Frame();
		$maniaLink->addChild($confirmFrame);
		$confirmFrame->setPosition($width / 2 + 6, $posY);
		$confirmFrame->setVisible(false);
		$confirmFrame->setZ(ManialinkManager::MAIN_MANIALINK_Z_VALUE);

		$quad = new Quad();
		$confirmFrame->addChild($quad);
		$quad->setStyles($quadStyle, $quadSubstyle);
		$quad->setSize(12, 4);
		$quad->setZ(-0.5);

		$quad = new Quad_BgsPlayerCard();
		$confirmFrame->addChild($quad);
		$quad->setSubStyle($quad::SUBSTYLE_BgCardSystem);
		$quad->setSize(11, 3.5);
		$quad->setZ(-0.3);

		$label = new Label_Button();
		$confirmFrame->addChild($label);
		$label->setText('Sure?');
		$label->setTextSize(1);
		$label->setScale(0.90);
		$label->setX(-1.3);

		$buttLabel = new Label_Button();
		$confirmFrame->addChild($buttLabel);
		$buttLabel->setPosition(3.2, 0.4, 0.2);
		$buttLabel->setSize(3, 3);

		if ($remove) {
			$buttLabel->setTextSize(1);
			$buttLabel->setTextColor('a00');
			$buttLabel->setText('x');
			$quad->setAction(self::ACTION_REMOVE_MAP . '.' . $mapUid);
		} else {
			$buttLabel->setTextSize(2);
			$buttLabel->setTextColor('0f0');
			$buttLabel->setText('»');
			$quad->setAction(self::ACTION_SWITCH_MAP . '.' . $mapUid);
		}
		return $confirmFrame;
	}

	/**
	 * Unset the player if he opened another Main Widget
	 *
	 * @param Player $player
	 * @param string $openedWidget
	 */
	public function handleWidgetOpened(Player $player, $openedWidget) {
		// unset when another main widget got opened
		if ($openedWidget !== self::WIDGET_NAME) {
			$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
		}
	}

	/**
	 * Close the widget
	 *
	 * @param Player $player
	 */
	public function closeWidget(Player $player) {
		// TODO: resolve duplicate with 'playerCloseWidget'
		$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
	}

	/**
	 * Handle ManialinkPageAnswer Callback
	 *
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback) {
		$actionId    = $callback[1][2];
		$actionArray = explode('.', $actionId);
		if (count($actionArray) <= 2) {
			return;
		}

		$action = $actionArray[0] . '.' . $actionArray[1];
		$login  = $callback[1][1];
		$player = $this->maniaControl->getPlayerManager()->getPlayer($login);
		$mapUid = $actionArray[2];

		switch ($action) {
			case self::ACTION_UPDATE_MAP:
				$this->maniaControl->getMapManager()->updateMap($player, $mapUid);
				$this->showMapList($player);
				break;
			case self::ACTION_REMOVE_MAP:
				try {
					$this->maniaControl->getMapManager()->removeMap($player, $mapUid);
				} catch (FileException $e) {
					$this->maniaControl->getChat()->sendException($e, $player);
				}
				break;
			case self::ACTION_SWITCH_MAP:
				// Don't queue on Map-Change
				$this->maniaControl->getMapManager()->getMapQueue()->dontQueueNextMapChange();
				try {
					$this->maniaControl->getClient()->jumpToMapIdent($mapUid);
				} catch (NextMapException $exception) {
					$this->maniaControl->getChat()->sendError('Error on Jumping to Map Ident: ' . $exception->getMessage(), $player);
					break;
				} catch (NotInListException $exception) {
					// TODO: "Map not found." -> how is that possible?
					$this->maniaControl->getChat()->sendError('Error on Jumping to Map Ident: ' . $exception->getMessage(), $player);
					break;
				}

				$map = $this->maniaControl->getMapManager()->getMapByUid($mapUid);

				$message = $player->getEscapedNickname() . ' skipped to Map $z' . $map->getEscapedName() . '!';
				$this->maniaControl->getChat()->sendSuccess($message);
				Logger::logInfo($message, true);

				$this->playerCloseWidget($player);
				break;
			case self::ACTION_START_SWITCH_VOTE:
				/** @var CustomVotesPlugin $votesPlugin */
				$votesPlugin = $this->maniaControl->getPluginManager()->getPlugin(self::DEFAULT_CUSTOM_VOTE_PLUGIN);
				$map         = $this->maniaControl->getMapManager()->getMapByUid($mapUid);

				$message = $player->getEscapedNickname() . '$s started a vote to switch to ' . $map->getEscapedName() . '!';

				$votesPlugin->defineVote('switchmap', 'Goto ' . $map->name, true, $message)->setStopCallback(Callbacks::ENDMAP);

				$votesPlugin->startVote($player, 'switchmap', function ($result) use (&$votesPlugin, &$map) {
					$votesPlugin->undefineVote('switchmap');

					//Don't queue on Map-Change
					$this->maniaControl->getMapManager()->getMapQueue()->dontQueueNextMapChange();

					try {
						$this->maniaControl->getClient()->JumpToMapIdent($map->uid);
					} catch (NextMapException $exception) {
						return;
					} catch (NotInListException $exception) {
						return;
					} catch (ChangeInProgressException $exception) {
						// TODO: delay skip if change is in progress
						return;
					}

					$this->maniaControl->getChat()->sendInformation('$sVote Successful -> Map switched!');
				});
				break;
			case self::ACTION_QUEUED_MAP:
				$this->maniaControl->getMapManager()->getMapQueue()->addMapToMapQueue($callback[1][1], $mapUid);
				$this->showMapList($player);
				break;
			case self::ACTION_UNQUEUE_MAP:
				$this->maniaControl->getMapManager()->getMapQueue()->removeFromMapQueue($player, $mapUid);
				$this->showMapList($player);
				break;
			default:
				if (substr($actionId, 0, strlen(self::ACTION_PAGING_CHUNKS)) === self::ACTION_PAGING_CHUNKS) {
					// Paging chunks
					$neededPage = (int) substr($actionId, strlen(self::ACTION_PAGING_CHUNKS));
					$this->showMapList($player, null, $neededPage - 1);
				}
				break;
		}
	}

	/**
	 * Close the widget for
	 *
	 * @param Player $player
	 */
	public function playerCloseWidget(Player $player) {
		$player->destroyCache($this, self::CACHE_CURRENT_PAGE);
		$this->maniaControl->getManialinkManager()->closeWidget($player);
	}

	/**
	 * Reopen the widget on Map Begin, MapListChanged, etc.
	 */
	public function updateWidget() {
		$players = $this->maniaControl->getPlayerManager()->getPlayers();
		foreach ($players as $player) {
			$currentPage = $player->getCache($this, self::CACHE_CURRENT_PAGE);
			if ($currentPage !== null) {
				$this->showMapList($player, null, $currentPage);
			}
		}
	}
} 
