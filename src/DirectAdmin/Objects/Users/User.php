<?php
/**
 * DirectAdmin
 * (c) Omines Internetbureau B.V.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DirectAdmin\Objects\Users;

use Omines\DirectAdmin\Context\BaseContext;
use Omines\DirectAdmin\DirectAdmin;
use Omines\DirectAdmin\DirectAdminException;
use Omines\DirectAdmin\Objects\Object;

/**
 * User
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com
 */
class User extends Object
{
    /** @var string */
    protected $username;

    /** @var array */
    protected $config;

    public function __construct($name, BaseContext $context, $config = null)
    {
        parent::__construct($context);
        $this->username = $name;
        $this->config = $config;
    }

    /**
     * @return string The username.
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string|null The default domain for the user, if any.
     */
    public function getDefaultDomain()
    {
        return $this->getConfig('domain');
    }

    public function getDomains()
    {
        return $this->getContext()->getDomains($this);
    }

    /**
     * @return string The user type, as one of the USERTYPE_ constants in the DirectAdmin class.
     */
    public function getType()
    {
        return $this->getConfig('usertype');
    }

    /**
     * Internal function to safe guard config changes and cache them.
     *
     * @param string $item Config item to retrieve.
     * @return mixed The value of the config item, or NULL.
     */
    private function getConfig($item)
    {
        if(!isset($this->config))
            $this->reload();
        return isset($this->config[$item]) ? $this->config[$item] : null;
    }

    /**
     * Reloads the current user config from the server, if it has been changed since last retrieved.
     */
    public function reload()
    {
        $this->config = $this->getContext()->invokeGet('SHOW_USER_CONFIG', ['user' => $this->username]);
    }

    /**
     * Constructs the correct object from the given user config.
     *
     * @param array $config The raw config from DirectAdmin.
     * @param BaseContext $context The context within which the config was retrieved.
     * @return Admin|Reseller|User The correct object.
     * @throws DirectAdminException If the user type could not be determined.
     */
    public static function fromConfig($config, BaseContext $context)
    {
        $name = $config['username'];
        switch($config['usertype'])
        {
            case DirectAdmin::USERTYPE_USER:
                return new User($name, $context, $config);
            case DirectAdmin::USERTYPE_RESELLER:
                return new Reseller($name, $context, $config);
            case DirectAdmin::USERTYPE_ADMIN:
                return new Admin($name, $context, $config);
            default:
                throw new DirectAdminException("Unknown user type $config[usertype]");
        }
    }
}