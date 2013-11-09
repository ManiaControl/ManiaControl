<?php

namespace ManiaControl;

/**
 * Class plugin parent class for all plugins
 *
 * @author Lukas Kremsmayr and steeffeen
 */


 class Plugin {

	/**
  	 * Private properties
  	 */
  	private $mControl;
    private $version;
    private $author;
    private $updateUrl;
    private $name;



    public function __construct($mControl, $name, $version = 0, $author = '', $updateUrl = ''){
       $this->mControl = $mControl;
       $this->name = $name;
       $this->version = $version;
       $this->author = $author;
       $this->updateUrl = $updateUrl;
    }

	/**
	 * Reserves manialinks on the ManialinkIdHandler
	 *
	 * @param int $count   
	 * @return array with manialink Ids     	      	
	 */
    public function reserveManialinkIds($count){
       return $this->mControl->manialinkIdHandler->reserveManialikIds($count);
    }
 
    public function checkUpdate(){
    
    }

     /**
      * @param mixed $author
      */
     public function setAuthor($author)
     {
         $this->author = $author;
     }

     /**
      * @return mixed
      */
     public function getAuthor()
     {
         return $this->author;
     }

     /**
      * @param mixed $updateUrl
      */
     public function setUpdateUrl($updateUrl)
     {
         $this->updateUrl = $updateUrl;
     }

     /**
      * @return mixed
      */
     public function getUpdateUrl()
     {
         return $this->updateUrl;
     }

     /**
      * @param mixed $version
      */
     public function setVersion($version)
     {
         $this->version = $version;
     }

     /**
      * @return mixed
      */
     public function getVersion()
     {
         return $this->version;
     }
     /**
      * @param mixed $name
      */
     public function setName($name)
     {
         $this->name = $name;
     }

     /**
      * @return mixed
      */
     public function getName()
     {
         return $this->name;
     }
 }
?>