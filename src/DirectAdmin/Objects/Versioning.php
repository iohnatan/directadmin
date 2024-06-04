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
class Versioning extends BaseObject {

	/** Construct the object.
	 *
	 * @param UserContext $context Context within which the object is valid .
	 */
	public function __construct( UserContext $context ) {
		parent::__construct( $name = '', $context );
	}

	/** Get Directadmin versions info (admins only).
	 *
	 */
	public function version()
	{
		$value = $this->getContext()->invoke_new_api_get( 'version' );
		return $value;
	}

}