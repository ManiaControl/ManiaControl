<?php

namespace FML\Stylesheet;

/**
 * Class representing a Stylesheet Mood
 * Warning: The mood class isn't fully supported yet - Missing attributes: LDir1 etc.
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Mood
{

    /**
     * @var string $lightAmbientColor Light ambient color
     */
    protected $lightAmbientColor = null;

    /**
     * @var string $cloudsMinimumColor Clouds minimum color
     */
    protected $cloudsMinimumColor = null;

    /**
     * @var string $cloudsMaximumColor Clouds maximum color
     */
    protected $cloudsMaximumColor = null;

    /**
     * @var string $light0Color Color of light source 0
     */
    protected $light0Color = null;

    /**
     * @var float $light0Intensity Intensity of light source 0
     */
    protected $light0Intensity = 1.;

    /**
     * @var float $light0PhiAngle Phi angle of light source 0
     */
    protected $light0PhiAngle = 0.;

    /**
     * @var float $light0ThetaAngle Theta angle of light source 0
     */
    protected $light0ThetaAngle = 0.;

    /**
     * @var string $lightBallColor Light ball color
     */
    protected $lightBallColor = null;

    /**
     * @var float $lightBallIntensity Light ball intensity
     */
    protected $lightBallIntensity = 1.;

    /**
     * @var float $lightBallRadius Light ball radius
     */
    protected $lightBallRadius = 0.;

    /**
     * @var string $fogColor Fog color
     */
    protected $fogColor = null;

    /**
     * @var float $selfIlluminationColor Self illumination color
     */
    protected $selfIlluminationColor = null;

    /**
     * @var float $skyGradientV_Scale Sky gradient scale
     */
    protected $skyGradientScale = 1.;

    /**
     * @var SkyGradientKey[] $skyGradientKeys Sky Gradient Keys
     */
    protected $skyGradientKeys = array();

    /**
     * Create a new Mood
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get the light ambient color
     *
     * @api
     * @return string
     */
    public function getLightAmbientColor()
    {
        return $this->lightAmbientColor;
    }

    /**
     * Set the ambient color in which elements reflect the light
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setLightAmbientColor($red, $green, $blue)
    {
        $this->lightAmbientColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the minimum value for the background color range
     *
     * @api
     * @return string
     */
    public function getCloudsMinimumColor()
    {
        return $this->cloudsMinimumColor;
    }

    /**
     * Set the minimum value for the background color range
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setCloudsMinimumColor($red, $green, $blue)
    {
        $this->cloudsMinimumColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the maximum value for the background color range
     *
     * @api
     * @return string
     */
    public function getCloudsMaximumColor()
    {
        return $this->cloudsMaximumColor;
    }

    /**
     * Set the maximum value for the background color range
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setCloudsMaximumColor($red, $green, $blue)
    {
        $this->cloudsMaximumColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the RGB color of light source 0
     *
     * @api
     * @return string
     */
    public function getLight0Color()
    {
        return $this->light0Color;
    }

    /**
     * Set the RGB color of light source 0
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setLight0Color($red, $green, $blue)
    {
        $this->light0Color = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the intensity of light source 0
     *
     * @api
     * @return float
     */
    public function getLight0Intensity()
    {
        return $this->light0Intensity;
    }

    /**
     * Set the intensity of light source 0
     *
     * @api
     * @param float $intensity Light intensity
     * @return static
     */
    public function setLight0Intensity($intensity)
    {
        $this->light0Intensity = (float)$intensity;
        return $this;
    }

    /**
     * Get the phi angle of light source 0
     *
     * @api
     * @return float
     */
    public function getLight0PhiAngle()
    {
        return $this->light0PhiAngle;
    }

    /**
     * Set the phi angle of light source 0
     *
     * @api
     * @param float $phiAngle Phi angle
     * @return static
     */
    public function setLight0PhiAngle($phiAngle)
    {
        $this->light0PhiAngle = (float)$phiAngle;
        return $this;
    }

    /**
     * Get the theta angle of light source 0
     *
     * @api
     * @return float
     */
    public function getLight0ThetaAngle()
    {
        return $this->light0ThetaAngle;
    }

    /**
     * Set the theta angle of light source 0
     *
     * @api
     * @param float $thetaAngle Theta angle
     * @return static
     */
    public function setLight0ThetaAngle($thetaAngle)
    {
        $this->light0ThetaAngle = (float)$thetaAngle;
        return $this;
    }

    /**
     * Get the light ball color
     *
     * @api
     * @return string
     */
    public function getLightBallColor()
    {
        return $this->lightBallColor;
    }

    /**
     * Set the light ball color
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setLightBallColor($red, $green, $blue)
    {
        $this->lightBallColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the light ball intensity
     *
     * @api
     * @return float
     */
    public function getLightBallIntensity()
    {
        return $this->lightBallIntensity;
    }

    /**
     * Set the light ball intensity
     *
     * @api
     * @param float $intensity Light ball intensity
     * @return static
     */
    public function setLightBallIntensity($intensity)
    {
        $this->lightBallIntensity = (float)$intensity;
        return $this;
    }

    /**
     * Get the light ball radius
     *
     * @api
     * @return float
     */
    public function getLightBallRadius()
    {
        return $this->lightBallRadius;
    }

    /**
     * Set the light ball radius
     *
     * @api
     * @param float $radius Light ball radius
     * @return static
     */
    public function setLightBallRadius($radius)
    {
        $this->lightBallRadius = (float)$radius;
        return $this;
    }

    /**
     * Get the fog color
     *
     * @api
     * @return string
     */
    public function getFogColor()
    {
        return $this->fogColor;
    }

    /**
     * Set the fog color
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setFogColor($red, $green, $blue)
    {
        $this->fogColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the self illumination color
     *
     * @api
     * @return string
     */
    public function getSelfIlluminationColor()
    {
        return $this->selfIlluminationColor;
    }

    /**
     * Set the self illumination color
     *
     * @api
     * @param float $red   Red color value
     * @param float $green Green color value
     * @param float $blue  Blue color value
     * @return static
     */
    public function setSelfIlluminationColor($red, $green, $blue)
    {
        $this->selfIlluminationColor = floatval($red) . ' ' . floatval($green) . ' ' . floatval($blue);
        return $this;
    }

    /**
     * Get the sky gradient scale
     *
     * @api
     * @return float
     */
    public function getSkyGradientScale()
    {
        return $this->skyGradientScale;
    }

    /**
     * Set the sky gradient scale
     *
     * @api
     * @param float $skyGradientScale Sky gradient scale
     * @return static
     */
    public function setSkyGradientScale($skyGradientScale)
    {
        $this->skyGradientScale = (float)$skyGradientScale;
        return $this;
    }

    /**
     * Get Sky Gradient Keys
     *
     * @api
     * @return SkyGradientKey[]
     */
    public function getSkyGradientKeys()
    {
        return $this->skyGradientKeys;
    }

    /**
     * Add a sky gradient key
     *
     * @api
     * @param SkyGradientKey $skyGradientKey Sky Gradient Key
     * @return static
     */
    public function addSkyGradientKey(SkyGradientKey $skyGradientKey)
    {
        array_push($this->skyGradientKeys, $skyGradientKey);
        return $this;
    }

    /**
     * Add a sky gradient
     *
     * @api
     * @param float  $x     X value
     * @param string $color Color value
     * @return static
     */
    public function addSkyGradient($x, $color)
    {
        $skyGradientKey = new SkyGradientKey($x, $color);
        return $this->addSkyGradientKey($skyGradientKey);
    }

    /**
     * Remove all Sky Gradient Keys
     *
     * @api
     * @return static
     */
    public function removeAllSkyGradientKeys()
    {
        $this->skyGradientKeys = array();
        return $this;
    }

    /**
     * Render the Mood
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Mood should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("mood");
        if ($this->lightAmbientColor) {
            $domElement->setAttribute("LAmbient_LinearRgb", $this->lightAmbientColor);
        }
        if ($this->cloudsMinimumColor) {
            $domElement->setAttribute("CloudsRgbMinLinear", $this->cloudsMinimumColor);
        }
        if ($this->cloudsMaximumColor) {
            $domElement->setAttribute("CloudsRgbMaxLinear", $this->cloudsMaximumColor);
        }
        if ($this->light0Color) {
            $domElement->setAttribute("LDir0_LinearRgb", $this->light0Color);
        }
        if ($this->light0Intensity != 1.) {
            $domElement->setAttribute("LDir0_Intens", $this->light0Intensity);
        }
        if ($this->light0PhiAngle) {
            $domElement->setAttribute("LDir0_DirPhi", $this->light0PhiAngle);
        }
        if ($this->light0ThetaAngle) {
            $domElement->setAttribute("LDir0_DirTheta", $this->light0ThetaAngle);
        }
        if ($this->lightBallColor) {
            $domElement->setAttribute("LBall_LinearRgb", $this->lightBallColor);
        }
        if ($this->lightBallIntensity != 1.) {
            $domElement->setAttribute("LBall_Intens", $this->lightBallIntensity);
        }
        if ($this->lightBallRadius) {
            $domElement->setAttribute("LBall_Radius", $this->lightBallRadius);
        }
        if ($this->fogColor) {
            $domElement->setAttribute("FogColorSrgb", $this->fogColor);
        }
        if ($this->selfIlluminationColor) {
            $domElement->setAttribute("SelfIllumColor", $this->selfIlluminationColor);
        }
        if ($this->skyGradientScale != 1.) {
            $domElement->setAttribute("SkyGradientV_Scale", $this->skyGradientScale);
        }
        if ($this->skyGradientKeys) {
            $skyGradientElement = $domDocument->createElement("skygradient");
            $domElement->appendChild($skyGradientElement);
            foreach ($this->skyGradientKeys as $skyGradientKey) {
                $skyGradientKeyElement = $skyGradientKey->render($domDocument);
                $skyGradientElement->appendChild($skyGradientKeyElement);
            }
        }
        return $domElement;
    }

}
