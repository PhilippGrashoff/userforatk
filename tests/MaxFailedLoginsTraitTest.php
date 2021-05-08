<?php declare(strict_types=1);

namespace PMRAtk\tests\phpunit\Data\Traits;

use auditforatk\Audit;
use PMRAtk\Data\User;
use PMRAtk\tests\phpunit\TestCase;

class MaxFailedLoginsTraitTest extends TestCase
{

    protected $sqlitePersistenceModels = [
        User::class,
        Audit::class
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
        $user->maxFailedLogins = 1;
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
        $user->maxFailedLogins = 1;
        self::assertFalse($user->hasTooManyFailedLogins());
        $user->addFailedLogin();
        self::assertTrue($user->hasTooManyFailedLogins());
    }
}