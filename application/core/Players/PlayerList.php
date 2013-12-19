<?php

namespace ManiaControl\Players;


use FML\Controls\Control;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Labels\Label_Text;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_BgRaceScore2;
use FML\Controls\Quads\Quad_Emblems;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Controls\Quads\Quad_Icons64x64_2;
use FML\Controls\Quads\Quad_UIConstruction_Buttons;
use FML\ManiaLink;
use FML\Script\Script;
use FML\Script\Tooltips;
use ManiaControl\Admin\AuthenticationManager;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\ManiaControl;
use ManiaControl\Manialinks\ManialinkManager;
use ManiaControl\Manialinks\ManialinkPageAnswerListener;

class PlayerList implements ManialinkPageAnswerListener, CallbackListener {

	/**
	 * Constants
	 */
	const ACTION_CLOSEWIDGET = 'PlayerList.CloseWidget';
	const ACTION_FORCE_RED = 'PlayerList.ForceRed';
	const ACTION_FORCE_BLUE = 'PlayerList.ForceBlue';
	const ACTION_FORCE_SPEC = 'PlayerList.ForceSpec';
	/**
	 * Private properties
	 */
	private $maniaControl = null;
	private $width;
	private $height;
	private $quadStyle;
	private $quadSubstyle;
	private $playersListShown = array();

