<?php

namespace ManiaControl\Utils;

use cURL\Request;
use cURL\Response;
use ManiaControl\ManiaControl;

/**
 * Reader Utility Class for efficient Web Requests
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2017 ManiaControl Team
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
	public static function getUrl($url, callable $function = null) {
		$request  = static::newRequest($url);
		$response = $request->send();
		if ($function) {
			static::performCallback($response, $function);
		}
		return $response;
	}

	/**
	 * @deprecated
	 * @see WebReader::getUrl()
	 */
	public static function loadUrl($url, callable $function = null) {
		if ($function) {
			return static::getUrl($url, $function);
		}
		return static::getUrl($url);
	}

	/**
	 * Create a new cURL Request for the given URL
	 *
	 * @param string $url
	 * @return Request
	 */
	protected static function newRequest($url) {
		$request = new Request($url);
		$options = $request->getOptions();
		$options->set(CURLOPT_TIMEOUT, 5) // timeout
		        ->set(CURLOPT_HEADER, false) // don't display response header
		        ->set(CURLOPT_CRLF, true) // linux line feed
		        ->set(CURLOPT_ENCODING, '') // accept encoding
		        ->set(CURLOPT_USERAGENT, 'ManiaControl v' . ManiaControl::VERSION) // user-agent
		        ->set(CURLOPT_RETURNTRANSFER, true) // return instead of output content
		        ->set(CURLOPT_AUTOREFERER, true); // follow redirects
		return $request;
	}

	/**
	 * Perform the given callback function with the response
	 *
	 * @param Response $response
	 * @param callable $function
	 */
	protected static function performCallback(Response $response, callable $function) {
		$content = $response->getContent();
		$error   = $response->getError()->getMessage();
		call_user_func($function, $content, $error);
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
		$request = static::newRequest($url);
		$request->getOptions()->set(CURLOPT_POST, true); // post method
		if ($content) {
			$request->getOptions()->set(CURLOPT_POSTFIELDS, $content); // post content field
		}
		$response = $request->send();
		if ($function) {
			static::performCallback($response, $function);
		}
		return $response;
	}
}
