<?php
/**
 *		Cookies.php - An object to represent cookies with webBot
 *
 *		This class helps dissecting the responses from HTTP requests
 *		facilitates easy access to: Status code, headers, and content
 *
 * @author Durendal
 * @license GPL
 * @link https://github.com/Durendal/webBot
 */
namespace Durendal\webBot;

use Durendal\webBot as webBot;

require_once 'Exceptions.php';

class Cookies
{
	private $cookies;
	private $cookieJar;
	private $parentHandle;

	/**
	 *   __construct(&$ch, $cookies = array(), $cookieJar = "cookies.txt")
	 *
	 *     Constructs a fresh Cookies object and sets any cookies passed to it.
	 *
	 * @param cURL $ch - cURL handle of the parent request
	 * @param array $cookies - An array of custom cookies to set.
	 * @param string $cookieJar - The location of the file to write/read cookies from
	 *
	 * @return void
	 */
	public function __construct($ch = NULL, $cookies = array(), $cookieJar = "cookies.txt") {
		$this->parentHandle = NULL;
		$this->setCookieJar($cookieJar);

		if($ch)
			$this->init($ch, $cookies);
	}

	public function __toString() {
		return "<HTTP Cookies - >";
	}

	public function setCookieJar($cookieJar) {
		$this->cookieJar = $cookieJar;
	}

	public function getCookieJar() {
		return $this->cookieJar;
	}

	public function init($ch, $cookies = array()) {
		$this->parentHandle = $ch;

		curl_setopt($this->parentHandle, CURLINFO_HEADER_OUT, TRUE);

		// Set cookie jar
		curl_setopt($this->parentHandle, CURLOPT_COOKIEJAR, $this->cookieJar);
		curl_setopt($this->parentHandle, CURLOPT_COOKIEFILE, $this->cookieJar);

		// Clear Cookies in cookie jar
		curl_setopt($this->parentHandle, CURLOPT_COOKIELIST, "SESS");

		// Populate Cookiejar with any custom submitted cookies
		foreach($cookies as $key => $value)
			$this->setCookie($key, $value);

		// Write cookies to cookie jar
		curl_setopt($this->parentHandle, CURLOPT_COOKIELIST, "FLUSH");

		$this->cookies = $this->getCookies();
	}

	/**
	 *	setCookie($cookie)
	 *
	 *		sets the cookie file to $cookie and rebuilds the curl handler.
	 *		note that if you already have an instance of the curlHandler
	 *		instantiated, you will need to rebuild it via rebuildHandle()
	 *		for this to take effect
	 *
	 * @param string $key - The name for the cookie being added
	 * @param string $value - The value of the cookie being added
	 *
	 * @return void
	 */
	public function setCookie($key, $value)
	{
		if($this->parentHandle == NULL)
			throw new webBot\UninitializedCookieException("Must Initialize Cookie Object before setting cookies.");

		curl_setopt($this->parentHandle, CURLOPT_COOKIELIST, sprintf("%s=%s", $key, $value));
		curl_setopt($this->parentHandle, CURLOPT_COOKIELIST, "FLUSH");

		$this->cookies = $this->getCookies();

	}

	/**
	 *    generateCookies()
	 *
	 *      Returns a string built from an array of Cookies
	 *
	 * @return string - A string containing the currently set cookies
	 */
	public function generateCookies($host)
	{
		if($this->parentHandle == NULL)
			throw new webBot\UninitializedCookieException("Must Initialize Cookie Object before setting cookies.");

		$cookieStr = "";

		foreach($this->cookies[$host] as $val)
			$cookieStr .= sprintf("%s=%s; ", $val['name'], $val['value']);

		return substr($cookieStr, 0, strlen($cookieStr)-1);
	}

	/**
	 *	getCookies()
	 *
	 *		returns the current set of cookies
	 *
	 * @return string
	 */
	public function getCookies()
	{
		if($this->parentHandle == NULL)
			throw new webBot\UninitializedCookieException("Must Initialize Cookie Object before setting cookies.");

		$cookies = curl_getinfo($this->parentHandle, CURLINFO_COOKIELIST);

		foreach($cookies as $i => $val) {
			$val = explode("\t", $val);
			if(count($val) == 7)
				$this->cookies[$val[0]][] = array('flag' => $val[1], 'path' => $val[2], 'secure' => $val[3], 'expiration' => $val[4], 'name' => $val[5], 'value' => $val[6]);
			unset($cookies[$i]);
		}

		return $this->cookies;
	}
}
