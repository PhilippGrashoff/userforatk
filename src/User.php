<?php declare(strict_types=1);

namespace PMRAtk\Data;

use atk4\data\Exception;
use atk4\login\Field\Password;
use traitsforatkdata\UniqueFieldTrait;
use traitsforatkdata\UserException;
use atk4\data\Model;


class User extends Model
{

    use UniqueFieldTrait;

    public $table = 'user';
    public $caption = 'Benutzer';


    protected function init(): void
    {
        parent::init();
        $this->addfields(
            [
                [
                    'name',
                    'type' => 'string',
                    'caption' => 'Name',
                    'system' => true,
                ],
                [
                    'firstname',
                    'type' => 'string',
                    'caption' => 'Vorname'
                ],
                [
                    'lastname',
                    'type' => 'string',
                    'caption' => 'Nachname'
                ],
                [
                    'username',
                    'type' => 'string',
                    'caption' => 'Benutzername',
                    'ui' => ['form' => ['inputAttr' => ['autocomplete' => 'new-password']]]
                ],
                [
                    'password',
                    Password::class,
                    'caption' => 'Passwort',
                    'system' => true,
                    'ui' => ['form' => ['inputAttr' => ['autocomplete' => 'new-password']]]
                ],
                [
                    'role',
                    'type' => 'string',
                    'caption' => 'Benutzerrolle'
                ]
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
                if(
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
        string $token,
        string $new_password_1,
        string $new_password_2
    ) {
        //new passwords need to match
        if ($new_password_1 !== $new_password_2) {
            throw new UserException('Die neuen Passwörter stimmen nicht überein');
        }
        $t = new Token($this->persistence);
        $t->loadBy('value', $token);
        $this->tryLoad($t->get('model_id'));
        if (!$this->loaded()) {
            throw new UserException('Das Token konnte nicht gefunden werden');
        }

        $this->set('password', $new_password_1);
        $this->save();
        $t->delete();
    }

    public function setNewToken(): string
    {
        $t = new Token($this->persistence, ['parentObject' => $this, 'expiresAfterInMinutes' => 180]);
        $t->save();
        return $t->get('value');
    }

    public function getSignature()
    {
        return $this->get('signature');
    }


    public function addFailedLogin(bool $save = true)
    {
        $this->set('failed_logins', $this->get('failed_logins') + 1);
        if ($save) {
            $this->save();
        }
    }

    public function setFailedLoginsToZero(bool $save = true)
    {
        $this->set('failed_logins', 0);
        if ($save) {
            $this->save();
        }
    }

    public function hasTooManyFailedLogins(): bool
    {
        if ($this->get('failed_logins') > $this->maxFailedLogins) {
            return true;
        }

        return false;
    }

    public function getRemainingLogins(): int
    {
        return (int)$this->maxFailedLogins - $this->get('failed_logins');
    }
}