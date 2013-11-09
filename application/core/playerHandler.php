<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 09.11.13
 * Time: 19:44
 */

namespace ManiaControl;


/**
 * Class playerHandler
 * @package ManiaControl
 */
class playerHandler {

    /**
     * Private properties
     */
    private $playerList;
    private $mc;

    /**
     * Public properties
     */
    public $rightLevels = array(0 => 'Player', 1 => 'Operator', 2 => 'Admin', 3 => 'MasterAdmin', 4 => 'MasterAdmin');

    public function __construct(ManiaControl $mc){
        $this->mc = $mc;
        $this->playerList = array();
        $this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
        $this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERDISCONNECT, $this, 'playerDisconnect');
    }


    /**
     * initializes a Whole PlayerList
     * @param $players
     */
    public function addPlayerList($players){
        foreach($players as $player){
            $this->playerConnect(array($player['Login'], ''));
        }
    }
    /**
     * Handles a playerConnect
     * @param $player
     */
    public function playerConnect($player){
        //TODO: Welcome Message?, on mc restart not all players listed, no relay support yet
        //TODO: Add Rights
        //TODO: Database
        $this->mc->client->query('GetDetailedPlayerInfo', $player[0]);
        $this->addPlayer(new Player($this->mc->client->getResponse()));
        $player = $this->playerList[$player[0]];
        if($player->pid != 0){ //Player 0 = server
            $string = array(0 => 'New Player', 1 => 'Operator', 2 => 'Admin', 3 => 'MasterAdmin', 4 => 'MasterAdmin');
            $this->mc->chat->sendChat('$ff0'.$string[$player->rightLevel].': '. $player->nickname . '$z $ff0Nation:$fff ' . $player->nation . ' $ff0Ladder: $fff' . $player->ladderrank);
        }
     }

    /**
     * Handles a playerDisconnect
     * @param $player
     */
    public function playerDisconnect($player){
        $player = $this->removePlayer($player[0]);
        $played = TOOLS::formatTime(time() - $player->created);
        $this->mc->chat->sendChat($player->nickname . '$z $ff0has left the game. Played:$fff ' . $played);
    }


    /**
     * Gets a Player from the Playerlist
     * @param $login
     * @return null
     */
    public function getPlayer($login){
        if (isset($this->playerList[$login]))
            return $this->playerList[$login];
        else
            return null;
    }

    /**
     * Adds a player to the PlayerList
     * @param Player $player
     * @return bool
     */
    private function addPlayer(Player $player){
        if($player != null){
            $this->playerList[$player->login] = $player;
            return true;
        }else{
            return false;
        }
    }
    /**
     * Removes a Player from the PlayerList
     * @param $login
     * @return Player $player
     */
    private function removePlayer($login){
        if(isset($this->playerList[$login])){
            $player = $this->playerList[$login];
            unset($this->playerList[$login]);
        } else {
            $player = null;
        }
        return $player;
    }
} 