	/**
	 * Create a new server commands instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;


		$this->maniaControl->manialinkManager->registerManialinkPageAnswerListener(self::ACTION_CLOSEWIDGET , $this,
			'closeWidget');
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERMANIALINKPAGEANSWER, $this,
			'handleManialinkPageAnswer');
		$this->maniaControl->callbackManager->registerCallbackListener(PlayerManager::CB_PLAYERINFOCHANGED, $this, 'playerInfoChanged');

		//settings
		$this->width = 150;
		$this->height = 80;
		$this->quadStyle = Quad_BgRaceScore2::STYLE; //TODO add default menu style to style manager
		$this->quadSubstyle = Quad_BgRaceScore2::SUBSTYLE_HandleSelectable;

	}

	public function showPlayerList(Player $player){
		$this->playersListShown[$player->login] = true;

		$maniaLink = new ManiaLink(ManialinkManager::MAIN_MLID);


		// Create script and features
		$script = new Script();
		$maniaLink->setScript($script);

		$tooltips = new Tooltips();
		$script->addFeature($tooltips);

		//mainframe
		$frame = new Frame();
		$maniaLink->add($frame);
		$frame->setSize($this->width,$this->height);
		$frame->setPosition(0, 0);

		//Background Quad
		$backgroundQuad = new Quad();
		$frame->add($backgroundQuad);
		$backgroundQuad->setSize($this->width,$this->height);
		$backgroundQuad->setStyles($this->quadStyle, $this->quadSubstyle);

		// Add Close Quad (X)
		$closeQuad = new Quad_Icons64x64_1();
		$frame->add($closeQuad);
		$closeQuad->setPosition($this->width * 0.483, $this->height * 0.467, 3);
		$closeQuad->setSize(6, 6);
		$closeQuad->setSubStyle(Quad_Icons64x64_1::SUBSTYLE_QuitRace);
		$closeQuad->setAction(self::ACTION_CLOSEWIDGET );

		//Start offsets
		$x = -$this->width / 2;
		$y = $this->height / 2;

		//Headline
		$headFrame = new Frame();
		$frame->add($headFrame);
		$headFrame->setY($y - 5);
		//$array = array("Id" => $x + 5, "Nickname" => $x + 10,  "Login" => $x + 40, "Ladder" => $x + 60,"Zone" => $x + 85);
		if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){
			$array = array("Id" => $x + 5, "Nickname" => $x + 18,  "Login" => $x + 60, "Location" => $x + 91, "Actions" => $x + 135);
		}else{
			$array = array("Id" => $x + 5, "Nickname" => $x + 18,  "Login" => $x + 60, "Location" => $x + 91);
		}
		$this->maniaControl->manialinkManager->labelLine($headFrame,$array);

		//get PlayerList
		$players = $this->maniaControl->playerManager->getPlayers();

		$i = 1;
		$y -= 10;
		foreach($players as $listPlayer){
			//$path = substr($listPlayer->path, 6);
			//$path = $listPlayer->getCountry() . " - " . $listPlayer->getProvince();
			$path = $listPlayer->getProvince();
			$playerFrame = new Frame();
			$frame->add($playerFrame);
			//$array = array($i => $x + 5, $listPlayer->nickname => $x + 10,  $listPlayer->login => $x + 50, $listPlayer->ladderRank => $x + 60, $listPlayer->ladderScore => $x + 70, $path => $x + 85);
			$array = array($i => $x + 5, $listPlayer->nickname => $x + 18,  $listPlayer->login => $x + 60, $path => $x + 91);
			$this->maniaControl->manialinkManager->labelLine($playerFrame,$array);
			$playerFrame->setY($y);

			//Team Emblem
			if($listPlayer->teamId != -1){ //Show Players Team
				$redQuad = new Quad_Emblems(); //TODO rename quads
				$playerFrame->add($redQuad);
				$redQuad->setX($x + 10);
				$redQuad->setZ(0.1);
				$redQuad->setSize(3.8,3.8);

				switch($listPlayer->teamId){
					case 0: $redQuad->setSubStyle($redQuad::SUBSTYLE_1); break;
					case 1: $redQuad->setSubStyle($redQuad::SUBSTYLE_2); break;
					//case 2: $redQuad->setSubStyle($redQuad::SUBSTYLE_2); break;
				}
			}else{ //player is in spec
				$neutralQuad = new Quad_BgRaceScore2();
				$playerFrame->add($neutralQuad);
				$neutralQuad->setX($x + 10);
				$neutralQuad->setZ(0.1);
				$neutralQuad->setSubStyle($neutralQuad::SUBSTYLE_Spectator);
				$neutralQuad->setSize(3.8,3.8);
			}

			//Nation Quad
			$countryQuad = new Quad();
			$playerFrame->add($countryQuad);

			$countryQuad->setImage("file://Skins/Avatars/Flags/{$this->mapCountry($listPlayer->getCountry())}.dds");
			$countryQuad->setX($x + 88);
			$countryQuad->setSize(4,4);
			$countryQuad->setZ(-0.1);

			//Level Quad
			$rightQuad = new Quad_BgRaceScore2();
			$playerFrame->add($rightQuad);
			$rightQuad->setX($x + 13);
			$rightQuad->setZ(-0.1);
			$rightQuad->setSubStyle($rightQuad::SUBSTYLE_CupFinisher);
			$rightQuad->setSize(7,3.5);

			$rightLabel = new Label_Text();
			$playerFrame->add($rightLabel);
			$rightLabel->setX($x + 13.9);
			$rightLabel->setTextSize(0.8);


			//Description Label
			$descriptionLabel = new Label();
			$frame->add($descriptionLabel);
			$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
			$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
			$descriptionLabel->setSize($this->width * 0.7, 4);
			$descriptionLabel->setTextSize(2);
			$descriptionLabel->setVisible(false);
			$descriptionLabel->setText($this->maniaControl->authenticationManager->getAuthLevelName($listPlayer->authLevel));
			$tooltips->add($rightQuad, $descriptionLabel);

			switch($listPlayer->authLevel){
				case authenticationManager::AUTH_LEVEL_MASTERADMIN:
				case authenticationManager::AUTH_LEVEL_SUPERADMIN:  $rightLabel->setText("MA"); break;
				case authenticationManager::AUTH_LEVEL_ADMIN:		$rightLabel->setText("AD"); break;
				case authenticationManager::AUTH_LEVEL_OPERATOR:	$rightLabel->setText("OP");
			}

			$rightLabel->setTextColor("fff");


			if($this->maniaControl->authenticationManager->checkRight($player, AuthenticationManager::AUTH_LEVEL_OPERATOR)){

				//Further Player actions
				$playerQuad = new Quad_Icons64x64_1();
				$playerFrame->add($playerQuad);
				$playerQuad->setX($x + 132);
				$playerQuad->setZ(0.1);
				$playerQuad->setSubStyle($playerQuad::SUBSTYLE_Buddy);
				$playerQuad->setSize(3.8,3.8);
				//$playerQuad->setAction(self::ACTION_FORCE_BLUE . "." .$listPlayer->login);

				$redQuad = new Quad_Emblems();
				$playerFrame->add($redQuad);
				$redQuad->setX($x + 145);
				$redQuad->setZ(0.1);
				$redQuad->setSubStyle($redQuad::SUBSTYLE_2);
				$redQuad->setSize(3.8,3.8);
				$redQuad->setAction(self::ACTION_FORCE_RED . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Red Team!');
				$tooltips->add($redQuad, $descriptionLabel);

				//Force to Blue Team
				$blueQuad = new Quad_Emblems();
				$playerFrame->add($blueQuad);
				$blueQuad->setX($x + 141);
				$blueQuad->setZ(0.1);
				$blueQuad->setSubStyle($blueQuad::SUBSTYLE_1);
				$blueQuad->setSize(3.8,3.8);
				$blueQuad->setAction(self::ACTION_FORCE_BLUE . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Blue Team!');
				$tooltips->add($blueQuad, $descriptionLabel);


				$spectatorQuad = new Quad_BgRaceScore2();
				$playerFrame->add($spectatorQuad);
				$spectatorQuad->setX($x + 137);
				$spectatorQuad->setZ(0.1);
				$spectatorQuad->setSubStyle($spectatorQuad::SUBSTYLE_Spectator);
				$spectatorQuad->setSize(3.8,3.8);
				$spectatorQuad->setAction(self::ACTION_FORCE_SPEC . "." .$listPlayer->login);

				//Description Label
				$descriptionLabel = new Label();
				$frame->add($descriptionLabel);
				$descriptionLabel->setAlign(Control::LEFT, Control::TOP);
				$descriptionLabel->setPosition($x + 10, -$this->height / 2 + 5);
				$descriptionLabel->setSize($this->width * 0.7, 4);
				$descriptionLabel->setTextSize(2);
				$descriptionLabel->setVisible(false);
				$descriptionLabel->setText("Force " . $listPlayer->nickname . '$z to Spectator!');
				$tooltips->add($spectatorQuad, $descriptionLabel);
			}
			$i++;
			$y -= 4;
		}


		//render and display xml
		$this->maniaControl->manialinkManager->displayWidget($maniaLink, $player);
	}

	/**
	 * Closes the widget
	 * @param array  $callback
	 * @param Player $player
	 */
	public function closeWidget(array $callback, Player $player) {
		$this->playersListShown[$player->login] = false; //TODO unset
		$this->maniaControl->manialinkManager->closeWidget($player);
	}


