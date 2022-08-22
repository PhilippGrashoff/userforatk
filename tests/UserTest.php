<?php declare(strict_types=1);

namespace userforatk\tests;

use Atk4\Data\Exception;
use Atk4\Ui\App;
use tokenforatk\Token;
use traitsforatkdata\TestCase;
use traitsforatkdata\UserException;
use userforatk\User;

class UserTest extends TestCase
{

    private $app;
    private $persistence;


    protected $sqlitePersistenceModels = [
        User::class,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->persistence = $this->getSqliteTestPersistence();
        $this->app = new App(['always_run' => false]);
        $this->app->db = $this->persistence;
        $this->persistence->app = $this->app;
    }

    public function testUserNameUnique()
    {
        $user = new User($this->persistence);
        $user->set('name', 'Duggu');
        $user->set('username', 'ABC');
        $user->save();

        $user2 = new User($this->persistence);
        $user2->set('name', 'sfsdf');
        $user2->set('username', 'ABC');
        self::expectException(UserException::class);
        $user2->save();
    }

    public function testUserWithEmptyUsernameCanBeSaved()
    {
        $persistence = $this->getSqliteTestPersistence();
        $user = new User($persistence);
        $user->save();
        self::assertEmpty($user->get('username'));
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
        $this->persistence->app = new \stdClass();
        $this->persistence->app->auth = new \stdClass();
        $this->persistence->app->auth->user = $loggedInUser;

        self::expectException(\Atk4\Data\Exception::class);
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

    public function testResetPassword()
    {
        $persistence = $this->getSqliteTestPersistence([Token::class]);
        $user = new User($persistence);
        $user->set('name', 'Duggu');
        $user->set('username', 'Duggudd');
        $user->save();
        $token = Token::createTokenForEntity($user);

        //unexisting Token should throw exception
        $exception_found = false;
        try {
            $user->resetPassword('nonexistingtoken', 'nuggu', 'nuggu');
        } catch (UserException $e) {
            $exception_found = true;
        }
        self::assertTrue($exception_found);

        //non-matching passwords should cause exception
        $exception_found = false;
        try {
            $user->resetPassword($token->getTokenString(), 'nuggu', 'duggu');
        } catch (UserException $e) {
            $exception_found = true;
        }
        self::assertTrue($exception_found);

        //that should work
        $user->resetPassword($token->getTokenString(), 'nuggu', 'nuggu');

        //token should be deleted
        $t = new Token($persistence);
        $t->tryLoadBy('value', $token->getTokenString());
        self::assertFalse($t->loaded());
    }

    public function testResetPasswordTokenNotConnectedToModel()
    {
        $persistence = $this->getSqliteTestPersistence([Token::class]);
        $user = new User($persistence);
        $user->set('name', 'Duggu');
        $user->set('username', 'Duggudd');
        $user->save();
        $token = Token::createTokenForEntity($user);
        $token->set('model_id', 99999);
        $token->save();

        self::expectException(UserException::class);
        $user->resetPassword($token->getTokenString(), 'DEDE', 'DEDE');
    }
}
