<?php
/**
 * @bxnolanginspection
 */

namespace Vasoft\LikeIt\Controllers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\UserTable;

class Auth extends Controller
{
    public function configureActions()
    {
        $arDefault = ['prefilters' => [new ActionFilter\Csrf()]];
        return [
            'check' => $arDefault,
            'auth' => $arDefault,
            'register' => $arDefault,
            'init' => $arDefault,
            'code' => $arDefault,
            'login' => $arDefault,
        ];
    }

    public function codeAction(int $code, bool $remember): array
    {
        global $USER;
        $result = false;
        try {
            $session = \Vasoft\Auth\Entities\Session::getInstance();
            $modeRegister = $session->getPass() !== '' && $session->getName() !== '';

            $expire = $session->getExpires();
            if ($expire < -20) {
                $result = [
                    'email' => $session->getEmail(),
                    'error' => 'Истекло время ожидания. Повторите запрос снова'
                ];
                if ($modeRegister) {
                    $result['name'] = $session->getName();
                }
                return $result;
            }
            if ($code !== $session->getCode()) {
                return [
                    'error' => 'Не верный код'
                ];
            }
            if ($modeRegister) {
                $arFields = [
                    'EMAIL' => $session->getEmail(),
                    'LOGIN' => $session->getEmail(),
                    'PASSWORD' => $session->getPass(),
                    'CONFIRM_PASSWORD' => $session->getPass(),
                    'ACTIVE' => 'Y'
                ];
                $groups = Option::get('main', 'new_user_registration_def_group', false);
                if ($groups) {
                    $groups = explode(',', $groups);
                    $arFields['GROUP_ID'] = $groups;
                }
                $user = new \CUser;
                $userId = $user->Add($arFields);
                if (!$userId) {
                    return ['error' => $user->LAST_ERROR];
                }
            } else {
                $userId = $this->getUser($session->getEmail());
            }
            $USER->Authorize($userId,$remember ? 'Y' : 'N');
            $result = true;
            $session->clean();
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return ['exists' => $result];
    }

    public function initAction(): array
    {
        $expire = 0;
        $messages = [];
        try {
            $session = \Vasoft\Auth\Entities\Session::getInstance();
            $expire = $session->getExpires();
            $messages = [
                'headerLogin' => 'Вход',
                'headerCode' => 'Код подтверждения',
                'headerReg' => 'Регистрация',
                'noteLogin' => '<b>Для входа</b> введите Email и пароль. Если вы забыли пароль можете ввести только Email и нажать "Отправить код".<br><br><a href="/personal/?forgot_password=yes">Восстановить пароль</a><br><br><a href="/personal/?register=yes">Регистрация</a>',
                'noteCode' => 'На указанную почту отправлено письмо с кодом. Введите код подтверждения из письма',
                'noteReg' => 'Заполните все поля и нажмите "Отправить код". На указанную почту будет отправлено  письмо с кодом подтверждения.',
                'placeholderEmail' => 'Ведите Email',
                'placeholderName' => 'Ведите имя',
                'placeholderCode' => 'Ведите код из письма',
                'placeholderPass' => 'Ведите пароль',
                'placeholderRemember' => 'Запомнить',
                'errorPass' => 'Необходимо указать пароль',
                'errorName' => 'Необходимо указать имя',
                'errorEmail' => 'Необходимо указать Email',
                'errorCode' => 'Необходимо указать код подтверждения',
                'logon' => 'Вход',
                'send' => 'Отправить код',
                'expire' => 'Время истекло. Повторите запрос кода.',
                'waiting' => 'Осталось ',
            ];
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return ['expires' => $expire, 'messages' => $messages];
    }

    private function getUser($email): int
    {
        $userId = 0;
        try {
            $arExists = UserTable::query()
                ->setFilter(['EMAIL' => $email])
                ->setSelect(['ID'])
                ->fetch();
            if ($arExists) {
                $userId = (int)$arExists['ID'];
            }
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return $userId;
    }

    public function checkAction(string $email): array
    {
        $exists = $this->getUser($email) > 0;
        return [
            'exists' => $exists
        ];
    }

    public function authAction(string $email, string $pass, bool $remember): array
    {
        $exists = false;
        try {
            $email = trim($email);
            $pass = trim($pass);
            if ($email === '' || $pass === '') {
                return ['error' => 'Необходимо ввести  E-Mail и пароль'];
            }
            $arExists = UserTable::query()
                ->setFilter([
                    'ACTIVE' => 'Y',
                    [
                        'LOGIC' => 'OR',
                        'LOGIN' => $email,
                        'EMAIL' => $email
                    ]
                ])
                ->setSelect(['LOGIN'])
                ->fetch();
            if (!$arExists) {
                return ['error' => 'Не верный логин или пароль'];
            }
            $user = new \CUser();
            $res = $user->Login(
                $arExists['LOGIN'],
                $pass,
                $remember ? 'Y' : 'N'
            );
            if (is_array($res) && $res['TYPE'] === 'ERROR') {
                return ['error' => $res['MESSAGE']];
            }
            $exists = true;
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return [
            'exists' => $exists
        ];
    }

    public function registerAction(string $email, string $pass, string $name): array
    {
        $expire = 0;
        try {
            $session = \Vasoft\Auth\Entities\Session::getInstance();
            $arData = $session->createRegister($email, $pass, $name);
            $expire = $session->getExpires();
            //$item = Codes::createRegister($email, $pass, $name);
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return ['expires' => $expire];
    }

    public function loginAction(string $email): array
    {
        $expire = 0;
        try {
            $session = \Vasoft\Auth\Entities\Session::getInstance();
            $session->createAuth($email);
            $expire = $session->getExpires();
        } catch (\Exception $e) {
            /** Гасим все ошибки */
        }
        return ['expires' => $expire];
    }
}


