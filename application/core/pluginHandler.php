<?php

namespace ManiaControl;

/**
 * Class handles plugins
 *
 * @author Lukas Kremsmayr and steeffeen
 */


 class PluginHandler {

     /**
      * Private properties
      */
     private $mc;
     private $plugins;

     public function __construct($mc){
         $this->mControl = $mc;
         $this->plugins = array();
     }

     public function registerPlugin($plugin){
        array_push($this->plugins, $plugin);
     }
 }
?>