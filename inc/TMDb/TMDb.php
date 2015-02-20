<?php
/**
 * TMDb PHP API class - API 'themoviedb.org'
 * API Documentation: http://help.themoviedb.org/kb/api/
 * Documentation and usage in README file
 *
 * @author Jonas De Smet - Glamorous
 * @since 09.11.2009
 * @date 16.11.2012
 * @copyright Jonas De Smet - Glamorous
 * @version 1.5.1
 * @license BSD http://www.opensource.org/licenses/bsd-license.php
 */

class TMDb
{
	const POST = 'post';
	const GET = 'get';
	const HEAD = 'head';

	const IMAGE_BACKDROP = 'backdrop';
	const IMAGE_POSTER = 'poster';
	const IMAGE_PROFILE = 'profile';

	const API_VERSION = '3';
	const API_URL = 'api.themoviedb.org';
	const API_SCHEME = 'http://';
	const API_SCHEME_SSL = 'https://';

	const VERSION = '1.5.0';

	/**
	 * The API-key
	 *
	 * @var string
	 */
	protected $_apikey;

	/**
	 * The default language
	 *
	 * @var string
	 */
	protected $_lang;

	/**
	 * The TMDb-config
	 *
	 * @var object
	 */
	protected $_config;

	/**
	 * Stored Session Id
	 *
	 * @var string
	 */
	protected $_session_id;

	/**
	 * API Scheme
	 *
	 * @var string
	 */
	protected $_apischeme;

	/**
	 * Default constructor
	 *
	 * @param string $apikey			API-key recieved from TMDb
	 * @param string $defaultLang		Default language (ISO 3166-1)
	 * @param boolean $config			Load the TMDb-config
	 * @return void
	 */
	public function __construct($apikey, $default_lang = 'en', $config = FALSE, $scheme = TMDb::API_SCHEME)
	{
		$this->_apikey = (string) $apikey;
		$this->_apischeme = ($scheme == TMDb::API_SCHEME) ? TMDb::API_SCHEME : TMDb::API_SCHEME_SSL;
		$this->setLang($default_lang);

		if($config === TRUE)
		{
			$this->getConfiguration();
		}
	}

	/**
	 * Search a movie by querystring
	 *
	 * @param string $text				Query to search after in the TMDb database
	 * @param int $page					Number of the page with results (default first page)
	 * @param bool $adult				Whether of not to include adult movies in the results (default FALSE)
	 * @param mixed $lang				Filter the result with a language (ISO 3166-1) other then default, use FALSE to retrieve results from all languages
	 * @return TMDb result array
	 */
	public function searchMovie($query, $page = 1, $adult = FALSE, $year = NULL, $lang = NULL)
	{
		$params = array(
			'query' => $query,
			'page' => (int) $page,
			'language' => ($lang !== NULL) ? $lang : $this->getLang(),
			'include_adult' => (bool) $adult,
			'year' => $year,
		);
		return $this->_makeCall('search/movie', $params);
	}

	/**
	 * Retrieve all basic information for a particular movie
	 *
	 * @param mixed $id					TMDb-id or IMDB-id
	 * @param mixed $lang				Filter the result with a language (ISO 3166-1) other then default, use FALSE to retrieve results from all languages
	 * @return TMDb result array
	 */
	public function getMovie($id, $lang = NULL)
	{
		$params = array(
			'language' => ($lang !== NULL) ? $lang : $this->getLang(),
		);
		return $this->_makeCall('movie/'.$id, $params);
	}

	/**
	 * Retrieve all of the movie cast information for a particular movie
	 *
	 * @param mixed $id					TMDb-id or IMDB-id
	 * @return TMDb result array
	 */
	public function getMovieCast($id)
	{
		return $this->_makeCall('movie/'.$id.'/casts');
	}

