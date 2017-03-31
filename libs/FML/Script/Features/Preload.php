<?php

namespace FML\Script\Features;

use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;

/**
 * Script Feature for Image Preloading
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Preload extends ScriptFeature
{

    /**
     * @var string[] $imageUrls Image urls
     */
    protected $imageUrls = array();

    /**
     * Construct a new Preload
     *
     * @api
     * @param string[] $imageUrls (optional) Image urls
     */
    public function __construct(array $imageUrls = null)
    {
        if ($imageUrls) {
            $this->setImageUrls($imageUrls);
        }
    }

    /**
     * Get Image Urls to preload
     *
     * @api
     * @return string[]
     */
    public function getImageUrls()
    {
        return $this->imageUrls;
    }

    /**
     * Add an Image Url to preload
     *
     * @api
     * @param string $imageUrl Image Url
     * @return static
     */
    public function addImageUrl($imageUrl)
    {
        if (!in_array($imageUrl, $this->imageUrls)) {
            array_push($this->imageUrls, $imageUrl);
        }
        return $this;
    }

    /**
     * Set Image Urls to preload
     *
     * @api
     * @param string[] $imageUrls Image Urls
     * @return static
     */
    public function setImageUrls(array $imageUrls = array())
    {
        $this->imageUrls = $imageUrls;
        return $this;
    }

    /**
     * Remove all Image Urls
     *
     * @api
     * @return static
     */
    public function removeAllImageUrls()
    {
        $this->imageUrls = array();
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel(ScriptLabel::ONINIT, $this->getScriptText());
        return $this;
    }

    /**
     * Get the script text
     *
     * @return string
     */
    protected function getScriptText()
    {
        $scriptText = "";
        foreach ($this->imageUrls as $imageUrl) {
            $escapedImageUrl = Builder::escapeText($imageUrl);
            $scriptText .= "
PreloadImage({$escapedImageUrl});";
        }
        return $scriptText;
    }

}
