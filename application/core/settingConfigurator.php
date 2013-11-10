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

    }

    private function onInit(){

        $this->maniaControl->chat->sendChat("test");
    }
} 