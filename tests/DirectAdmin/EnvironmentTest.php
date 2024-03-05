<?php

/*
 * DirectAdmin API Client
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Omines\DirectAdmin\DA_Connection;

/**
 * Tests for responses to invalid environment configuration.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class EnvironmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Omines\DirectAdmin\DirectAdminException
     */
    public function testCorruptedUrl()
    {
        $admin = DA_Connection::connectAdmin('noproto://www.google.com/', 'username', 'password');
        $admin->getContextUser()->getType();
    }

    /**
     * @expectedException \Omines\DirectAdmin\DirectAdminException
     */
    public function testInvalidUsername()
    {
        $admin = DA_Connection::connectAdmin(DIRECTADMIN_URL, '_invalid', MASTER_ADMIN_PASSWORD);
        $admin->getContextUser()->getType();
    }

    /**
     * @expectedException \Omines\DirectAdmin\DirectAdminException
     */
    public function testInvalidPassword()
    {
        $admin = DA_Connection::connectAdmin(DIRECTADMIN_URL, MASTER_ADMIN_USERNAME, MASTER_ADMIN_PASSWORD . '_invalid');
        $admin->getContextUser()->getType();
    }

    /**
     * @expectedException \Omines\DirectAdmin\DirectAdminException
     */
    public function testInvalidCall()
    {
        $admin = DA_Connection::connectAdmin(DIRECTADMIN_URL, MASTER_ADMIN_USERNAME, MASTER_ADMIN_PASSWORD);
        $admin->invokeApiGet('INVALID_COMMAND');
    }

    /**
     * @expectedException \Omines\DirectAdmin\DirectAdminException
     */
    public function testInvalidUrl()
    {
        $admin = DA_Connection::connectAdmin('http://www.google.com/', 'username', 'password');
        $admin->getContextUser()->getType();
    }
}