	/**
	 * Retrieve all images for a particular movie
	 *
	 * @param mixed $id					TMDb-id or IMDB-id
	 * @param mixed $lang				Filter the result with a language (ISO 3166-1) other then default, use FALSE to retrieve results from all languages
	 * @return TMDb result array
	 */
	public function getMovieImages($id, $lang = NULL)
	{
		$params = array(
			'language' => ($lang !== NULL) ? $lang : $this->getLang(),
		);
		return $this->_makeCall('movie/'.$id.'/images', $params);
	}

	/**
	 * Authentication: retrieve authentication token
	 * More information about the authentication process: http://help.themoviedb.org/kb/api/user-authentication
	 *
	 * @return TMDb result array
	 */
	public function getAuthToken()
	{
		$result = $this->_makeCall('authentication/token/new');

		if( ! isset($result['request_token']))
		{
			if($this->getDebugMode())
			{
				throw new TMDbException('No valid request token from TMDb');
			}
			else
			{
				return FALSE;
			}
		}

		return $result;
	}

	/**
	 * Authentication: retrieve authentication session and set it to the class
	 * More information about the authentication process: http://help.themoviedb.org/kb/api/user-authentication
	 *
	 * @param string $token
	 * @return TMDb result array
	 */
	public function getAuthSession($token)
	{
		$params = array(
			'request_token' => $token,
		);

		$result = $this->_makeCall('authentication/session/new', $params);

		if(isset($result['session_id']))
		{
			$this->setAuthSession($result['session_id']);
		}

		return $result;
	}

	/**
	 * Authentication: set retrieved session id in the class for authenticated requests
	 * More information about the authentication process: http://help.themoviedb.org/kb/api/user-authentication
	 *
	 * @param string $session_id
	 */
	public function setAuthSession($session_id)
	{
		$this->_session_id = $session_id;
	}

	/**
	 * Get configuration from TMDb
	 *
	 * @return TMDb result array
	 */
	public function getConfiguration()
	{
		$config = $this->_makeCall('configuration');

		if( ! empty($config))
		{
			$this->setConfig($config);
		}

		return $config;
	}

	/**
	 * Get Image URL
	 *
	 * @param string $filepath			Filepath to image
	 * @param const $imagetype			Image type: TMDb::IMAGE_BACKDROP, TMDb::IMAGE_POSTER, TMDb::IMAGE_PROFILE
	 * @param string $size				Valid size for the image
	 * @return string
	 */
	public function getImageUrl($filepath, $imagetype, $size)
	{
		$config = $this->getConfig();

		if(isset($config['images']))
		{
			$base_url = $config['images']['base_url'];
			$available_sizes = $this->getAvailableImageSizes($imagetype);

			if(in_array($size, $available_sizes))
			{
				return $base_url.$size.$filepath;
			}
			else
			{
				throw new TMDbException('The size "'.$size.'" is not supported by TMDb');
			}
		}
		else
		{
			throw new TMDbException('No configuration available for image URL generation');
		}
	}

	/**
	 * Get available image sizes for a particular image type
	 *
	 * @param const $imagetype			Image type: TMDb::IMAGE_BACKDROP, TMDb::IMAGE_POSTER, TMDb::IMAGE_PROFILE
	 * @return array
	 */
	public function getAvailableImageSizes($imagetype)
	{
		$config = $this->getConfig();

		if(isset($config['images'][$imagetype.'_sizes']))
		{
			return $config['images'][$imagetype.'_sizes'];
		}
		else
		{
			throw new TMDbException('No configuration available to retrieve available image sizes');
		}
	}

	/**
	 * Get ETag to keep track of state of the content
	 *
	 * @param string $uri				Use an URI to know the version of it. For example: 'movie/550'
	 * @return string
	 */
	public function getVersion($uri)
	{
		$headers = $this->_makeCall($uri, NULL, NULL, TMDb::HEAD);
		return isset($headers['Etag']) ? $headers['Etag'] : '';
	}

