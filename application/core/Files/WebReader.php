<?php

namespace ManiaControl\Files;

use cURL\Request;
use cURL\Response;
use ManiaControl\ManiaControl;

/**
 * Reader Utility Class for efficient Web Requests
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class WebReader {

	/**
	 * Load a URL via GET
	 *
	 * @param string   $url
	 * @param callable $function
	 * @return Response
	 */
	public static function loadUrl($url, callable $function = null) {
		$request  = static::newRequest($url);
		$response = $request->send();
		if (!is_null($function)) {
			$content = $response->getContent();
			$error   = $response->getError()
			                    ->getMessage();
			call_user_func($function, $content, $error);
		}
		return $response;
	}

	/**
	 * Create a new cURL Request for the given URL
	 *
	 * @param string $url
	 * @return Request
	 */
	protected static function newRequest($url) {
		$request = new Request($url);
		$request->getOptions()
		        ->set(CURLOPT_TIMEOUT, 10)
		        ->set(CURLOPT_HEADER, false) // don't display response header
		        ->set(CURLOPT_CRLF, true) // linux line feed
		        ->set(CURLOPT_ENCODING, '') // accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION) // user-agent
		        ->set(CURLOPT_RETURNTRANSFER, true) // return instead of output content
		        ->set(CURLOPT_AUTOREFERER, true); // follow redirects
		return $request;
	}

	/**
	 * Load a URL via POST
	 *
	 * @param string   $url
	 * @param string   $content
	 * @param callable $function
	 * @return Response
	 */
	public static function postUrl($url, $content = null, callable $function = null) {
		$request = static::newRequest($url)
		                 ->set(CURLOPT_POST, true); // post method
		if ($content) {
			$request->set(CURLOPT_POSTFIELDS, $content); // post content field
		}
		$response = $request->send();
		if (!is_null($function)) {
			$content = $response->getContent();
			$error   = $response->getError()
			                    ->getMessage();
			call_user_func($function, $content, $error);
		}
		return $response;
	}
}
