<?php

namespace MCTeam;

use FML\Controls\Frame;
use FML\Controls\Quads\Quad_BgsPlayerCard;
use FML\ManiaLink;
use FML\Script\Features\Paging;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\Callbacks;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Statistics\StatisticCollector;
use ManiaControl\Statistics\StatisticManager;
use Maniaplanet\DedicatedServer\Structures\AbstractStructure;

/**
 * ManiaControl ServerRanking Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ServerRankingPlugin implements Plugin, CallbackListener, CommandListener {
	/*
	 * Constants
	 */
	const PLUGIN_ID                       = 6;
	const PLUGIN_VERSION                  = 0.1;
	const PLUGIN_NAME                     = 'Server Ranking Plugin';
	const PLUGIN_AUTHOR                   = 'MCTeam';
	const TABLE_RANK                      = 'mc_rank';
	const RANKING_TYPE_RECORDS            = 'Records';
	const RANKING_TYPE_RATIOS             = 'Ratios';
	const RANKING_TYPE_POINTS             = 'Points';
	const SETTING_MIN_RANKING_TYPE        = 'ServerRankings Type Records/Points/Ratios';
	const SETTING_MIN_HITS_RATIO_RANKING  = 'Min Hits on Ratio Rankings';
	const SETTING_MIN_HITS_POINTS_RANKING = 'Min Hits on Points Rankings';
	const SETTING_MIN_REQUIRED_RECORDS    = 'Minimum amount of records required on Records Ranking';
	const SETTING_MAX_STORED_RECORDS      = 'Maximum number of records per map for calculations';
	const CB_RANK_BUILT                   = 'ServerRankingPlugin.RankBuilt';

	/**
	 * Private Properties
	 */
	/** @var ManiaControl $maniaControl * */
	private $maniaControl = null;
	private $recordCount = 0;

	/**
	 * @see \ManiaControl\Plugins\Plugin::prepare()
	 */
	public static function prepare(ManiaControl $maniaControl) {
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return "ServerRanking Plugin, ServerRanking by an avg build from the records, per count of points, or by a multiplication from Kill/Death Ratio and Laser accuracy";
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::load()
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->initTables();

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING, 100);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_HITS_POINTS_RANKING, 15);

		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_REQUIRED_RECORDS, 3);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_MAX_STORED_RECORDS, 50);

		$script = $this->maniaControl->client->getScriptName();

		if ($this->maniaControl->mapManager->getCurrentMap()->getGame() === 'tm') {
			//TODO also add obstacle here as default
			$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_RECORDS);
		} else if ($script["CurrentValue"] === 'InstaDM.Script.txt') {
			$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_RATIOS);
		} else {
			$this->maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_POINTS);
		}

		//Check if the type is Correct
		$type = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_RANKING_TYPE);
		if (!$this->isValidRankingType($type)) {
			$error = 'Ranking Type is not correct, possible values(' . self::RANKING_TYPE_RATIOS . ', ' . self::RANKING_TYPE_POINTS . ', ' . self::RANKING_TYPE_POINTS . ')';
			throw new \Exception($error);
		}

		//Register CallbackListeners
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERCONNECT, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(Callbacks::ENDMAP, $this, 'handleEndMap');

		//Register CommandListener
		$this->maniaControl->commandManager->registerCommandListener('rank', $this, 'command_showRank', false, 'Shows your current ServerRank.');
		$this->maniaControl->commandManager->registerCommandListener('nextrank', $this, 'command_nextRank', false, 'Shows the person in front of you in the ServerRanking.');
		$this->maniaControl->commandManager->registerCommandListener(array('topranks', 'top100'), $this, 'command_topRanks', false, 'Shows an overview of the best-ranked 100 players.');

		// TODO: only update records count
		$this->resetRanks();
	}

	/**
	 * Create necessary database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RANK . "` (
				`PlayerIndex` int(11) NOT NULL,
				`Rank` int(11) NOT NULL,
				`Avg` float NOT NULL,
				KEY `PlayerIndex` (`PlayerIndex`),
				UNIQUE `Rank` (`Rank`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='ServerRanking';";
		$mysqli->query($query);
		if ($mysqli->error) {
			throw new \Exception($mysqli->error);
		}
	}

	/**
	 * Check if the given Ranking Type is valid
	 *
	 * @param string $rankingType
	 * @return bool
	 */
	private function isValidRankingType($rankingType) {
		return in_array($rankingType, $this->getRankingTypes());
	}

	/**
	 * Get the possible Ranking Types
	 *
	 * @return string[]
	 */
	private function getRankingTypes() {
		return array(self::RANKING_TYPE_POINTS, self::RANKING_TYPE_RATIOS, self::RANKING_TYPE_RECORDS);
	}

	/**
	 * Resets and rebuilds the Ranking
	 */
	private function resetRanks() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Erase old Average Data
		$query = "TRUNCATE TABLE `" . self::TABLE_RANK . "`;";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
		}

		$type = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_RANKING_TYPE);

		switch ($type) {
			case self::RANKING_TYPE_RATIOS:
				$minHits = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_HITS_RATIO_RANKING);

				$hits            = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_HIT, -1, $minHits);
				$killDeathRatios = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_KD_RATIO);
				$accuracies      = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_LASER_ACC);

				$ranks = array();
				foreach ($hits as $login => $hitCount) {
					if (!isset($killDeathRatios[$login]) || !isset($accuracies[$login])) {
						continue;
					}
					$ranks[$login] = $killDeathRatios[$login] * $accuracies[$login] * 1000;
				}

				arsort($ranks);

				break;
			case self::RANKING_TYPE_POINTS:
				$minHits = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_HITS_POINTS_RANKING);
				$ranks   = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_HIT, -1, $minHits);
				break;
			case self::RANKING_TYPE_RECORDS:
				// TODO: verify workable status
				/** @var LocalRecordsPlugin $localRecordsPlugin */
				$localRecordsPlugin = $this->maniaControl->pluginManager->getPlugin(__NAMESPACE__ . '\LocalRecordsPlugin');
				if (!$localRecordsPlugin) {
					return;
				}

				$requiredRecords = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_REQUIRED_RECORDS);
				$maxRecords      = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MAX_STORED_RECORDS);

				$query = 'SELECT playerIndex, COUNT(*) AS Cnt
						FROM ' . LocalRecordsPlugin::TABLE_RECORDS . '
						GROUP BY PlayerIndex
						HAVING Cnt >=' . $requiredRecords;

				$result  = $mysqli->query($query);
				$players = array();
				while ($row = $result->fetch_object()) {
					$players[$row->playerIndex] = array(0, 0); //sum, count
				}
				$result->free_result();

				$maps = $this->maniaControl->mapManager->getMaps();
				foreach ($maps as $map) {
					$records = $localRecordsPlugin->getLocalRecords($map, $maxRecords);

					$index = 1;
					foreach ($records as $record) {
						if (isset($players[$record->playerIndex])) {
							$players[$record->playerIndex][0] += $index;
							$players[$record->playerIndex][1]++;
						}
						$index++;
					}
				}

				$mapCount = count($maps);

				//compute each players new average score
				$ranks = array();
				foreach ($players as $playerIndex => $val) {
					$sum = $val[0];
					$cnt = $val[1];
					// ranked maps sum + $maxRecs rank for all remaining maps
					$ranks[$playerIndex] = ($sum + ($mapCount - $cnt) * $maxRecords) / $mapCount;
				}

				asort($ranks);
				break;
		}

		if (empty($ranks)) {
			return;
		}

		$this->recordCount = count($ranks);

		//Compute each player's new average score
		$query = "INSERT INTO `" . self::TABLE_RANK . "` VALUES ";
		$index = 1;

		foreach ($ranks as $playerIndex => $rankValue) {
			$query .= '(' . $playerIndex . ',' . $index . ',' . $rankValue . '),';
			$index++;
		}
		$query = substr($query, 0, strlen($query) - 1); // strip trailing ','

		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
		}
	}

	/**
	 * @see \ManiaControl\Plugins\Plugin::unload()
	 */
	public function unload() {
	}

	/**
	 * Handle PlayerConnect callback
	 *
	 * @param Player $player
	 */
	public function handlePlayerConnect(Player $player) {
		$this->showRank($player);
		$this->showNextRank($player);
	}

	/**
	 * Shows the serverRank to a certain Player
	 *
	 * @param Player $player
	 */
	public function showRank(Player $player) {
		$rankObj = $this->getRank($player);

		$type = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_RANKING_TYPE);

		$message = '';
		if ($rankObj) {
			switch ($type) {
				case self::RANKING_TYPE_RATIOS:
					$killDeathRatio = $this->maniaControl->statisticManager->getStatisticData(StatisticManager::SPECIAL_STAT_KD_RATIO, $player->index);
					$accuracy       = $this->maniaControl->statisticManager->getStatisticData(StatisticManager::SPECIAL_STAT_LASER_ACC, $player->index);
					$message        = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> (K/D: $<$fff' . round($killDeathRatio, 2) . '$> Acc: $<$fff' . round($accuracy * 100) . '%$>)';
					break;
				case self::RANKING_TYPE_POINTS:
					$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> Points: $fff' . $rankObj->avg;
					break;
				case self::RANKING_TYPE_RECORDS:
					$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> Avg: $fff' . round($rankObj->avg, 2);
			}
		} else {
			switch ($type) {
				case self::RANKING_TYPE_RATIOS:
					$minHits = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_HITS_RATIO_RANKING);
					$message = '$0f3 You must make $<$fff' . $minHits . '$> Hits on this server before receiving a rank...';
					break;
				case self::RANKING_TYPE_POINTS:
					$minPoints = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_HITS_POINTS_RANKING);
					$message   = '$0f3 You must make $<$fff' . $minPoints . '$> Hits on this server before receiving a rank...';
					break;
				case self::RANKING_TYPE_RECORDS:
					$minRecords = $this->maniaControl->settingManager->getSettingValue($this, self::SETTING_MIN_REQUIRED_RECORDS);
					$message    = '$0f3 You need $<$fff' . $minRecords . '$> Records on this server before receiving a rank...';
			}
		}
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Get the Rank Object for the given Player
	 *
	 * @param Player $player
	 * @return Rank
	 */
	private function getRank(Player $player) {
		//TODO setting global from db or local
		$mysqli = $this->maniaControl->database->mysqli;

		$query  = "SELECT * FROM `" . self::TABLE_RANK . "`
				WHERE `PlayerIndex` = {$player->index};";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		if ($result->num_rows <= 0) {
			$result->free_result();
			return null;
		}

		$row = $result->fetch_array();
		$result->free_result();
		return Rank::fromArray($row);
	}

	/**
	 * Show which Player is next ranked to you
	 *
	 * @param Player $player
	 * @return bool
	 */
	public function showNextRank(Player $player) {
		$rankObject = $this->getRank($player);
		if (!$rankObject) {
			return false;
		}

		if ($rankObject->rank > 1) {
			$nextRank   = $this->getNextRank($player);
			$nextPlayer = $this->maniaControl->playerManager->getPlayerByIndex($nextRank->playerIndex);
			$message    = '$0f3The next better ranked player is $fff' . $nextPlayer->nickname;
		} else {
			$message = '$0f3No better ranked player :-)';
		}
		$this->maniaControl->chat->sendChat($message, $player->login);

		return true;
	}

	/**
	 * Get the Next Ranked Player
	 *
	 * @param Player $player
	 * @return Rank
	 */
	private function getNextRank(Player $player) {
		$rankObject = $this->getRank($player);
		if (!$rankObject) {
			return null;
		}
		$nextRank = $rankObject->rank - 1;

		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "SELECT * FROM `" . self::TABLE_RANK . "`
				WHERE `Rank` = {$nextRank}";
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return null;
		}
		if ($result->num_rows <= 0) {
			$result->free();
			return null;
		}

		$row = $result->fetch_array();
		$result->free();
		return Rank::fromArray($row);
	}

	/**
	 * Show Ranks on Map End
	 */
	public function handleEndMap() {
		$this->resetRanks();

		foreach ($this->maniaControl->playerManager->getPlayers() as $player) {
			if ($player->isFakePlayer()) {
				continue;
			}
			$this->showRank($player);
			$this->showNextRank($player);
		}

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_RANK_BUILT);
	}

	/**
	 * Shows the current Server-Rank
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_showRank(array $chatCallback, Player $player) {
		$this->showRank($player);
	}

	/**
	 * Show the next better ranked player
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_nextRank(array $chatCallback, Player $player) {
		if (!$this->showNextRank($player)) {
			$message = '$0f3You need to have a ServerRank first!';
			$this->maniaControl->chat->sendChat($message, $player->login);
		}
	}

	/**
	 * Handles /topranks|top100 command
	 *
	 * @param array  $chatCallback
	 * @param Player $player
	 */
	public function command_topRanks(array $chatCallback, Player $player) {
		$this->showTopRanksList($player);
	}

	/**
	 * Provide a ManiaLink window with the top ranks to the player
	 *
	 * @param Player $player
	 */
	private function showTopRanksList(Player $player) {
		$query  = "SELECT * FROM `" . self::TABLE_RANK . "` ORDER BY `Rank` ASC LIMIT 0, 100";
		$mysqli = $this->maniaControl->database->mysqli;
		$result = $mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error);
			return;
		}

		$width  = $this->maniaControl->manialinkManager->styleManager->getListWidgetsWidth();
		$height = $this->maniaControl->manialinkManager->styleManager->getListWidgetsHeight();

		// create manialink
		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);
		$script    = $maniaLink->getScript();
		$paging    = new Paging();
		$script->addFeature($paging);

		// Main frame
		$frame = $this->maniaControl->manialinkManager->styleManager->getDefaultListFrame($script, $paging);
		$maniaLink->add($frame);

		// Start offsets
		$posX = -$width / 2;
		$posY = $height / 2;

		//Predefine description Label
		$descriptionLabel = $this->maniaControl->manialinkManager->styleManager->getDefaultDescriptionLabel();
		$frame->add($descriptionLabel);

		// Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($posY - 5);
		$array = array('$oRank' => $posX + 5, '$oNickname' => $posX + 18, '$oAverage' => $posX + 70);
		$this->maniaControl->manialinkManager->labelLine($headFrame, $array);

		$index = 1;
		$posY -= 10;
		$pageFrame = null;

		while ($rankedPlayer = $result->fetch_object()) {
			if ($index % 15 === 1) {
				$pageFrame = new Frame();
				$frame->add($pageFrame);
				$posY = $height / 2 - 10;
				$paging->addPage($pageFrame);
			}

			$playerFrame = new Frame();
			$pageFrame->add($playerFrame);
			$playerFrame->setY($posY);

			if ($index % 2 !== 0) {
				$lineQuad = new Quad_BgsPlayerCard();
				$playerFrame->add($lineQuad);
				$lineQuad->setSize($width, 4);
				$lineQuad->setSubStyle($lineQuad::SUBSTYLE_BgPlayerCardBig);
				$lineQuad->setZ(0.001);
			}

			$playerObject = $this->maniaControl->playerManager->getPlayerByIndex($rankedPlayer->PlayerIndex);
			$array        = array($rankedPlayer->Rank => $posX + 5, $playerObject->nickname => $posX + 18, (string)round($rankedPlayer->Avg, 2) => $posX + 70);
			$this->maniaControl->manialinkManager->labelLine($playerFrame, $array);

			$posY -= 4;
			$index++;
		}

		// Render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'TopRanks');
	}
}

/**
 * Rank Structure
 */
// TODO: extract class to own file
class Rank extends AbstractStructure {
	public $playerIndex;
	public $rank;
	public $avg;
}