	/**
	 * Makes the call to the API
	 *
	 * @param string $function			API specific function name for in the URL
	 * @param array $params				Unencoded parameters for in the URL
	 * @param string $session_id		Session_id for authentication to the API for specific API methods
	 * @param const $method				TMDb::GET or TMDb:POST (default TMDb::GET)
	 * @return TMDb result array
	 */
	private function _makeCall($function, $params = NULL, $session_id = NULL, $method = TMDb::GET)
	{
		$params = ( ! is_array($params)) ? array() : $params;
		$auth_array = array('api_key' => $this->_apikey);

		if($session_id !== NULL)
		{
			$auth_array['session_id'] = $session_id;
		}

		$url = $this->_apischeme.TMDb::API_URL.'/'.TMDb::API_VERSION.'/'.$function.'?'.http_build_query($auth_array, '', '&');

		if($method === TMDb::GET)
		{
			if(isset($params['language']) AND $params['language'] === FALSE)
			{
				unset($params['language']);
			}

			$url .= ( ! empty($params)) ? '&'.http_build_query($params, '', '&') : '';
		}

		$results = '{}';

		if (extension_loaded('curl'))
		{
			$headers = array(
				'Accept: application/json',
			);

			$ch = curl_init();

			if($method == TMDB::POST)
			{
				$json_string = json_encode($params);
				curl_setopt($ch,CURLOPT_POST, 1);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $json_string);
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: '.strlen($json_string);
			}
			elseif($method == TMDb::HEAD)
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($ch, CURLOPT_NOBODY, 1);
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$response = curl_exec($ch);

			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);

			$error_number = curl_errno($ch);
			$error_message = curl_error($ch);

			if($error_number > 0)
			{
				throw new TMDbException('Method failed: '.$function.' - '.$error_message);
			}

			curl_close($ch);
		}
		else
		{
			throw new TMDbException('CURL-extension not loaded');
		}

		$results = json_decode($body, TRUE);

		if(strpos($function, 'authentication/token/new') !== FALSE)
		{
			$parsed_headers = $this->_http_parse_headers($header);
			$results['Authentication-Callback'] = $parsed_headers['Authentication-Callback'];
		}

		if($results !== NULL)
		{
			return $results;
		}
		elseif($method == TMDb::HEAD)
		{
			return $this->_http_parse_headers($header);
		}
		else
		{
			throw new TMDbException('Server error on "'.$url.'": '.$response);
		}
	}

	/**
	 * Setter for the default language
	 *
	 * @param string $lang		(ISO 3166-1)
	 * @return void
	 */
	public function setLang($lang)
	{
		$this->_lang = $lang;
	}

	/**
	 * Setter for the TMDB-config
	 *
	 * $param array $config
	 * @return void
	 */
	public function setConfig($config)
	{
		$this->_config = $config;
	}

	/**
	 * Getter for the default language
	 *
	 * @return string
	 */
	public function getLang()
	{
		return $this->_lang;
	}

	/**
	 * Getter for the TMDB-config
	 *
	 * @return array
	 */
	public function getConfig()
	{
		if(empty($this->_config))
		{
			$this->_config = $this->getConfiguration();
		}

		return $this->_config;
	}

	/*
	 * Internal function to parse HTTP headers because of lack of PECL extension installed by many
	 *
	 * @param string $header
	 * @return array
	 */
	protected function _http_parse_headers($header)
	{
		$return = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
		foreach($fields as $field)
		{
			if(preg_match('/([^:]+): (.+)/m', $field, $match))
			{
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
				if( isset($return[$match[1]]) )
				{
					$return[$match[1]] = array($return[$match[1]], $match[2]);
				}
				else
				{
					$return[$match[1]] = trim($match[2]);
				}
			}
		}
		return $return;
	}
}

/**
 * TMDb Exception class
 *
 * @author Jonas De Smet - Glamorous
 */
class TMDbException extends Exception{}

?>