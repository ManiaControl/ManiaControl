<?php
/**
 * @author Lukas Kremsmayr and steeffeen
 */

namespace ManiaControl;


class settingConfigurator {
    private $maniaControl = null;
    public function __construct(ManiaControl $maniaControl){
        $this->maniaControl = $maniaControl;
        $this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MC_ONINIT, $this, 'onInit');
        $this->maniaControl->manialinkIdHandler->reserveManiaLinkIds(100);
    }

    public function onInit(array $callback){
       // $this->maniaControl->manialinkUtil->
       // $this->maniaControl->chat->sendChat("test");
    }
} 