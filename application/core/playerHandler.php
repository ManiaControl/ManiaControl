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
     * Handels a playerConnect
     * @param $player
     */
    public function playerConnect($player){
        error_log("test");
        $this->mc->client->query('GetDetailedPlayerInfo', $player[0]);
        $this->addPlayer(new Player($this->mc->client->getResponse()));
        $this->mc->chat->sendChat("test");
    }
    /**
     * Adds a player to the PlayerList
     * @param Player $player
     * @return bool
     */
    public function addPlayer(Player $player){
        if($player != null){
            $this->playerList[$player->getLogin()] = $player;
            return true;
        }else{
            return false;
        }
    }
} 