<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Context;

use Omines\DirectAdmin\DA_Connection;
use Omines\DirectAdmin\DirectAdminException;
use Omines\DirectAdmin\Objects\Users\User;

/**
 * Encapsulates a contextual connection to a server for user functions.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class UserContext extends BaseContext
{
    /** @var User */
    private $user;

    /** Constructs the object.
     *
     * @param DA_Connection $connection A prepared connection
     * @param bool          $validate   Whether to check if the connection matches the context
     */
    public function __construct( DA_Connection $connection, $validate = false )
    {
        parent::__construct($connection);
        if ($validate) {
            $classMap = [
                DA_Connection::ACCOUNT_TYPE_ADMIN    => AdminContext::class,
                DA_Connection::ACCOUNT_TYPE_RESELLER => ResellerContext::class,
                DA_Connection::ACCOUNT_TYPE_USER     => self::class,
            ];
            if ($classMap[$this->getType()] != get_class($this)) {
                /* @codeCoverageIgnoreStart */
                throw new DirectAdminException('Validation mismatch on context construction');
                /* @codeCoverageIgnoreEnd */
            }
        }
    }

    /** Returns the type of the account (user/reseller/admin).
     *
     * @return string One of the DirectAdmin::ACCOUNT_TYPE_ constants describing the type of underlying account
     */
    public function getType()
    {
        return $this->getContextUser()->getType();
    }

    /** Returns the actual user object behind the context.
     *
     * @return User The user object behind the context
     */
    public function getContextUser()
    {
        if (!isset($this->user)) {
            $this->user = User::fromConfig($this->invokeApiGet('SHOW_USER_CONFIG'), $this);
        }
        return $this->user;
    }

    /** Returns the username of the current context.
     *
     * @return string Username for the current context
     */
    public function getUsername()
    {
        return $this->getConnection()->getUsername();
    }
}
