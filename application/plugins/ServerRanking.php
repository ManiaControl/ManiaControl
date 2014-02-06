<?php

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;
use ManiaControl\Statistics\StatisticCollector;
use ManiaControl\Statistics\StatisticManager;
use Maniaplanet\DedicatedServer\Structures\AbstractStructure;

class ServerRankingPlugin implements Plugin, CallbackListener, CommandListener {
	/**
	 * Constants
	 */
	const PLUGIN_ID                      = 11;
	const PLUGIN_VERSION                 = 0.1;
	const PLUGIN_NAME                    = 'ServerRankingPlugin';
	const PLUGIN_AUTHOR                  = 'kremsy';
	const TABLE_RANK                     = 'mc_rank';
	const RANKING_TYPE_RECORDS           = 'Records';
	const RANKING_TYPE_RATIOS            = 'Ratios';
	const RANKING_TYPE_HITS              = 'Hits';
	const SETTING_MIN_RANKING_TYPE       = 'ServerRankings Type Records/Hits/Ratios';
	const SETTING_MIN_HITS_RATIO_RANKING = 'Min Hits on Ratio Rankings';
	const SETTING_MIN_HITS_HITS_RANKING  = 'Min Hits on Hits Rankings';
	const SETTING_MIN_REQUIRED_RECORDS   = 'Minimum amount of records required on Records Ranking';
	const SETTING_MAX_STORED_RECORDS     = 'Maximum number of records per map for calculations';
	const CB_RANK_BUILT                  = 'ServerRankingPlugin.RankBuilt';

	/**
	 * Private Properties
	 */
	/** @var maniaControl $maniaControl * */
	private $maniaControl = null;
	private $recordCount = 0;

	/**
	 * Prepares the Plugin
	 *
	 * @param ManiaControl $maniaControl
	 * @return mixed
	 */
	public static function prepare(ManiaControl $maniaControl) {
		//Todo
	}

