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
  	private $mc;
    private $version;
    private $author;
    private $updateUrl;
    private $name;
    private $description;
    private $active;

    public function __construct($mc, $name, $version = 0, $author = '', $description = '', $updateUrl = ''){
       $this->mc = $mc;
       $this->name = $name;
       $this->version = $version;
       $this->author = $author;
       $this->description = $description;
       $this->updateUrl = $updateUrl;

       $this->mc->pluginHandler->registerPlugin($this);
    }

	/**
	 * Reserves manialinks on the ManialinkIdHandler
	 *
	 * @param int $count   
	 * @return array with manialink Ids     	      	
	 */
    public function reserveManialinkIds($count){
       return $this->mc->manialinkIdHandler->reserveManialikIds($count);
    }

    public function checkUpdate(){
    
    }

     /**
      * Enables the Plugin
      */
     public function enablePlugin()
     {
         $this->active = true;
     }

     /**
      * Disable the Plugin
      */
     public function disablePlugin()
     {
         $this->active = true;
     }

     /**
      * @return mixed
      */
     public function isActive()
     {
         return $this->active;
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

     /**
      * @param string $description
      */
     public function setDescription($description)
     {
         $this->description = $description;
     }

     /**
      * @return string
      */
     public function getDescription()
     {
         return $this->description;
     }

 }
?>