<?php

namespace ManiaControl;

/**
 * Class  manialinkidHandler handles manialink id's
 *
 * @author Lukas Kremsmayr and steeffeen
 */


 class ManialinkIdHandler {
  	/**
  	 * Private properties
  	 */
    private $maniaLinkIdCount;

    public function __construct(){
      $maniaLinkIdCount = 0;
    }

 	/**
	 * Reservses manialinks for a plugin
	 *
	 * @param int $count   
	 * @return array with manialink Ids     	      	
	 */
          
    public function reserveManiaLinkIds($count){
      $mlIds = array();
      for($i = 0; $i < $count; $i++){
         $mlIds[0] = $i + $this->maniaLinkIdCount;
      }
      $this->maniaLinkIdCount += $count;
      return $mlIds;
    }

 }
?>