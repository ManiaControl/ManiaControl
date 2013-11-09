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

    public function __construct(ManiaControl $mc){
        $this->mc = $mc;
        $this->playerList = array();
        $this->mc->callbacks->registerCallbackHandler(Callbacks::CB_MP_PLAYERCONNECT, $this, 'playerConnect');
    }


    /**
     * initializes a Whole Playerlist
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
        $this->mc->client->query('GetDetailedPlayerInfo', $player[0]);
        $this->addPlayer(new Player($this->mc->client->getResponse()));
        $player = $this->playerList[$player[0]];
        if($player->pid != 0){ //Player 0 = server
            $this->mc->chat->sendChat('$ff0New Player: '. $player->nickname . '$z $ff0Nation:$fff ' . $player->nation . ' $ff0Ladder: $fff' . $player->ladderrank);
        }
     }
    /**
     * Adds a player to the PlayerList
     * @param Player $player
     * @return bool
     */
    public function addPlayer(Player $player){
        if($player != null){
            $this->playerList[$player->login] = $player;
            return true;
        }else{
            return false;
        }
    }
} 