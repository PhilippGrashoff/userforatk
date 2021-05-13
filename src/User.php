<?php declare(strict_types=1);

namespace userforatk;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use tokenforatk\Token;
use traitsforatkdata\UniqueFieldTrait;
use traitsforatkdata\UserException;


class User extends Model
{

    use UniqueFieldTrait;

    public $table = 'user';
    public $caption = 'Benutzer';

    protected int $maxFailedLogins = 10;


    protected function init(): void
    {
        parent::init();
        $this->addfield(

            'name',
            [

                'type' => 'string',
                'caption' => 'Name',
                'system' => true,
            ]
        );
        $this->addfield(
            'firstname',
            [
                'type' => 'string',
                'caption' => 'Vorname'
            ]
        );
        $this->addfield(
            'lastname',
            [
                'type' => 'string',
                'caption' => 'Nachname'
            ]
        );
        $this->addfield(
            'username',
            [
                'type' => 'string',
                'caption' => 'Benutzername',
                'ui' => ['form' => ['inputAttr' => ['autocomplete' => 'new-password']]]
            ]
        );
        $this->addfield(
            'password',
            [
                Password::class,
                'caption' => 'Passwort',
                'system' => true,
                'ui' => ['form' => ['inputAttr' => ['autocomplete' => 'new-password']]]
            ]
        );
        $this->addfield(
            'role',
            [
                'type' => 'string',
                'caption' => 'Benutzerrolle'
            ]
        );
        $this->addField(
            'failed_logins',
            [
                'type' => 'integer',
                'caption' => 'Gescheiterte Login-Versuche seit letztem erfolgreichen Login',
                'default' => 0,
                'system' => true,
            ]
        );

        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model, $isUpdate) {
                if (
                    $model->get('username')
                    && !$model->isFieldUnique('username')
                ) {
                    throw new UserException('Der Benutzername ist bereits vergeben! Bitte wähle einen anderen');
                }
            }
        );
    }

    public function setNewPassword(
        string $new_password_1,
        string $new_password_2,
        bool $compare_old_password = false,
        string $old_password = ''
    ): void {
        //other user than logged in user tries saving?
        if (
            $this->app->auth->user->loaded()
            && $this->app->auth->user->get('id') !== $this->get('id')
        ) {
            throw new Exception('Password can only be changed by account owner');
        }

        //old password entered needs to fit saved one
        if (
            $compare_old_password
            && !$this->compare('password', $old_password)
        ) {
            throw new UserException('Das Alte Passwort ist nicht korrekt');
        }

        //new passwords need to match
        if ($new_password_1 !== $new_password_2) {
            throw new UserException('Die Passwörter stimmen nicht überein');
        }

        $this->set('password', $new_password_1);
    }

    public function resetPassword(
        string $tokenString,
        string $new_password_1,
        string $new_password_2
    ): void {
        //new passwords need to match
        if ($new_password_1 !== $new_password_2) {
            throw new UserException('Die neuen Passwörter stimmen nicht überein');
        }

        $token = Token::loadTokenForModel($tokenString, $this);
        $this->set('password', $new_password_1);
        $this->save();
        $token->markAsUsed();
    }

    public function setNewToken(): string
    {
        $token = new Token($this->persistence, ['parentObject' => $this, 'expiresAfterInMinutes' => 180]);
        $token->save();
        return $token->get('value');
    }

    public function addFailedLogin(bool $save = true): void
    {
        $this->set('failed_logins', $this->get('failed_logins') + 1);
        if ($save) {
            $this->save();
        }
    }

    public function setFailedLoginsToZero(bool $save = true): void
    {
        $this->set('failed_logins', 0);
        if ($save) {
            $this->save();
        }
    }

    public function hasTooManyFailedLogins(): bool
    {
        return $this->get('failed_logins') > $this->maxFailedLogins;
    }

    public function getRemainingLogins(): int
    {
        return $this->maxFailedLogins - $this->get('failed_logins');
    }
}