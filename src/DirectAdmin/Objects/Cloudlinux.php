<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;

use Omines\DirectAdmin\Context\UserContext;
use Omines\DirectAdmin\DirectAdminException;
use Omines\DirectAdmin\Objects\BaseObject;
use Omines\DirectAdmin\Utility\Conversion;

/**
 * Encapsulates DA Cloudlinux API.
 */
class Cloudlinux extends BaseObject {

	/** @var string . */
	public static $request_path = 'CMD_PLUGINS/phpselector/index.raw';

	/**
	 * Construct the object.
	 *
	 * @param UserContext $context Context within which the object is valid .
	 */
	public function __construct( UserContext $context ) {
		parent::__construct( $name = '', $context );
	}

	/** Undocumented.
	 *
	 * @param string $version .
	 *
	 * @return void
	 */
	public function setAccountPhpVersion( $version ) {
		$loginCookieJar = $this->getContext()->getConnection()->getLoginCookieJar();

		$csrfCookie = $this->getCsrfCookie();
		$csrftoken   = $csrfCookie->getValue();
		$loginCookieJar->setCookie( $csrfCookie );

		$parameters = [
			'command'                 => 'cloudlinux-selector',
			'method'                  => 'set',
			'params[interpreter]'     => 'php',
			'params[current-version]' => $version,
			'csrftoken'               => $csrftoken, // also required.
		];

		// https://server:2222/CMD_PLUGINS/phpselector/index.raw?c=send-request.
		$query_string = 'c=send-request';
		return $this->invokeApiPost( $query_string, $parameters, $loginCookieJar );
	}

	/** Undocumented.
	 *
	 * @param string   $version    .
	 * @param string[] $extensions .
	 *
	 * @return void
	 */
	public function setPhpVersionEnabledExtensions( $version, $extensions ) {
		$enabled_extensions = [];
		foreach ( $extensions as $extension ) {
			$enabled_extensions[ $extension ] = 'enabled';
		}
		$json_enabled_extensions = json_encode( $enabled_extensions );

		$loginCookieJar = $this->getContext()->getConnection()->getLoginCookieJar();

		$csrfCookie = $this->getCsrfCookie();
		$csrftoken   = $csrfCookie->getValue();
		$loginCookieJar->setCookie( $csrfCookie );

		$parameters = [
			'command'             => 'cloudlinux-selector',
			'method'              => 'set',
			'params[interpreter]' => 'php',
			'params[version]'     => $version,
			'params[extensions]'  => $json_enabled_extensions,
			'csrftoken'           => $csrftoken,
		];

		$query_string = 'c=send-request';
		return $this->invokeApiPost( $query_string, $parameters, $loginCookieJar );
	}

	/** Undocumented.
	 *
	 * @param string $version .
	 * @param string $options .
	 *
	 * @return void
	 */
	public function setPhpVersionOptions( $version, $options ) {
		$json_options = json_encode( $options );

		$loginCookieJar = $this->getContext()->getConnection()->getLoginCookieJar();

		$csrfCookie = $this->getCsrfCookie();
		$csrftoken   = $csrfCookie->getValue();
		$loginCookieJar->setCookie( $csrfCookie );

		$parameters = [
			'command'             => 'cloudlinux-selector',
			'method'              => 'set',
			'params[interpreter]' => 'php',
			'params[version]'     => $version,
			'params[options]'     => $json_options,
			'csrftoken'           => $csrftoken,
		];

		$query_string = 'c=send-request';
		return $this->invokeApiPost( $query_string, $parameters, $loginCookieJar );
	}

	/** Get FORGERY PROTECTION TOKEN.
	 *
	 * @return SetCookie
	 */
	public function getCsrfCookie() {
		// https://server:2222/CMD_PLUGINS/phpselector/index.raw?a=cookie
		$uri        = static::$request_path . '?a=cookie';
		$cookie_jar = new CookieJar();

		$result = $this->getContext()->getConnection()->rawRequestWithCookies(
			$method = 'POST',
			$uri,
			[], // $options.
			$cookie_jar
		);

		if ( ! empty( $result['error'] ) ) {
			throw new DirectAdminException(
				"$method to '$uri' failed: $result[details] ($result[text])"
			);
		}

		$csrf = $cookie_jar->getCookieByName( 'csrftoken' );
		if ( empty( $csrf  ) ) {
			throw new \Exception( 'csrftoken cookie not found' );
		}
		return $csrf;
	}

	/** Invokes the DirectAdmin API with specific options.
	 *
	 * @param string    $query_string   .
	 * @param array     $postParameters Optional. Form parameters
	 * @param CookieJar $cookie_jar     Optional.
	 *
	 * @return array
	 * @throws DirectAdminException If anything went wrong on the network level
	 */
	public function invokeApiPost(
		string $query_string,
		array $postParameters = [],
		$cookie_jar = null
	) {
		$uri = static::$request_path . "?$query_string";

		$connection = $this->getContext()->getConnection();

		$options = [ 'form_params' => $postParameters ];
		if ( ! empty( $cookie_jar ) ) {
			$options['cookies'] = $cookie_jar;

			// DA requires the referer and that this be from the same domain.
			// "The request was made without a referer header and will not be immediately followed. If you wish to follow this URL anyway, click the link to continue with the missing header CMD_PLUGINS/phpselector/index.raw/c=send-request&redirect=yes.
			$options[ 'headers'] = [ 'Referer' => $connection->getBaseUrl() . "/$uri" ];
		}

		$result = $connection->rawRequest(
			$method = 'POST', $uri, $options
		);

		// check native DA error.
		if ( ! empty( $result['error'] ) ) {
			throw new DirectAdminException(
				"$method to '$uri' failed: $result[details] ($result[text])");
		}

		// check cloudlinux json response.
		return Conversion::sanitizeArray( $result );
	}

}