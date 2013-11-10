<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 10.11.13
 * Time: 16:46
 */

namespace ManiaControl;


class mapHandler {

    /**
     * Private properties
     */
    private $maniaControl = null;
    private $mapList = array();


    /**
     * Construct map  handler
     * @param ManiaControl $maniaControl
     */
    public function __construct(ManiaControl $maniaControl) {
        $this->maniaControl = $maniaControl;

        $this->initTables();

        $this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MC_ONINIT, $this, 'onInit');
        $this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_BEGINMAP, $this, 'beginMap');
        $this->maniaControl->callbacks->registerCallbackHandler(Callbacks::CB_MP_ENDMAP, $this, 'endMap');
    }

    /**
     * Initialize all necessary tables
     *
     * @return bool
     */
    private function initTables() {


    }

    /**
     * Handle OnInit callback
     *
     * @param array $callback
     */
    public function onInit(){
        $this->maniaControl->client->query('GetMapList', 300, 0);
        $mapList = $this->maniaControl->client->getResponse();
        foreach ($mapList as $map) {
            $this->addMap($map);
        }
    }

    /**
     * Add a map to the MapList
     *
     * @param  Map $map
     * @return bool
     */
    private function addMap(Map $map) {
        if (!$map) {
            return false;
        }
        $this->mapList[$map->uid] = $map;
        return true;
    }

} 