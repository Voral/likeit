<?php

namespace Vasoft\LikeIt\Entity;

use Bitrix\Main\Context;
use Bitrix\Main\Web\Cookie;
use RuntimeException;

final class User
{
    private const COOKIE_NAME = 'VSLK_HISTORY';

    private static ?User $instance = null;
    private int $id = 0;
    private string $hash = '';
    private string $ip = '';
    private string $userAgent = '';


    public static function getInstance(): User
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        global $USER;
        if ($USER->IsAuthorized()) {
            $this->id = (int)$USER->GetID();
        }
        $this->hash = $this->loadHash();
    }

    private function __clone()
    {
        /**
         * Impossible for singleton
         */
    }

    /**
     * @throws RuntimeException
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    private function loadHash(): string
    {
        $context = Context::getCurrent();
        $request = $context->getRequest();
        $hashValue = trim($request->getCookie(self::COOKIE_NAME));
        if ($hashValue === '') {
            $hashValue = $this->generateHash();
        }
        $cookie = new Cookie(self::COOKIE_NAME, $hashValue, time() + 60480000);
        $cookie->setDomain($context->getServer()->getHttpHost());
        $cookie->setHttpOnly(false);
        $context->getResponse()->addCookie($cookie);
        return $hashValue;
    }

    private function generateHash(): string
    {
        $server = Context::getCurrent()->getServer();
        return md5($server->get('HTTP_USER_AGENT') . ' ' . $this->getIP());
    }

    public function getUserAgent(): string
    {
        if ($this->userAgent === '') {
            $server = Context::getCurrent()->getServer();
            $this->userAgent = trim($server->get('HTTP_USER_AGENT'));
        }
        return $this->userAgent;
    }

    public function getIP(): string
    {
        if ($this->ip === '') {
            $server = Context::getCurrent()->getServer();
            $this->ip = trim($server->get('HTTP_CF_CONNECTING_IP'));
            if ($this->ip === '') {
                $this->ip = trim($server->get('HTTP_X_REAL_IP'));
            }
            $this->ip = $this->ip === '' ? trim($server->get('REMOTE_ADDR')) : $this->ip;
        }
        return $this->ip;
    }
}