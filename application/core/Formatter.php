<?php

namespace ManiaControl;

/**
 * Class offering Methods to format Texts and Values
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Formatter {

	/**
	 * Format the given Time (in Milliseconds)
	 *
	 * @param int $time
	 * @return string
	 */
	public static function formatTime($time) {
		if (!is_int($time)) {
			$time = (int)$time;
		}
		$milliseconds = $time % 1000;
		$seconds      = floor($time / 1000);
		$minutes      = floor($seconds / 60);
		$hours        = floor($minutes / 60);
		$minutes -= $hours * 60;
		$seconds -= $hours * 60 + $minutes * 60;
		$format = ($hours > 0 ? $hours . ':' : '');
		$format .= ($hours > 0 && $minutes < 10 ? '0' : '') . $minutes . ':';
		$format .= ($seconds < 10 ? '0' : '') . $seconds . ':';
		$format .= ($milliseconds < 100 ? '0' : '') . ($milliseconds < 10 ? '0' : '') . $milliseconds;
		return $format;
	}

	/**
	 * Format a Time to H:M:S
	 *
	 * @param int $seconds
	 * @return string
	 */
	public static function formatTimeHMS($seconds) {
		$minutes = floor($seconds / 60);
		$hours   = floor($minutes / 60);
		$minutes -= $hours * 60;
		$seconds -= ($hours * 3600 + $minutes * 60);

		$hours   = ($hours < 10 ? '0' : '') . $hours;
		$minutes = ($minutes < 10 ? '0' : '') . $minutes;;
		$seconds = ($seconds < 10 ? '0' : '') . $seconds;;

		return $hours . ":" . $minutes . ":" . $seconds;
	}

	/**
	 * Format an elapsed time String (2 days ago...) by a given timestamp
	 *
	 * @param int $ptime
	 * @return string
	 */
	public static function time_elapsed_string($ptime) {
		$etime = time() - $ptime;

		if ($etime < 1) {
			return '0 seconds';
		}

		$a = array(12 * 30 * 24 * 60 * 60 => 'year', 30 * 24 * 60 * 60 => 'month', 24 * 60 * 60 => 'day', 60 * 60 => 'hour', 60 => 'minute', 1 => 'second');

		foreach ($a as $secs => $str) {
			$d = $etime / $secs;
			if ($d >= 1) {
				$r = round($d);
				return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
			}
		}
		return '';
	}

	/**
	 * Format the given Time (Seconds) to hh:mm:ss
	 *
	 * @param int $seconds
	 * @return string
	 */
	public static function formatTimeH($seconds) {
		return date("H:i:s", $seconds);
	}

	/**
	 * Convert the given Time (Seconds) to MySQL Timestamp
	 *
	 * @param int $seconds
	 * @return string
	 */
	public static function formatTimestamp($seconds) {
		return date("Y-m-d H:i:s", $seconds);
	}

	/**
	 * Remove possibly dangerous Codes
	 * (Dangerous Codes are Links and Formats that might screw up the following Styling)
	 *
	 * @param string $string
	 * @return string
	 */
	public static function stripDirtyCodes($string) {
		$string = self::stripLinks($string);
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[ow<>]/iu', '$1', $string);
		return $string;
	}

	/**
	 * Remove Links from the String
	 *
	 * @param string $string
	 * @return string
	 */
	public static function stripLinks($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[hlp](?:\[.*?\])?(.*?)(?:\$[hlp]|(\$z)|$)/iu', '$1$2$3', $string);
	}

	/**
	 * Remove all Codes from the String
	 *
	 * @param string $string
	 * @return string
	 */
	public static function stripCodes($string) {
		$string = self::stripLinks($string);
		$string = self::stripColors($string);
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		return $string;
	}

	/**
	 * Remove Colors from the String
	 *
	 * @param string $string
	 * @return string
	 */
	public static function stripColors($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$(?:g|[0-9a-f][^\$]{0,2})/iu', '$1', $string);
	}

	/**
	 * Map Country Names to 3-letter Nation Abbreviations
	 * Created by Xymph
	 * Based on http://en.wikipedia.org/wiki/List_of_IOC_country_codes
	 * See also http://en.wikipedia.org/wiki/Comparison_of_IOC,_FIFA,_and_ISO_3166_country_codes
	 *
	 * @param string $country
	 * @return string
	 */
	public static function mapCountry($country) {
		$nations = array('Afghanistan' => 'AFG', 'Albania' => 'ALB', 'Algeria' => 'ALG', 'Andorra' => 'AND', 'Angola' => 'ANG', 'Argentina' => 'ARG', 'Armenia' => 'ARM', 'Aruba' => 'ARU', 'Australia' => 'AUS', 'Austria' => 'AUT', 'Azerbaijan' => 'AZE', 'Bahamas' => 'BAH', 'Bahrain' => 'BRN', 'Bangladesh' => 'BAN', 'Barbados' => 'BAR', 'Belarus' => 'BLR', 'Belgium' => 'BEL', 'Belize' => 'BIZ', 'Benin' => 'BEN', 'Bermuda' => 'BER', 'Bhutan' => 'BHU', 'Bolivia' => 'BOL', 'Bosnia&Herzegovina' => 'BIH', 'Botswana' => 'BOT', 'Brazil' => 'BRA', 'Brunei' => 'BRU', 'Bulgaria' => 'BUL', 'Burkina Faso' => 'BUR', 'Burundi' => 'BDI', 'Cambodia' => 'CAM', 'Cameroon' => 'CAR', // actually CMR
			'Canada' => 'CAN', 'Cape Verde' => 'CPV', 'Central African Republic' => 'CAF', 'Chad' => 'CHA', 'Chile' => 'CHI', 'China' => 'CHN', 'Chinese Taipei' => 'TPE', 'Colombia' => 'COL', 'Congo' => 'CGO', 'Costa Rica' => 'CRC', 'Croatia' => 'CRO', 'Cuba' => 'CUB', 'Cyprus' => 'CYP', 'Czech Republic' => 'CZE', 'Czech republic' => 'CZE', 'DR Congo' => 'COD', 'Denmark' => 'DEN', 'Djibouti' => 'DJI', 'Dominica' => 'DMA', 'Dominican Republic' => 'DOM', 'Ecuador' => 'ECU', 'Egypt' => 'EGY', 'El Salvador' => 'ESA', 'Eritrea' => 'ERI', 'Estonia' => 'EST', 'Ethiopia' => 'ETH', 'Fiji' => 'FIJ', 'Finland' => 'FIN', 'France' => 'FRA', 'Gabon' => 'GAB', 'Gambia' => 'GAM', 'Georgia' => 'GEO', 'Germany' => 'GER', 'Ghana' => 'GHA', 'Greece' => 'GRE', 'Grenada' => 'GRN', 'Guam' => 'GUM', 'Guatemala' => 'GUA', 'Guinea' => 'GUI', 'Guinea-Bissau' => 'GBS', 'Guyana' => 'GUY', 'Haiti' => 'HAI', 'Honduras' => 'HON', 'Hong Kong' => 'HKG', 'Hungary' => 'HUN', 'Iceland' => 'ISL', 'India' => 'IND', 'Indonesia' => 'INA', 'Iran' => 'IRI', 'Iraq' => 'IRQ', 'Ireland' => 'IRL', 'Israel' => 'ISR', 'Italy' => 'ITA', 'Ivory Coast' => 'CIV', 'Jamaica' => 'JAM', 'Japan' => 'JPN', 'Jordan' => 'JOR', 'Kazakhstan' => 'KAZ', 'Kenya' => 'KEN', 'Kiribati' => 'KIR', 'Korea' => 'KOR', 'Kuwait' => 'KUW', 'Kyrgyzstan' => 'KGZ', 'Laos' => 'LAO', 'Latvia' => 'LAT', 'Lebanon' => 'LIB', 'Lesotho' => 'LES', 'Liberia' => 'LBR', 'Libya' => 'LBA', 'Liechtenstein' => 'LIE', 'Lithuania' => 'LTU', 'Luxembourg' => 'LUX', 'Macedonia' => 'MKD', 'Malawi' => 'MAW', 'Malaysia' => 'MAS', 'Mali' => 'MLI', 'Malta' => 'MLT', 'Mauritania' => 'MTN', 'Mauritius' => 'MRI', 'Mexico' => 'MEX', 'Moldova' => 'MDA', 'Monaco' => 'MON', 'Mongolia' => 'MGL', 'Montenegro' => 'MNE', 'Morocco' => 'MAR', 'Mozambique' => 'MOZ', 'Myanmar' => 'MYA', 'Namibia' => 'NAM', 'Nauru' => 'NRU', 'Nepal' => 'NEP', 'Netherlands' => 'NED', 'New Zealand' => 'NZL', 'Nicaragua' => 'NCA', 'Niger' => 'NIG', 'Nigeria' => 'NGR', 'Norway' => 'NOR', 'Oman' => 'OMA', 'Other Countries' => 'OTH', 'Pakistan' => 'PAK', 'Palau' => 'PLW', 'Palestine' => 'PLE', 'Panama' => 'PAN', 'Paraguay' => 'PAR', 'Peru' => 'PER', 'Philippines' => 'PHI', 'Poland' => 'POL', 'Portugal' => 'POR', 'Puerto Rico' => 'PUR', 'Qatar' => 'QAT', 'Romania' => 'ROM', // actually ROU
			'Russia' => 'RUS', 'Rwanda' => 'RWA', 'Samoa' => 'SAM', 'San Marino' => 'SMR', 'Saudi Arabia' => 'KSA', 'Senegal' => 'SEN', 'Serbia' => 'SCG', // actually SRB
			'Sierra Leone' => 'SLE', 'Singapore' => 'SIN', 'Slovakia' => 'SVK', 'Slovenia' => 'SLO', 'Somalia' => 'SOM', 'South Africa' => 'RSA', 'Spain' => 'ESP', 'Sri Lanka' => 'SRI', 'Sudan' => 'SUD', 'Suriname' => 'SUR', 'Swaziland' => 'SWZ', 'Sweden' => 'SWE', 'Switzerland' => 'SUI', 'Syria' => 'SYR', 'Taiwan' => 'TWN', 'Tajikistan' => 'TJK', 'Tanzania' => 'TAN', 'Thailand' => 'THA', 'Togo' => 'TOG', 'Tonga' => 'TGA', 'Trinidad and Tobago' => 'TRI', 'Tunisia' => 'TUN', 'Turkey' => 'TUR', 'Turkmenistan' => 'TKM', 'Tuvalu' => 'TUV', 'Uganda' => 'UGA', 'Ukraine' => 'UKR', 'United Arab Emirates' => 'UAE', 'United Kingdom' => 'GBR', 'United States of America' => 'USA', 'Uruguay' => 'URU', 'Uzbekistan' => 'UZB', 'Vanuatu' => 'VAN', 'Venezuela' => 'VEN', 'Vietnam' => 'VIE', 'Yemen' => 'YEM', 'Zambia' => 'ZAM', 'Zimbabwe' => 'ZIM');
		if (array_key_exists($country, $nations)) {
			return $nations[$country];
		}
		if ($country) {
			trigger_error("Couldn't map Country: '{$country}'!");
		}
		return 'OTH';
	}
}
