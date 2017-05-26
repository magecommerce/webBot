<?php
/**
 *		Response.php - An object to hold HTTP Responses
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

require_once 'Headers.php';

class Response
{

	/**
	 * @var int $status - The status code of the response
	 * @var string $content - The contents of the response
	 * @var array $headers - The headers of the response
	 * @var int $uid - The UID of the response
	 */
	private $status;
	private $content;
	private $headers;
	private $uid;
	private $parentHandle;

	/**
	 *	__construct($curlData)
	 *
	 *		Returns the status code of the request
	 *
	 * @param string $curlData - The response from the curl_exec() call, including headers and content.
	 *
	 * @return string
	 */
	public function __construct($ch, $response)
	{
		$this->parentHandle = $ch;
		list($headers, $this->content) = explode("\r\n\r\n", $response, 2);
		$this->status = curl_getinfo($this->parentHandle, CURLINFO_HTTP_CODE);
		//$headers = array_filter(explode("\n", curl_getinfo($this->parentHandle, CURLINFO_HEADER_OUT)), function($value) { return $value !== '' && $value !== ' ' && strlen($value) != 1; });
		$headers = http_parse_headers($headers);
		//$this->status = substr(array_shift($this->headers), 9, 3);
		$this->headers = new webBot\Headers($headers);
		$this->uid = hash('md5', sprintf("%s%d", $this->content, time()));
	}

	public function __toString() {
		return "<HTTP Response - >";
	}

	/**
	 *	status()
	 *
	 *		Returns the status code of the request
	 *
	 * @return string
	 */
	public function status()
	{
		return $this->status;
	}

	/**
	 *	content()
	 *
	 *		Returns the content of the request
	 *
	 * @return string
	 */
	public function content()
	{
		return $this->content;
	}

	/**
	 *	headers()
	 *
	 *		Returns an array of the response headers from the request
	 *
	 * @return array
	 */
	public function headers()
	{
		return $this->headers;
	}

	/**
	 *	uid()
	 *
	 *		Returns a Unique ID of the request that is generated by creating an MD5 hash of the content and a timestamp.
	 *
	 * @return string
	 */
	public function uid()
	{
		return $this->uid;
	}

}
/*
	Graciously borrowed from http://php.net/manual/en/function.http-parse-headers.php#112986
*/
if (!function_exists('http_parse_headers'))
{
	function http_parse_headers($raw_headers)
	{
		$headers = array();
		$key = ''; // [+]

		foreach(explode("\n", $raw_headers) as $i => $h)
		{
			$h = explode(':', $h, 2);

			if (isset($h[1]))
			{
				if (!isset($headers[$h[0]]))
					$headers[$h[0]] = trim($h[1]);
				elseif (is_array($headers[$h[0]]))
				{
					// $tmp = array_merge($headers[$h[0]], array(trim($h[1]))); // [-]
					// $headers[$h[0]] = $tmp; // [-]
					$headers[$h[0]] = array_merge($headers[$h[0]], array(trim($h[1]))); // [+]
				}
				else
				{
					// $tmp = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [-]
					// $headers[$h[0]] = $tmp; // [-]
					$headers[$h[0]] = array_merge(array($headers[$h[0]]), array(trim($h[1]))); // [+]
				}

				$key = $h[0]; // [+]
			}
			else // [+]
			{ // [+]
				if (substr($h[0], 0, 1) == "\t") // [+]
					$headers[$key] .= "\r\n\t".trim($h[0]); // [+]
				elseif (!$key) // [+]
					$headers[0] = trim($h[0]);trim($h[0]); // [+]
			} // [+]
		}

		return $headers;
	}
}

?>
