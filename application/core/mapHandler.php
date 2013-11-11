<?php
/**
 * Handler for maps
 *
 * @author kremsy & steeffeen
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

    //TODO: database init
    //TODO: erasemap from server
    //TODO: implement of a method which are called by xlist command and results maplists from maniaexcahnge (or extra class for it)
    //TODO: admin add from maniaexchange, would handle it here
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
			$map = new Map($this->maniaControl, $map);
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