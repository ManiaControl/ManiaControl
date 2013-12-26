<?php

namespace ManiaControl;

/**
 * Class offering methods to format texts and values
 *
 * @author steeffeen & kremsy
 */
abstract class Formatter {

	/**
	 * Formats the given time (milliseconds)
	 *
	 * @param int $time        	
	 * @return string
	 */
	public static function formatTime($time) {
		if (!is_int($time)) {
			$time = (int) $time;
		}
		$milliseconds = $time % 1000;
		$seconds = floor($time / 1000);
		$minutes = floor($seconds / 60);
		$hours = floor($minutes / 60);
		$minutes -= $hours * 60;
		$seconds -= $hours * 60 + $minutes * 60;
		$format = ($hours > 0 ? $hours . ':' : '');
		$format .= ($hours > 0 && $minutes < 10 ? '0' : '') . $minutes . ':';
		$format .= ($seconds < 10 ? '0' : '') . $seconds . ':';
		$format .= ($milliseconds < 100 ? '0' : '') . ($milliseconds < 10 ? '0' : '') . $milliseconds;
		return $format;
	}

	/**
	 * Formats the given time (seconds) to hh:mm:ss
	 *
	 * @param int $seconds        	
	 * @return string
	 */
	public static function formatTimeH($seconds) {
		return gmdate("H:i:s", $seconds);
	}

	/**
	 * Convert the given time (seconds) to mysql timestamp
	 *
	 * @param int $seconds        	
	 * @return string
	 */
	public static function formatTimestamp($seconds) {
		return date("Y-m-d H:i:s", $seconds);
	}

	/**
	 *  Strip all codes except colors from the string
	 */
	public static function stripCodesWithoutColors($string) { //Todo don't remove $i, $s
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		$string = self::stripLinks($string);
		return $string;
	}

	/**
	 * Strip $codes from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	public static function stripCodes($string) {
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		$string = self::stripLinks($string);
		$string = self::stripColors($string);
		return $string;
	}

	/**
	 * Remove link codes from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	public static function stripLinks($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[hlp](?:\[.*?\])?(.*?)(?:\$[hlp]|(\$z)|$)/iu', '$1$2$3', $string);
	}

	/**
	 * Remove colors from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	static function stripColors($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$(?:g|[0-9a-f][^\$]{0,2})/iu', '$1', $string);
	}

	/**
	 * Map country names to 3-letter Nation abbreviations
	 * Created by Xymph
	 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
	 * See also http://en.wikipedia.org/wiki/Comparison_of_IOC,_FIFA,_and_ISO_3166_country_codes
	 */
	static function mapCountry($country) {

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
