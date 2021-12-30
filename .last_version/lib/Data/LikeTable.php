<?php

namespace Vasoft\LikeIt\Data;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Entity;
use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Fields;


/**
 * Class LikeTable Таблица для хранения лайков проставленных пользователями
 *
 * @package Vasoft\LikeIt
 * @author Alexander Vorobyev https://va-soft.ru/
 * @version 1.2.0
 */
class LikeTable extends Entity\DataManager
{

    /**
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public static function getTableName()
    {
        return 'vasoft_likeit_like';
    }

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getMap()
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configureAutocomplete()
                ->configurePrimary(),
            (new Fields\IntegerField('ELEMENTID'))->configureRequired(),
            (new Fields\StringField('IP'))->configureRequired(),
            (new Fields\StringField('USERAGENT'))->configureRequired(),
            (new Fields\StringField('HASH'))->configureRequired(),
            new Fields\IntegerField('USERID'),
        ];
    }

    /**
     * Создает индексы при установке модуля
     * @return void
     * @throws SqlQueryException
     */
    public static function createIndexes(): void
    {
        $connection = Application::getInstance()->getConnection(self::getConnectionName());
        if ('mysql' === $connection->getType()) {
            $sql = "CREATE UNIQUE INDEX %s ON " . self::getTableName() . " (%s)";
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_HASH_EID', 'HASH, ELEMENTID'));
            $sql = "CREATE INDEX %s ON " . self::getTableName() . " (%s)";
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_EID', 'ELEMENTID'));
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_HASH', 'HASH'));
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_USERID', 'USERID'));
        }
    }

    /**
     * Удаляет индексы при удалении модуля
     * @return void
     * @throws SqlQueryException
     */
    public static function dropIndexes(): void
    {
        $connection = Application::getInstance()->getConnection(self::getConnectionName());
        if ('mysql' === $connection->getType()) {
            $sql = 'DROP INDEX %s ON ' . self::getTableName();
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_HASH'));
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_USERID'));
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_EID'));
            $connection->queryExecute(sprintf($sql, 'VASOFT_LIKIT_HASH_EID'));
        }
    }

    /**
     * Обработчик события уделения элемента инфоблока
     * @param $id
     * @return void
     * @throws SqlQueryException
     */
    public static function onBeforeElementDeleteHandler($id): void
    {
        $id = (int)$id;
        if ($id > 0) {
            $connection = Application::getInstance()->getConnection(self::getConnectionName());
            $sql = "DELETE FROM " . self::getTableName() . " WHERE ELEMENTID = %d";
            $connection->queryExecute(sprintf($sql, $id));
        }
    }
}