	/**
	 * @param array $callback
	 */
	public function handleManialinkPageAnswer(array $callback){
		$actionId = $callback[1][2];
		$forceBlue = (strpos($actionId, self::ACTION_FORCE_BLUE) === 0);
		$forceRed = (strpos($actionId, self::ACTION_FORCE_RED) === 0);
		$forceSpec = (strpos($actionId, self::ACTION_FORCE_SPEC) === 0);

		if(!$forceBlue && !$forceRed && !$forceSpec)
			return;

		$actionArray = explode(".", $actionId);

		//TODO maybe with ids isntead of logins, lower network traffic
		if($forceBlue){
			$this->maniaControl->client->query('ForcePlayerTeam', $actionArray[2], 0); //TODO bestätigung
		}else if($forceRed){
			$this->maniaControl->client->query('ForcePlayerTeam', $actionArray[2], 1); //TODO bestätigung
		}else if($forceSpec){
			$this->maniaControl->client->query('ForceSpectator', $actionArray[2], 3); //TODO bestätigung
		}

	}

	/**
	 * Reopen the widget on PlayerInfoChanged
	 * @param array $callback
	 */
	public function playerInfoChanged(array $callback){
		foreach($this->playersListShown as $login => $shown){
			if($shown == true){
				$player = $this->maniaControl->playerManager->getPlayer($login);
				$this->showPlayerList($player);
			}
		}
	}