	/**
	 * Load the plugin
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 * @return bool
	 */
	public function load(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

		$this->initTables();

		$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING, 100);
		$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_HITS_HITS_RANKING, 15);

		$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_REQUIRED_RECORDS, 3);
		$maniaControl->settingManager->initSetting($this, self::SETTING_MAX_STORED_RECORDS, 50);

		$titleId     = $this->maniaControl->server->titleId;
		$titlePrefix = strtolower(substr($titleId, 0, 2));

		if ($titlePrefix == 'tm') { //TODO also add obstacle here as default
			$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_RECORDS);
		} else if ($this->maniaControl->client->getScriptName()["CurrentValue"] == "InstaDM.Script.txt") {
			$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_RATIOS);
		} else {
			$maniaControl->settingManager->initSetting($this, self::SETTING_MIN_RANKING_TYPE, self::RANKING_TYPE_HITS);
		}

		//Check if the type is Correct
		$type = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_RANKING_TYPE);
		if ($type != self::RANKING_TYPE_RECORDS && $type != self::RANKING_TYPE_HITS && $type != self::RANKING_TYPE_RATIOS) {
			$error = 'Ranking Type is not correct, possible values(' . self::RANKING_TYPE_RATIOS . ', ' . self::RANKING_TYPE_HITS . ', ' . self::RANKING_TYPE_HITS . ')';
			throw new Exception($error);
		}

		//Register CallbackListeners
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_ENDMAP, $this, 'handleEndMap');

		//Register CommandListener
		$this->maniaControl->commandManager->registerCommandListener('rank', $this, 'command_showRank', false);
		$this->maniaControl->commandManager->registerCommandListener('nextrank', $this, 'command_nextRank', false);

		$this->resetRanks(); //TODO only update records count
	}

	/**
	 * Unload the plugin and its resources
	 */
	public function unload() {
		$this->maniaControl->callbackManager->unregisterCallbackListener($this);
		$this->maniaControl->commandManager->unregisterCommandListener($this);
	}

	/**
	 * Get plugin id
	 *
	 * @return int
	 */
	public static function getId() {
		return self::PLUGIN_ID;
	}

	/**
	 * Get Plugin Name
	 *
	 * @return string
	 */
	public static function getName() {
		return self::PLUGIN_NAME;
	}

	/**
	 * Get Plugin Version
	 *
	 * @return float
	 */
	public static function getVersion() {
		return self::PLUGIN_VERSION;
	}

	/**
	 * Get Plugin Author
	 *
	 * @return string
	 */
	public static function getAuthor() {
		return self::PLUGIN_AUTHOR;
	}

	/**
	 * Get Plugin Description
	 *
	 * @return string
	 */
	public static function getDescription() {
		return "ServerRanking Plugin, Serverranking by an avg build from the records, per count of hits, or by a multiplication from Kill/Death Ratio and Laser accuracy";
	}

	/**
	 * Create necessary database tables
	 */
	private function initTables() {
		$mysqli = $this->maniaControl->database->mysqli;
		$query  = "CREATE TABLE IF NOT EXISTS `" . self::TABLE_RANK . "` (
		           `PlayerIndex` mediumint(9) NOT NULL default 0,
		           `Rank` mediumint(9) NOT NULL default 0,
		           `Avg` float NOT NULL default 0,
		           KEY `PlayerIndex` (`PlayerIndex`),
		           UNIQUE `Rank` (`Rank`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Mania Control Serverranking';";
		$mysqli->query($query);
		if ($mysqli->error) {
			trigger_error($mysqli->error, E_USER_ERROR);
		}
	}

	/**
	 * Resets and rebuilds the Ranking
	 */
	private function resetRanks() {
		$mysqli = $this->maniaControl->database->mysqli;

		// Erase old Average Data
		$mysqli->query('TRUNCATE TABLE ' . self::TABLE_RANK);
		$type = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_RANKING_TYPE);

		switch($type) {
			case self::RANKING_TYPE_RATIOS:
				$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING);

				$hits            = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_HIT, -1, $minHits);
				$killDeathRatios = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_KD_RATIO);
				$accuracies      = $this->maniaControl->statisticManager->getStatsRanking(StatisticManager::SPECIAL_STAT_LASER_ACC);

				$ranks = array();
				foreach($hits as $player => $hitCount) {
					if (!isset($killDeathRatios[$player]) || !isset($accuracies[$player])) {
						continue;
					}
					$ranks[$player] = $killDeathRatios[$player] * $accuracies[$player] * 1000;

				}

				arsort($ranks);


				break;
			case self::RANKING_TYPE_HITS:
				$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_HITS_RANKING);

				$ranks = $this->maniaControl->statisticManager->getStatsRanking(StatisticCollector::STAT_ON_HIT, -1, $minHits);

				arsort($ranks);
				break;
			case self::RANKING_TYPE_RECORDS: //TODO verify workable status
				if (!$this->maniaControl->pluginManager->isPluginActive('LocalRecordsPlugin')) {
					return;
				}

				$requiredRecords = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_REQUIRED_RECORDS);
				$maxRecords      = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MAX_STORED_RECORDS);

				$query   = 'SELECT playerIndex, COUNT(*) AS Cnt
  		          FROM ' . LocalRecordsPlugin::TABLE_RECORDS . '
  		          GROUP BY PlayerId
  		          HAVING Cnt >=' . $requiredRecords;
				$result  = $mysqli->query($query);
				$players = array();
				while($row = $result->fetch_object()) {
					$players[$row->playerIndex] = array(0, 0); //sum, count
				}
				$result->free_result();

				/** @var LocalRecordsPlugin $localRecordsPlugin */
				$localRecordsPlugin = $this->maniaControl->pluginManager->getPlugin('LocalRecordsPlugin');
				$maps               = $this->maniaControl->mapManager->getMaps();
				foreach($maps as $map) {
					$records = $localRecordsPlugin->getLocalRecords($map, $maxRecords);

					$i = 1;
					foreach($records as $record) {
						if (isset($players[$record->playerIndex])) {
							$players[$record->playerIndex][0] += $i;
							$players[$record->playerIndex][1]++;
						}
						$i++;
					}
				}

				$mapCount = count($maps);

				//compute each players new average score
				$ranks = array();
				foreach($players as $player => $val) {
					$sum = $val[0];
					$cnt = $val[1];
					// ranked maps sum + $maxRecs rank for all remaining maps
					$ranks[$player] = ($sum + ($mapCount - $cnt) * $maxRecords) / $mapCount;
				}

				asort($ranks);
				break;
		}

		if (empty($ranks)) {
			return;
		}

		$this->recordCount = count($ranks);

		//Compute each player's new average score
		$query = "INSERT INTO " . self::TABLE_RANK . " VALUES ";
		$i     = 1;

		foreach($ranks as $player => $rankValue) {
			$query .= '(' . $player . ',' . $i . ',' . $rankValue . '),';
			$i++;
		}
		$query = substr($query, 0, strlen($query) - 1); // strip trailing ','

		$mysqli->query($query);
	}


	/**
	 * Handle PlayerConnect callback
	 *
	 * @param array $callback
	 */
	public function handlePlayerConnect(array $callback) {
		$player = $callback[1];
		if (!$player) {
			return;
		}

		$this->showRank($player);
		$this->showNextRank($player);
	}

	/**
	 * Shows Ranks on endMap
	 *
	 * @param array $callback
	 */
	public function handleEndMap(array $callback) {
		$this->resetRanks();

		foreach($this->maniaControl->playerManager->getPlayers() as $player) {
			$this->showRank($player);
			$this->showNextRank($player);
		}

		// Trigger callback
		$this->maniaControl->callbackManager->triggerCallback(self::CB_RANK_BUILT, array(self::CB_RANK_BUILT));
	}

	/**
	 * Shows the serverRank to a certain Player
	 *
	 * @param Player $player
	 */
	public function showRank(Player $player) {
		$rankObj = $this->getRank($player);

		$type = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_RANKING_TYPE);

		$message = '';
		if ($rankObj != null) {
			switch($type) {
				case self::RANKING_TYPE_RATIOS:
					$kd      = $this->maniaControl->statisticManager->getStatisticData(StatisticManager::SPECIAL_STAT_KD_RATIO, $player->index);
					$acc     = $this->maniaControl->statisticManager->getStatisticData(StatisticManager::SPECIAL_STAT_LASER_ACC, $player->index);
					$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> (K/D: $<$fff' . round($kd, 2) . '$> Acc: $<$fff' . round($acc * 100) . '%$>)';
					break;
				case self::RANKING_TYPE_HITS:
					$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> Hits: $fff' . $rankObj->avg;
					break;
				case self::RANKING_TYPE_RECORDS:
					$message = '$0f3Your Server rank is $<$ff3' . $rankObj->rank . '$> / $<$fff' . $this->recordCount . '$> Avg: $fff' . round($rankObj->avg, 2);
			}
		} else {
			switch($type) {
				case self::RANKING_TYPE_RATIOS:
					$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_RATIO_RANKING);
					$message = '$0f3 You must make $<$fff' . $minHits . '$> Hits on this server before recieving a rank...';
					break;
				case self::RANKING_TYPE_HITS:
					$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_HITS_HITS_RANKING);
					$message = '$0f3 You must make $<$fff' . $minHits . '$> Hits on this server before recieving a rank...';
					break;
				case self::RANKING_TYPE_RECORDS:
					$minHits = $this->maniaControl->settingManager->getSetting($this, self::SETTING_MIN_REQUIRED_RECORDS);
					$message = '$0f3 You need $<$fff' . $minHits . '$> Records on this server before recieving a rank...';
			}
		}
		$this->maniaControl->chat->sendChat($message, $player->login);
	}

	/**
	 * Gets A Rank As Object with properties Avg PlayerIndex and Rank
	 *
	 * @param Player $player
	 * @return Rank $rank
	 */
	private function getRank(Player $player) {
		//TODO setting global from db or local
		$mysqli = $this->maniaControl->database->mysqli;

		$result = $mysqli->query('SELECT * FROM ' . self::TABLE_RANK . ' WHERE PlayerIndex=' . $player->index);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array();
			$result->free_result();
			return Rank::fromArray($row);
		} else {
			$result->free_result();
			return null;
		}
	}

	/**
	 * Get the Next Ranked Player
	 *
	 * @param Player $player
	 * @return Rank
	 */
	private function getNextRank(Player $player) {
		$mysqli     = $this->maniaControl->database->mysqli;
		$rankObject = $this->getRank($player);
		$nextRank   = $rankObject->rank - 1;

		$result = $mysqli->query('SELECT * FROM ' . self::TABLE_RANK . ' WHERE Rank=' . $nextRank);
		if ($result->num_rows > 0) {
			$row = $result->fetch_array();
			$result->free_result();
			return Rank::fromArray($row);
		} else {
			$result->free_result();
			return null;
		}
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
	 * Shows which Player is next ranked to you
	 *
	 * @param Player $player
	 */
	public function showNextRank(Player $player) {
		$rankObject = $this->getRank($player);

		if ($rankObject != null) {
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
		return false;
	}
}

/**
 * Rank Structure
 */
class Rank extends AbstractStructure {
	public $playerIndex;
	public $rank;
	public $avg;
}