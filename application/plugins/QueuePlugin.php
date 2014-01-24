<?php
use FML\Controls\Frame;
use FML\Controls\Labels\Label_Button;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\ManiaLink;

use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Commands\CommandListener;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;
use ManiaControl\Players\Player;
use ManiaControl\Players\PlayerManager;
use ManiaControl\Plugins\Plugin;

/**
 * Queue plugin
 *
 * @author TheM
 */

// TODO: worst kick function
// TODO: idlekick function (?)

class QueuePlugin implements CallbackListener, CommandListener, ManialinkPageAnswerListener, Plugin {
    /**
     * Constants
     */
    const ID                   = 12;
    const VERSION              = 0.1;
    const ML_ID                = 'Queue.Widget';
    const ML_ADDTOQUEUE        = 'Queue.Add';
    const ML_REMOVEFROMQUEUE   = 'Queue.Remove';

    const QUEUE_MAX            = 'Maximum number in the queue';

    /**
     * Private properties
     */
    /** @var ManiaControl $maniaControl */
    private $maniaControl = null;
    private $queue = array();
    private $spectators = array();
    private $showPlay = array();

    /**
     * Load the plugin
     *
     * @param \ManiaControl\ManiaControl $maniaControl
     * @return bool
     */
    public function load(ManiaControl $maniaControl) {
        $this->maniaControl = $maniaControl;

        $this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERJOINED, $this, 'handlePlayerConnect');
        $this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERDISCONNECTED, $this, 'handlePlayerDisconnect');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERINFOCHANGED, $this, 'handlePlayerInfoChanged');
        $this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MC_1_SECOND, $this, 'handleEverySecond');
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ML_ADDTOQUEUE, $this, 'handleManiaLinkAnswerAdd');
        $this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ML_REMOVEFROMQUEUE, $this, 'handleManiaLinkAnswerRemove');

        $this->maniaControl->settingManager->initSetting($this, self::QUEUE_MAX, 8);

        foreach($this->maniaControl->playerManager->getPlayers() as $player) {
            if($player->isSpectator) {
                $this->spectators[] = $player->login;
                $this->maniaControl->client->forceSpectator($player->login, 1);
                $this->showJoinQueueWidget($player);
            }
        }
    }

    /**
     * Unload the plugin and its resources
     */
    public function unload() {
        $this->maniaControl->manialinkManager->unregisterManialinkPageAnswerListener($this);
        $this->maniaControl->callbackManager->unregisterCallbackListener($this);

        foreach($this->spectators as $spectator) {
            $this->maniaControl->client->forceSpectator($spectator, 3);
            $this->maniaControl->client->forceSpectator($spectator, 0);
        }

        foreach($this->maniaControl->playerManager->getPlayers() as $player) {
            $this->hideQueueWidget($player);
        }

        $this->queue = array();
        $this->spectators = array();
        $this->showPlay = array();
        $this->maniaControl = null;
    }

    /**
     * Get plugin id
     *
     * @return int
     */
    public static function getId() {
        return self::ID;
    }

    /**
     * Get Plugin Name
     *
     * @return string
     */
    public static function getName() {
        return 'Queue Plugin';
    }

    /**
     * Get Plugin Version
     *
     * @return float
     */
    public static function getVersion() {
        return self::VERSION;
    }

    /**
     * Get Plugin Author
     *
     * @return string
     */
    public static function getAuthor() {
        return 'TheM';
    }

    /**
     * Get Plugin Description
     *
     * @return string
     */
    public static function getDescription() {
        return 'Plugin offers the known AutoQueue/SpecJam options.';
    }

    public function handlePlayerConnect(array $callback) {
        $login = $callback[1]->login;
        $player = $this->maniaControl->playerManager->getPlayer($login);

        if($player->isSpectator) {
            $this->spectators[$player->login] = $player;
            $this->maniaControl->client->forceSpectator($player->login, 1);
            $this->showJoinQueueWidget($player);
        } else {
            if(count($this->queue) != 0) {
                $this->maniaControl->client->forceSpectator($player->login, 1);
                $this->spectators[$player->login] = $player;
                $this->showJoinQueueWidget($player);
            }
        }
    }

    public function handlePlayerDisconnect(array $callback) {
        $login = $callback[1]->login;
        if(isset($this->spectators[$login])) unset($this->spectators[$login]);
        $this->removePlayerFromQueue($login);
        $this->moveFirstPlayerToPlay();
    }

    public function handlePlayerInfoChanged(array $callback) {
        $login = $callback[1][0]['Login'];
        $player = $this->maniaControl->playerManager->getPlayer($login);

        if(!is_null($player)) {
            if($player->isSpectator) {
                if(!isset($this->spectators[$player->login])) {
                    $this->maniaControl->client->forceSpectator($player->login, 1);
                    $this->spectators[$player->login] = $player;
                    $this->showJoinQueueWidget($player);
                }
            }
        }
    }

    public function handleEverySecond() {
        if($this->maniaControl->client->getMaxPlayers()['CurrentValue'] > count($this->maniaControl->playerManager->players)) {
            $this->moveFirstPlayerToPlay();
        }

        foreach($this->queue as $queuedPlayer) {
            $this->showJoinQueueWidget($queuedPlayer);
        }

        foreach($this->showPlay as $showPlay) {
            if(($showPlay['time']+5) < time()) {
                $this->hideQueueWidget($showPlay['player']);
                unset($this->showPlay[$showPlay['player']->login]);
            }
        }
    }

    public function handleManiaLinkAnswerAdd(array $chatCallback, Player $player) {
        $this->addPlayerToQueue($player);
    }

    public function handleManiaLinkAnswerRemove(array $chatCallback, Player $player) {
        $this->removePlayerFromQueue($player);
        $this->showJoinQueueWidget($player);
        $this->maniaControl->chat->sendChat('$z$s$090[Queue] $fff'.$player->nickname.'$z$s$090 has left the queue!');
    }

    private function moveFirstPlayerToPlay() {
        if(count($this->queue) > 0) {
            $firstPlayer = $this->maniaControl->playerManager->getPlayer($this->queue[0]->login);
            $this->forcePlayerToPlay($firstPlayer);
        }
    }

    private function forcePlayerToPlay(Player $player) {
        if($this->maniaControl->client->getMaxPlayers()['CurrentValue'] > count($this->maniaControl->playerManager->players)) {
            $this->maniaControl->client->forceSpectator($player->login, 2);
            $this->maniaControl->client->forceSpectator($player->login, 0);
            if(isset($this->spectators[$player->login])) unset($this->spectators[$player->login]);
            $this->removePlayerFromQueue($player->login);
            $this->showPlayWidget($player);
            $this->maniaControl->chat->sendChat('$z$s$090[Queue] $fff'.$player->nickname.'$z$s$090 has a free spot and is now playing!');
        }
    }

    private function addPlayerToQueue(Player $player) {
        if($this->maniaControl->settingManager->getSetting($this, self::QUEUE_MAX) > count($this->queue)) {
            $this->queue[count($this->queue)] = $player;
            $this->maniaControl->chat->sendChat('$z$s$090[Queue] $fff'.$player->nickname.'$z$s$090 has joined the queue!');
        }
    }

    private function removePlayerFromQueue($login) {
        $count = 0;
        $newQueue = array();
        foreach($this->queue as $queuePlayer) {
            if($queuePlayer->login != $login) {
                $newQueue[$count] = $queuePlayer;
                $count++;
            }
        }

        $this->queue = $newQueue;
    }

    private function showJoinQueueWidget(Player $player) {
        $maniaLink = new ManiaLink(self::ML_ID);

        $quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
        $quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();
        $max_queue = $this->maniaControl->settingManager->getSetting($this, self::QUEUE_MAX);

        // Main frame
        $frame = new Frame();
        $maniaLink->add($frame);
        $frame->setSize(60, 6);
        $frame->setPosition(0, 67, 0);

        // Background
        $backgroundQuad = new Quad();
        $frame->add($backgroundQuad);
        $backgroundQuad->setPosition(0, 0, 0);
        $backgroundQuad->setSize(70, 10);
        $backgroundQuad->setStyles($quadStyle, $quadSubstyle);

        $cameraQuad = new Quad_Icons64x64_1();
        $frame->add($cameraQuad);
        $cameraQuad->setPosition(-29, 0.4, 2);
        $cameraQuad->setSize(9, 9);
        $cameraQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_Camera);

        $statusLabel = new Label_Text();
        $frame->add($statusLabel);
        $statusLabel->setPosition(4.5, 2.8, 1);
        $statusLabel->setSize(66, 4);
        $statusLabel->setAlign('center', 'center');
        $statusLabel->setScale(0.8);
        $statusLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

        $messageLabel = new Label_Button();
        $frame->add($messageLabel);
        $messageLabel->setPosition(4.5, -1.6, 1);
        $messageLabel->setSize(56, 4);
        $messageLabel->setAlign('center', 'center');
        $messageLabel->setScale(1.0);

        $inQueue = false;
        foreach($this->queue as $queuedPlayer) {
            if($queuedPlayer->login == $player->login) {
                $inQueue = true;
            }
        }

        if($inQueue) {
            $message = '$fff$sYou\'re in the queue (click to unqueue).';
            $statusLabel->setText('$aaaStatus: Queued spectator      Waiting: '.count($this->queue).'/'.$max_queue.'');
            $messageLabel->setAction(self::ML_REMOVEFROMQUEUE);
            $backgroundQuad->setAction(self::ML_REMOVEFROMQUEUE);
            $statusLabel->setAction(self::ML_REMOVEFROMQUEUE);
            $cameraQuad->setAction(self::ML_REMOVEFROMQUEUE);
        } else {
            if(count($this->queue) < $max_queue) {
                $message = '$0ff$sClick to join spectator waiting list.';
                $messageLabel->setAction(self::ML_ADDTOQUEUE);
                $backgroundQuad->setAction(self::ML_ADDTOQUEUE);
                $statusLabel->setAction(self::ML_ADDTOQUEUE);
                $cameraQuad->setAction(self::ML_ADDTOQUEUE);
            } else {
                $message = '$f00The waiting list is full!';
            }

            $statusLabel->setText('$aaaStatus: Not queued spectator      Waiting: '.count($this->queue).'/'.$max_queue.'');
        }

        $messageLabel->setText($message);
        $messageLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

        $this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'Queue');
    }

    private function showPlayWidget(Player $player) {
        $maniaLink = new ManiaLink(self::ML_ID);

        $quadStyle    = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowStyle();
        $quadSubstyle = $this->maniaControl->manialinkManager->styleManager->getDefaultMainWindowSubStyle();

        // Main frame
        $frame = new Frame();
        $maniaLink->add($frame);
        $frame->setSize(60, 6);
        $frame->setPosition(0, 67, 0);

        // Background
        $backgroundQuad = new Quad();
        $frame->add($backgroundQuad);
        $backgroundQuad->setPosition(0, 0, 0);
        $backgroundQuad->setSize(70, 10);
        $backgroundQuad->setStyles($quadStyle, $quadSubstyle);

        $cameraQuad = new Quad_Icons64x64_1();
        $frame->add($cameraQuad);
        $cameraQuad->setPosition(-29, 0.4, 2);
        $cameraQuad->setSize(9, 9);
        $cameraQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_Camera);

        $messageLabel = new Label_Button();
        $frame->add($messageLabel);
        $messageLabel->setPosition(4.5, 0.6, 1);
        $messageLabel->setSize(56, 4);
        $messageLabel->setAlign('center', 'center');
        $messageLabel->setScale(1.0);
        $messageLabel->setText('$090You have a free spot, enjoy playing!');
        $messageLabel->setStyle(Label_Text::STYLE_TextStaticSmall);

        $this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'Queue');
        $this->showPlay[$player->login] = array('time' => time(), 'player' => $player);
    }

    private function hideQueueWidget(Player $player) {
        $maniaLink = new ManiaLink(self::ML_ID);
        $this->maniaControl->manialinkManager->displayWidget($maniaLink, $player, 'Queue');
    }
}