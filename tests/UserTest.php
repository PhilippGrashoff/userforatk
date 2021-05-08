<?php declare(strict_types=1);

namespace PMRAtk\tests\phpunit\Data;

use auditforatk\Audit;
use Exception;
use PMRAtk\App\App;
use PMRAtk\Data\Token;
use PMRAtk\Data\User;
use PMRAtk\tests\phpunit\TestCase;
use traitsforatkdata\UserException;

class UserTest extends TestCase
{

    private $app;
    private $persistence;


    protected $sqlitePersistenceModels = [
        User::class,
        Audit::class,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->persistence = $this->getSqliteTestPersistence();
        $this->app = new App(['nologin'], ['always_run' => false]);
        $this->app->db = $this->persistence;
        $this->persistence->app = $this->app;
    }

    public function testUserNameUnique()
    {
        $persistence = $this->getSqliteTestPersistence();
        $c = new User($persistence);
        $c->set('name', 'Duggu');
        $c->set('username', 'ABC');
        $c->save();

        $c2 = new User($persistence);
        $c2->set('name', 'sfsdf');
        $c2->set('username', 'ABC');
        self::expectException(Exception::class);
        $c2->save();
    }

    public function testUserWithEmptyUsernameCanBeSaved() {
        $persistence = $this->getSqliteTestPersistence();
        $c = new User($persistence);
        $c->save();
        self::assertEmpty($c->get('username'));
    }

    public function testExceptionSetNewPasswordOtherUserLoggedIn()
    {
        $user = new User($this->persistence);
        $user->set('name', 'Duggu');
        $user->set('username', 'ABC');
        $user->set('password', 'ABC');
        $user->save();

        $loggedInUser = new User($this->persistence);
        $loggedInUser->set('name', 'Muggu');
        $loggedInUser->set('username', 'FRE');
        $loggedInUser->set('password', 'FRE');
        $loggedInUser->save();
        $this->app->auth->user = $loggedInUser;

        self::expectException(\atk4\data\Exception::class);
        $user->setNewPassword('ggg', 'ggg');
    }

    public function testExceptionSetNewPasswordOldPasswordWrong()
    {
        $user = new User($this->persistence);
        $user->set('name', 'Duggu');
        $user->set('username', 'ABC');
        $user->set('password', 'ABC');
        $user->save();
        self::expectException(UserException::class);
        $user->setNewPassword('ggg', 'ggg', true, 'falseoldpw');
    }

    public function testExceptionSetNewPasswordsDoNotMatch()
    {
        $user = new User($this->persistence);
        self::expectException(UserException::class);
        $user->setNewPassword('gggfgfg', 'ggg');
    }

    public function testSetNewPassword()
    {
        $user = new User($this->persistence);
        $user->setNewPassword('gggg', 'gggg');
        self::assertTrue(true);
    }

    /*
    public function testsendResetPasswordEmail()
    {
        $persistence = $this->getSqliteTestPersistence([EmailAccount::class, Token::class]);
        $this->_addStandardEmailAccount($persistence);
        $c = new User($persistence);
        $c->set('username', 'test');
        $c->save();

        //unexisting username should throw exception
        $exception_found = false;
        try {
            $c->sendResetPasswordEmail('LOBO');
        } catch (Exception $e) {
            $exception_found = true;
        }
        self::assertTrue($exception_found);

        //with correct username it should work
        $initial_token_count = (new Token($persistence))->action('count')->getOne();
        $c->sendResetPasswordEmail('test');
        self::assertEquals($initial_token_count + 1, (new Token($persistence))->action('count')->getOne());
    }*/

    public function testResetPassword()
    {
        $persistence = $this->getSqliteTestPersistence([Token::class]);
        $c = new User($persistence);
        $c->set('name', 'Duggu');
        $c->set('username', 'Duggudd');
        $c->save();
        $token = $c->setNewToken();

        //unexisting username should throw exception
        $exception_found = false;
        try {
            $c->resetPassword('nonexistingtoken', 'nuggu', 'nuggu');
        } catch (Exception $e) {
            $exception_found = true;
        }
        self::assertTrue($exception_found);

        //non matching passwords should cause exception
        $exception_found = false;
        try {
            $c->resetPassword($token, 'nuggu', 'duggu');
        } catch (Exception $e) {
            $exception_found = true;
        }
        self::assertTrue($exception_found);

        //that should work
        $c->resetPassword($token, 'nuggu', 'nuggu');

        //token should be deleted
        $t = new Token($persistence);
        $t->tryLoadBy('value', $token);
        self::assertFalse($t->loaded());
    }

    public function testResetPasswordTokenNotConnectedToModel()
    {
        $persistence = $this->getSqliteTestPersistence([Token::class]);
        $c = new User($persistence);
        $c->set('name', 'Duggu');
        $c->set('username', 'Duggudd');
        $c->save();
        $token = $c->setNewToken();

        //token should be deleted
        $t = new Token($persistence);
        $t->loadBy('value', $token);
        $t->set('model_id', 99999);
        $t->save();

        self::expectException(UserException::class);
        $c->resetPassword($token, 'DEDE', 'DEDE');
    }
}