	//TODO move that into somewhere
	/**
	 * Map country names to 3-letter Nation abbreviations
	 * Created by Xymph
	 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
	 * See also http://en.wikipedia.org/wiki/Comparison_of_IOC,_FIFA,_and_ISO_3166_country_codes
	 */
	private function mapCountry($country) {

		$nations = array(
			'Afghanistan' => 'AFG',
			'Albania' => 'ALB',
			'Algeria' => 'ALG',
			'Andorra' => 'AND',
			'Angola' => 'ANG',
			'Argentina' => 'ARG',
			'Armenia' => 'ARM',
			'Aruba' => 'ARU',
			'Australia' => 'AUS',
			'Austria' => 'AUT',
			'Azerbaijan' => 'AZE',
			'Bahamas' => 'BAH',
			'Bahrain' => 'BRN',
			'Bangladesh' => 'BAN',
			'Barbados' => 'BAR',
			'Belarus' => 'BLR',
			'Belgium' => 'BEL',
			'Belize' => 'BIZ',
			'Benin' => 'BEN',
			'Bermuda' => 'BER',
			'Bhutan' => 'BHU',
			'Bolivia' => 'BOL',
			'Bosnia&Herzegovina' => 'BIH',
			'Botswana' => 'BOT',
			'Brazil' => 'BRA',
			'Brunei' => 'BRU',
			'Bulgaria' => 'BUL',
			'Burkina Faso' => 'BUR',
			'Burundi' => 'BDI',
			'Cambodia' => 'CAM',
			'Cameroon' => 'CAR',  // actually CMR
			'Canada' => 'CAN',
			'Cape Verde' => 'CPV',
			'Central African Republic' => 'CAF',
			'Chad' => 'CHA',
			'Chile' => 'CHI',
			'China' => 'CHN',
			'Chinese Taipei' => 'TPE',
			'Colombia' => 'COL',
			'Congo' => 'CGO',
			'Costa Rica' => 'CRC',
			'Croatia' => 'CRO',
			'Cuba' => 'CUB',
			'Cyprus' => 'CYP',
			'Czech Republic' => 'CZE',
			'Czech republic' => 'CZE',
			'DR Congo' => 'COD',
			'Denmark' => 'DEN',
			'Djibouti' => 'DJI',
			'Dominica' => 'DMA',
			'Dominican Republic' => 'DOM',
			'Ecuador' => 'ECU',
			'Egypt' => 'EGY',
			'El Salvador' => 'ESA',
			'Eritrea' => 'ERI',
			'Estonia' => 'EST',
			'Ethiopia' => 'ETH',
			'Fiji' => 'FIJ',
			'Finland' => 'FIN',
			'France' => 'FRA',
			'Gabon' => 'GAB',
			'Gambia' => 'GAM',
			'Georgia' => 'GEO',
			'Germany' => 'GER',
			'Ghana' => 'GHA',
			'Greece' => 'GRE',
			'Grenada' => 'GRN',
			'Guam' => 'GUM',
			'Guatemala' => 'GUA',
			'Guinea' => 'GUI',
			'Guinea-Bissau' => 'GBS',
			'Guyana' => 'GUY',
			'Haiti' => 'HAI',
			'Honduras' => 'HON',
			'Hong Kong' => 'HKG',
			'Hungary' => 'HUN',
			'Iceland' => 'ISL',
			'India' => 'IND',
			'Indonesia' => 'INA',
			'Iran' => 'IRI',
			'Iraq' => 'IRQ',
			'Ireland' => 'IRL',
			'Israel' => 'ISR',
			'Italy' => 'ITA',
			'Ivory Coast' => 'CIV',
			'Jamaica' => 'JAM',
			'Japan' => 'JPN',
			'Jordan' => 'JOR',
			'Kazakhstan' => 'KAZ',
			'Kenya' => 'KEN',
			'Kiribati' => 'KIR',
			'Korea' => 'KOR',
			'Kuwait' => 'KUW',
			'Kyrgyzstan' => 'KGZ',
			'Laos' => 'LAO',
			'Latvia' => 'LAT',
			'Lebanon' => 'LIB',
			'Lesotho' => 'LES',
			'Liberia' => 'LBR',
			'Libya' => 'LBA',
			'Liechtenstein' => 'LIE',
			'Lithuania' => 'LTU',
			'Luxembourg' => 'LUX',
			'Macedonia' => 'MKD',
			'Malawi' => 'MAW',
			'Malaysia' => 'MAS',
			'Mali' => 'MLI',
			'Malta' => 'MLT',
			'Mauritania' => 'MTN',
			'Mauritius' => 'MRI',
			'Mexico' => 'MEX',
			'Moldova' => 'MDA',
			'Monaco' => 'MON',
			'Mongolia' => 'MGL',
			'Montenegro' => 'MNE',
			'Morocco' => 'MAR',
			'Mozambique' => 'MOZ',
			'Myanmar' => 'MYA',
			'Namibia' => 'NAM',
			'Nauru' => 'NRU',
			'Nepal' => 'NEP',
			'Netherlands' => 'NED',
			'New Zealand' => 'NZL',
			'Nicaragua' => 'NCA',
			'Niger' => 'NIG',
			'Nigeria' => 'NGR',
			'Norway' => 'NOR',
			'Oman' => 'OMA',
			'Other Countries' => 'OTH',
			'Pakistan' => 'PAK',
			'Palau' => 'PLW',
			'Palestine' => 'PLE',
			'Panama' => 'PAN',
			'Paraguay' => 'PAR',
			'Peru' => 'PER',
			'Philippines' => 'PHI',
			'Poland' => 'POL',
			'Portugal' => 'POR',
			'Puerto Rico' => 'PUR',
			'Qatar' => 'QAT',
			'Romania' => 'ROM',  // actually ROU
			'Russia' => 'RUS',
			'Rwanda' => 'RWA',
			'Samoa' => 'SAM',
			'San Marino' => 'SMR',
			'Saudi Arabia' => 'KSA',
			'Senegal' => 'SEN',
			'Serbia' => 'SCG',  // actually SRB
			'Sierra Leone' => 'SLE',
			'Singapore' => 'SIN',
			'Slovakia' => 'SVK',
			'Slovenia' => 'SLO',
			'Somalia' => 'SOM',
			'South Africa' => 'RSA',
			'Spain' => 'ESP',
			'Sri Lanka' => 'SRI',
			'Sudan' => 'SUD',
			'Suriname' => 'SUR',
			'Swaziland' => 'SWZ',
			'Sweden' => 'SWE',
			'Switzerland' => 'SUI',
			'Syria' => 'SYR',
			'Taiwan' => 'TWN',
			'Tajikistan' => 'TJK',
			'Tanzania' => 'TAN',
			'Thailand' => 'THA',
			'Togo' => 'TOG',
			'Tonga' => 'TGA',
			'Trinidad and Tobago' => 'TRI',
			'Tunisia' => 'TUN',
			'Turkey' => 'TUR',
			'Turkmenistan' => 'TKM',
			'Tuvalu' => 'TUV',
			'Uganda' => 'UGA',
			'Ukraine' => 'UKR',
			'United Arab Emirates' => 'UAE',
			'United Kingdom' => 'GBR',
			'United States of America' => 'USA',
			'Uruguay' => 'URU',
			'Uzbekistan' => 'UZB',
			'Vanuatu' => 'VAN',
			'Venezuela' => 'VEN',
			'Vietnam' => 'VIE',
			'Yemen' => 'YEM',
			'Zambia' => 'ZAM',
			'Zimbabwe' => 'ZIM',
		);

		if (array_key_exists($country, $nations)) {
			$nation = $nations[$country];
		} else {
			$nation = 'OTH';
			if ($country != '')
				trigger_error('Could not map country: ' . $country, E_USER_WARNING);
		}
		return $nation;
	}
} 