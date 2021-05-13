<?php declare(strict_types=1);

namespace userforatk\tests;

use traitsforatkdata\TestCase;
use userforatk\User;

class MaxFailedLoginsTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        User::class,
    ];

    public function testFailedLoginIncrease()
    {
        $persistence = $this->getSqliteTestPersistence();
        $user = new User($persistence);
        $user->set('username', 'DUggu');
        $user->set('name', 'Jsdfsdf');
        $user->save();
        self::assertEquals(0, $user->get('failed_logins'));
        $user->addFailedLogin();
        self::assertEquals(1, $user->get('failed_logins'));
    }

    public function testGetRemainingLogins()
    {
        $persistence = $this->getSqliteTestPersistence();
        $user = new User($persistence);
        $user->set('username', 'DUggu');
        $user->set('name', 'Jsdfsdf');
        $user->save();
        self::assertEquals(10, $user->getRemainingLogins());
        $user->addFailedLogin();
        self::assertEquals(9, $user->getRemainingLogins());
        $value = 1;
        $this->setProtected($user, 'maxFailedLogins', $value);
        self::assertEquals(0, $user->getRemainingLogins());
    }

    public function testSetFailedLoginsToZero()
    {
        $persistence = $this->getSqliteTestPersistence();
        $user = new User($persistence);
        $user->set('username', 'DUggu');
        $user->set('name', 'Jsdfsdf');
        $user->save();
        $user->addFailedLogin();
        self::assertEquals(1, $user->get('failed_logins'));
        $user->setFailedLoginsToZero();
        self::assertEquals(0, $user->get('failed_logins'));
    }

    public function testHasTooManyFailedLogins()
    {
        $persistence = $this->getSqliteTestPersistence();
        $user = new User($persistence);
        $user->set('username', 'DUggu');
        $user->set('name', 'Jsdfsdf');
        $user->save();
        $user->addFailedLogin();
        $value = 1;
        $this->setProtected($user, 'maxFailedLogins', $value);
        self::assertFalse($user->hasTooManyFailedLogins());
        $user->addFailedLogin();
        self::assertTrue($user->hasTooManyFailedLogins());
    }
}