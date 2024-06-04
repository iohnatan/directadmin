<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\TransferException;

use Omines\DirectAdmin\Context\AdminContext;
use Omines\DirectAdmin\Context\ResellerContext;
use Omines\DirectAdmin\Context\UserContext;
use Omines\DirectAdmin\Utility\Conversion;

/** DirectAdmin API main class, encapsulating a specific account connection to a single server.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DA_Connection
{
	const ACCOUNT_TYPE_ADMIN = 'admin';
	const ACCOUNT_TYPE_RESELLER = 'reseller';
	const ACCOUNT_TYPE_USER = 'user';

	/** @var string */
	private $authenticatedUser;

	/** @var string */
	private $username;

	/** @var string */
	private $password;

	/** @var string */
	private $baseUrl;

	/** @var Client */
	private $connection;

	/** Connects to DirectAdmin with an admin account.
	 *
	 * @param string $url The base URL of the DirectAdmin server
	 * @param string $username The username of the account
	 * @param string $password The password of the account
	 * @param bool $validate Whether to ensure the account exists and is of the correct type
	 * @return AdminContext
	 */
	public static function connectAdmin($url, $username, $password, $validate = false)
	{
		return new AdminContext(new self($url, $username, $password), $validate);
	}

	/** Connects to DirectAdmin with a reseller account.
	 *
	 * @param string $url The base URL of the DirectAdmin server
	 * @param string $username The username of the account
	 * @param string $password The password of the account
	 * @param bool $validate Whether to ensure the account exists and is of the correct type
	 * @return ResellerContext
	 */
	public static function connectReseller($url, $username, $password, $validate = false)
	{
		return new ResellerContext(new self($url, $username, $password), $validate);
	}

	/** Connects to DirectAdmin with a user account.
	 *
	 * @param string $url The base URL of the DirectAdmin server
	 * @param string $username The username of the account
	 * @param string $password The password of the account
	 * @param bool $validate Whether to ensure the account exists and is of the correct type
	 * @return UserContext
	 */
	public static function connectUser($url, $username, $password, $validate = false)
	{
		return new UserContext(new self($url, $username, $password), $validate);
	}

	/** Creates a connection wrapper to DirectAdmin as the specified account.
	 *
	 * @param string $url The base URL of the DirectAdmin server
	 * @param string $username The username of the account
	 * @param string $password The password of the account
	 */
	protected function __construct($url, $username, $password)
	{
		$accounts = explode('|', $username);
		$this->authenticatedUser = current($accounts);
		$this->username = end($accounts);
		$this->password = $password;
		$this->baseUrl = rtrim($url, '/') . '/';
		$this->connection = new Client([
			'base_uri' => $this->baseUrl,
			'auth' => [$username, $password],
		]);
	}

	/** @var CookieJar */
	protected $loginCookieJar;

	public function get_login_cookies() {
		// TODO:cookies are not returned, is a header that comes with the session id.
		// https://forum.directadmin.com/threads/directadmin-v1-661.70363/page-2
		// https://petstore.swagger.io/?url=https://demo.directadmin.com:2222/docs/swagger.json#/Session%20control/post_api_login
		// https://petstore.swagger.io/?url=https://demo.directadmin.com:2222/docs/swagger.json#/Session%20control/post_api_session_login_as_switch.

		if ( ! empty( $loginCookieJar ) ) {
			return $this->loginCookieJar;
		}

		$password = $this->getPassword();

		if ( ! $this->isManaged() ) {
			// doesn't needs to be authenticated, but there's no problem is auth is send.
			$uri = '/api/login'; // EX /CMD_LOGIN.

			$username = $this->getAuthenticatedUser();

			$options = [ 'json' =>
				[
					'username' => $username,
					'password' => $password,
				],
			];
		} else {
			// needs to be authenticated.
			$uri = '/api/session/login-as/switch';

			$username = $this->getUsername();

			$options = [ 'json' =>
				[
					'username' => $username
				],
			];
		}

		$out_cookie_jar = new CookieJar();

		// make POST raw request with out cookies.
		$result = $this->rawRequestWithCookies(
			$method = 'POST', $uri, $options, $out_cookie_jar
		);

		$this->loginCookieJar = $out_cookie_jar;
		return $this->loginCookieJar;
	}

	public function OLD_getLoginCookieJar() {
		if ( ! empty( $loginCookieJar ) ) {
			return $this->loginCookieJar;
		}

		$uri        = '/CMD_LOGIN';

		$password = $this->getPassword();
		$username = $this->getUsername();
		if ( $this->isManaged() ) {
			$username = $this->getAuthenticatedUser() . "|$username";
		}

		$postParameters = [
			'username' => $username,
			'password' => $password,
			'json'     => 'yes',
		];

		$options = [ 'form_params' => $postParameters ];

		$cookie_jar = new CookieJar();

		$result = $this->rawRequestWithCookies(
			$method = 'POST', $uri, $options, $cookie_jar
		);

		if ( ! empty( $result['error'] ) ) {
			throw new DirectAdminException(
				"$method to '$uri' failed: $result[details] ($result[text])"
			);
		}

		$this->loginCookieJar = $cookie_jar;
		return $this->loginCookieJar;
	}

    /** Whether the connection is managed by the "login-as" feature.
	 *
	 * https://www.directadmin.com/api.php
	 *
     * @return bool
     */
    protected function isManaged()
    {
        return $this->getUsername() !== $this->getAuthenticatedUser();
    }

	/** Returns the base url behind the current connection.
	 *
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	/** Returns the authenticated username behind the current connection.
	 *
	 * @return string
	 */
	public function getAuthenticatedUser() {
		return $this->authenticatedUser;
	}

	/** Returns the username behind the current connection.
	 *
	 * @return string Currently logged in user's username
	 */
	public function getUsername() {
		return $this->username;
	}

	/** Returns the password behind the current connection.
	 *
	 * @return string Currently logged in user's password
	 */
	public function getPassword() {
		return $this->password;
	}

	/** Invokes the DirectAdmin API with specific options.
	 *
	 * @param string $method HTTP method to use (ie. GET or POST)
	 * @param string $command DirectAdmin API command to invoke
	 * @param array $options Guzzle options to use for the call
	 * @return array The unvalidated response
	 * @throws DirectAdminException If anything went wrong on the network level
	 */
	public function invokeApi($method, $command, $options = [])
	{
		$result = $this->rawRequest($method, '/CMD_API_' . $command, $options);
		if (!empty($result['error'])) {
			throw new DirectAdminException("$method to $command failed: $result[details] ($result[text])");
		}
		return Conversion::sanitizeArray($result);
	}

	/** Invokes the DirectAdmin API with specific options.
	 *
	 * @param string $method HTTP method to use (ie. GET or POST)
	 * @param string $command DirectAdmin API command to invoke
	 * @param array $options Guzzle options to use for the call
	 * @return array The unvalidated response
	 * @throws DirectAdminException If anything went wrong on the network level
	 */
	public function invoke_new_api($method, $command, $options = [])
	{
		$result = $this->rawRequest($method, '/api/' . $command, $options);
		if (!empty($result['error'])) {
			throw new DirectAdminException("$method to $command failed: $result[details] ($result[text])");
		}
		return Conversion::sanitizeArray($result);
	}

	/** Returns a clone of the connection logged in as a managed user or reseller.
	 *
	 * @param string $username
	 * @return DA_Connection
	 */
	public function loginAs($username)
	{
		// DirectAdmin format is to just pipe the accounts together under the master password
		return new self(
			$this->baseUrl,
			$this->authenticatedUser . "|{$username}",
			$this->password
		);
	}

	/** Sends a raw request to DirectAdmin.
	 *
	 * @param string    $method                      .
	 * @param string    $uri                         URI string.
	 * @param array     $options                     Request Options to apply. See \GuzzleHttp\RequestOptions.
	 *                                               https://docs.guzzlephp.org/en/stable/request-options.html
	 * @param CookieJar $cookie_jar                  Optional.
	 *
	 * @return array
	 */
	public function rawRequestWithCookies(
		string $method, string $uri, array $options, &$cookie_jar = null
	) {
		// https://docs.guzzlephp.org/en/stable/request-options.html#cookies.
		$options['cookies'] = $cookie_jar;
		return self::rawRequest( $method, $uri, $options );
	}

	/** Sends a raw request to DirectAdmin.
	 *
	 * @param string $method  .
	 * @param string $uri     URI string.
	 * @param array  $options Request options to apply. See \GuzzleHttp\RequestOptions.
	 *                        https://docs.guzzlephp.org/en/stable/request-options.html
	 *
	 * @return array
	 */
	public function rawRequest( string $method, string $uri, array $options )
	{
		try {
			$response = $this->connection->request( $method, $uri, $options );

			// useful for debug, show if 'unauthorized' for example.
			$xdirectadmin_header = $response->getHeader('X-Directadmin');

			$body = $response->getBody()->getContents();
			if ($response->getHeader('Content-Type')[0] == 'text/html') {
				throw new DirectAdminException(
					sprintf(
						'DirectAdmin API returned text/html to %s %s containing "%s"',
						$method, $uri, strip_tags( $body ) )
					);
			}

			return Conversion::responseToArray($body);
		} catch (TransferException $exception) {
			// Rethrow anything that causes a network issue
			throw new DirectAdminException(sprintf('%s request to %s failed', $method, $uri), 0, $exception);
		}
	}

	/** Sends a raw request to DirectAdmin, but not checking if the response 'Content-Type' is valid.
	 *
	 * @param string    $method                      .
	 * @param string    $uri                         URI string.
	 * @param array     $options                     Request Options to apply. See \GuzzleHttp\RequestOptions.
	 *                                               https://docs.guzzlephp.org/en/stable/request-options.html
	 * @param CookieJar $cookie_jar                  Optional.
	 *
	 * @return string
	 */
	public function rawRequestWithoutValidation(
		$method, $uri, $options, &$cookie_jar = null
	) {
		try {
			// https://docs.guzzlephp.org/en/stable/request-options.html#cookies.
			$options['cookies'] = $cookie_jar;

			$response = $this->connection->request($method, $uri, $options);

			return $response->getBody()->getContents();
		} catch (TransferException $exception) {
			// Rethrow anything that causes a network issue
			throw new DirectAdminException(sprintf('%s request to %s failed', $method, $uri), 0, $exception);
		}
	}
}
