<?php /** @noinspection DuplicatedCode */

/** @noinspection AccessModifierPresentedInspection */

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\SystemException;
use Vasoft\Likeit\LikeTable;

Loc::loadMessages(__FILE__);

class vasoft_likeit extends CModule
{
    var $MODULE_ID = "vasoft.likeit";
    private const MINIMAL_KERNEL = '21.600.400';
    private const MINIMAL_PHP = 700040000;
    private const MINIMAL_PHP_PRINT = '7.4';

    private static array $arTables = array(
        \Vasoft\LikeIt\Data\LikeTable::class
    );
    private static array $exclusionAdminFiles = array(
        '.',
        '..',
        'menu.php'
    );

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('VASOFT_LIKEIT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VASOFT_LIKEIT_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = 'VASoft';
        $this->PARTNER_URI = 'https://va-soft.ru/';

        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';
        $this->MODULE_GROUP_RIGHTS = 'Y';
    }


    /**
     * @return void
     * @throws ArgumentException
     * @throws LoaderException
     * @throws SqlQueryException
     * @throws SystemException
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    public function DoInstall()
    {
        global $APPLICATION;
        if (!Loader::includeModule('iblock')) {
            $APPLICATION->ThrowException(Loc::getMessage('VASOFT_LIKEIT_NEED_IBLOCK'));
        } elseif (self::invalidPHP()) {
            $APPLICATION->ThrowException(Loc::getMessage("VASOFT_LIKEIT_NEED_PHP", ['#VERSION#' => self::MINIMAL_PHP_PRINT]));
        } elseif (self::invalidKernel()) {
            $APPLICATION->ThrowException(Loc::getMessage("VASOFT_LIKEIT_NEED_KERNEL", ['#VERSION#' => self::MINIMAL_KERNEL]));
        } else {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->installFiles();
            $this->installDB();
            $this->registerDependencies();
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
        $saveData = trim($request->get('step')) === 'Y';
        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(Loc::getMessage("VASOFT_LIKEIT_MODULE_REMOVING"), $this->getPath() . '/install/unstep1.php');
        } elseif (2 === $step) {
            $this->unRegisterDependencies();
            $this->unInstallFiles();
            if (!$saveData) {
                Loader::includeModule($this->MODULE_ID);
                LikeTable::dropIndexes();
                $this->unInstallDB();
            }
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->IncludeAdminFile(Loc::getMessage("VASOFT_LIKEIT_MODULE_REMOVING"), $this->getPath() . '/install/unstep2.php');
        }
    }

    public static function invalidPHP(): bool
    {
        return ((PHP_MAJOR_VERSION * 10000 + PHP_MINOR_VERSION) * 10000 + PHP_RELEASE_VERSION) < self::MINIMAL_PHP;
    }

    public static function invalidKernel(): bool
    {
        return !CheckVersion(ModuleManager::getVersion('main'), self::MINIMAL_KERNEL);
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
        Loader::includeModule($this->MODULE_ID);
        foreach (self::$arTables as $tableClass) {
            if (!Application::getConnection($tableClass::getConnectionName())->isTableExists(Base::getInstance($tableClass)->getDBTableName())) {
                Base::getInstance($tableClass)->createDbTable();
            }
        }
        LikeTable::createIndexes();
    }


    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function installFiles()
    {
        CopyDirFiles($this->getPath() . '/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
        CopyDirFiles($this->getPath() . '/install/components', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components', true, true);
        $path = $this->getPath() . '/tools/';
        $pathDR = $this->getPath(true) . '/tools/';
        if (Bitrix\Main\IO\Directory::isDirectoryExists($path) && $dir = opendir($path)) {
            while (false !== $item = readdir($dir)) {
                if (in_array($item, self::$exclusionAdminFiles, true)) {
                    continue;
                }
                $subName = str_replace('.', '_', $this->MODULE_ID);
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . $subName . '_' . $item, '<' . '?php require($_SERVER["DOCUMENT_ROOT"]."' . $pathDR . $item . '");');
            }
            closedir($dir);
        }
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
        Loader::includeModule($this->MODULE_ID);
        foreach (self::$arTables as $tableClass) {
            Bitrix\Main\Application::getConnection($tableClass::getConnectionName())->queryExecute('drop table if exists ' . Base::getInstance($tableClass)->getDBTableName());
        }
        Option::delete($this->MODULE_ID);
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function unInstallFiles()
    {
        \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/vasoft.likeit/');
        DeleteDirFiles($this->getPath() . "/install/components", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components");
        $path = $this->getPath() . '/tools/';
        if (Bitrix\Main\IO\Directory::isDirectoryExists($path) && $dir = opendir($path)) {
            while (false !== $item = readdir($dir)) {
                if (in_array($item, self::$exclusionAdminFiles, true)) {
                    continue;
                }
                $subName = str_replace('.', '_', $this->MODULE_ID);
                File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/tools/' . $subName . '_' . $item);
            }
            closedir($dir);
        }
    }

    /**
     * @return void
     * @throws LoaderException
     */
    public function registerDependencies(): void
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            EventManager::getInstance()->registerEventHandler(
                'iblock',
                'OnBeforeIBlockElementDelete',
                $this->MODULE_ID,
                \Vasoft\LikeIt\Data\LikeTable::class,
                "onBeforeElementDeleteHandler"
            );
        }
    }

    /**
     * @return void
     * @throws LoaderException
     */
    public function unRegisterDependencies(): void
    {
        if (Loader::includeModule($this->MODULE_ID)) {
            EventManager::getInstance()->unRegisterEventHandler(
                'iblock',
                'OnBeforeIBlockElementDelete',
                $this->MODULE_ID,
                \Vasoft\LikeIt\Data\LikeTable::class,
                "onBeforeElementDeleteHandler"
            );
        }
    }

    public function getPath($notDocumentRoot = false): string
    {
        return ($notDocumentRoot)
            ? preg_replace('#^(.*)/(local|bitrix)/modules#', '/$2/modules', dirname(__DIR__))
            : dirname(__DIR__);
    }
}