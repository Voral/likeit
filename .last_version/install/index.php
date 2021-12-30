<?php

/**
 * @noinspection DuplicatedCode
 * @noinspection AccessModifierPresentedInspection
 */

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Vasoft\LikeIt\Data\LikeTable;


Loc::loadMessages(__FILE__);

class vasoft_likeit extends CModule
{
    var $MODULE_ID = "vasoft.likeit";
    private const MINIMAL_KERNEL = '21.600.000';
    private const MINIMAL_PHP_VERSION = '7.4.0';

    private static array $arTables = array(
        LikeTable::class
    );

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('VASOFT_LIKEIT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VASOFT_LIKEIT_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage("VASOFT_COMPANY");
        $this->PARTNER_URI = 'https://va-soft.ru/';

        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }


    /**
     * @return bool
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function DoInstall()
    {
        global $APPLICATION;
        $result = false;
        try {
            $this->checkRequirements();
            ModuleManager::registerModule($this->MODULE_ID);
            if (Loader::includeModule($this->MODULE_ID)) {
                $this->installFiles();
                $this->installDB();
                LikeTable::createIndexes();
                $this->registerDependencies();
                $result = true;
            } else {
                throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_MODULE_REGISTER_ERROR"));
            }
        } catch (Exception $exception) {
            $APPLICATION->ThrowException($exception->getMessage());
        }
        return $result;
    }

    /**
     * @return void
     * @throws LoaderException
     * @throws SystemException
     */
    private function checkRequirements(): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new SystemException(Loc::getMessage('VASOFT_LIKEIT_NEED_IBLOCK'));
        }
        if (CheckVersion(PHP_VERSION, self::MINIMAL_PHP_VERSION) === false) {
            throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_NEED_PHP", ['#VERSION#' => self::MINIMAL_PHP_VERSION]));
        }
        if (CheckVersion(ModuleManager::getVersion('main'), self::MINIMAL_KERNEL) === false) {
            throw new SystemException(Loc::getMessage("VASOFT_LIKEIT_NEED_KERNEL", ['#VERSION#' => self::MINIMAL_KERNEL]));
        }
    }


    /**
     * @return void
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws LoaderException
     * @throws SqlQueryException
     * @throws SystemException
     * @noinspection NullPointerExceptionInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function DoUninstall()
    {
        global $APPLICATION;
        $context = Application::getInstance()->getContext();
        $request = $context->getRequest();
        $step = (int)$request->get('step');
        $saveData = trim($request->get('savedata')) === 'Y';
        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("VASOFT_LIKEIT_MODULE_REMOVING"), $this->getPath() . '/install/unstep1.php');
        } elseif (2 === $step) {
            Loader::includeModule($this->MODULE_ID);
            $this->unRegisterDependencies();
            LikeTable::dropIndexes();
            if (!$saveData) {
                $this->unInstallDB();
                Option::delete($this->MODULE_ID);
            }
            $this->unInstallFiles();
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(Loc::getMessage("VASOFT_LIKEIT_MODULE_REMOVING"), $this->getPath() . '/install/unstep2.php');
        }
    }

    /**
     * @return false|void
     * @throws LoaderException
     * @throws SqlQueryException
     * @throws ArgumentException
     * @throws SystemException
     * @noinspection PhpReturnDocTypeMismatchInspection
     */
    public function installDB()
    {
        foreach (self::$arTables as $tableClass) {
            if (!Application::getConnection($tableClass::getConnectionName())->isTableExists(Base::getInstance($tableClass)->getDBTableName())) {
                Base::getInstance($tableClass)->createDbTable();
            }
        }
    }


    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function installFiles()
    {
        CopyDirFiles($this->getPath() . '/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
        CopyDirFiles($this->getPath() . '/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
    }

    /**
     * @return void
     * @throws ArgumentException
     * @throws LoaderException
     * @throws SqlQueryException
     * @throws SystemException
     * @throws ArgumentNullException
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function unInstallDB()
    {
        foreach (self::$arTables as $tableClass) {
            Bitrix\Main\Application::getConnection($tableClass::getConnectionName())->queryExecute('drop table if exists ' . Base::getInstance($tableClass)->getDBTableName());
        }
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function unInstallFiles()
    {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vasoft.likeit/');
        DeleteDirFiles($this->getPath() . "/install/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components");
    }

    /**
     * @return void
     */
    public function registerDependencies(): void
    {
        EventManager::getInstance()->registerEventHandler(
            'iblock',
            'OnBeforeIBlockElementDelete',
            $this->MODULE_ID,
            LikeTable::class,
            "onBeforeElementDeleteHandler"
        );
    }

    /**
     * @return void
     */
    public function unRegisterDependencies(): void
    {
        EventManager::getInstance()->unRegisterEventHandler(
            'iblock',
            'OnBeforeIBlockElementDelete',
            $this->MODULE_ID,
            LikeTable::class,
            "onBeforeElementDeleteHandler"
        );
    }

    public function getPath($notDocumentRoot = false): string
    {
        return ($notDocumentRoot)
            ? preg_replace('#^(.*)/(local|bitrix)/modules#', '/$2/modules', dirname(__DIR__))
            : dirname(__DIR__);
    }
}
