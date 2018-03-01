<?php
declare(strict_types=1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\TreoCrm\Core\Utils\Config;
use Espo\Core\Exceptions;
use Espo\Core\Utils\PasswordHash;

/**
 * Class Installer
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Installer extends Base
{

    /**
     * @var PasswordHash
     */
    protected $passwordHash = null;

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        /**
         * Add dependencies
         */
        $this->addDependency('fileManager');
        $this->addDependency('dataManager');
        $this->addDependency('crypt');
        $this->addDependency('language');
    }

    /**
     *  Generate default config if not exists
     *
     * @throws Exceptions\Forbidden
     *
     * @return bool
     */
    public function generateConfig(): bool
    {
        $result = false;

        // check if is install
        if ($this->isInstalled()) {
            throw new Exceptions\Forbidden($this->translateError('alreadyInstalled'));
        }

        /** @var Config $config */
        $config = $this->getConfig();

        $pathToConfig = $config->getConfigPath();

        // get default config
        $defaultConfig = $config->getDefaults();

        // get permissions
        $owner = $this->getFileManager()->getPermissionUtils()->getDefaultOwner(true);
        $group = $this->getFileManager()->getPermissionUtils()->getDefaultGroup(true);

        if (!empty($owner)) {
            $defaultConfig['defaultPermissions']['user'] = $owner;
        }
        if (!empty($group)) {
            $defaultConfig['defaultPermissions']['group'] = $group;
        }

        $defaultConfig['passwordSalt'] = $this->getPasswordHash()->generateSalt();
        $defaultConfig['cryptKey'] = $this->getInjection('crypt')->generateKey();

        // create config if not exists
        if (!file_exists($pathToConfig)) {
            $result = $this->getFileManager()->putPhpContents($pathToConfig, $defaultConfig, true);
        }

        return $result;
    }

    /**
     * Get translations for installer
     *
     * @return array
     */
    public function getTranslations(): array
    {
        $language = $this->getInjection('language');

        $result = $language->get('Installer');

        // add languages
        $languages = $language->get('Global.options.language');

        $result['labels']['languages'] = $languages;

        return $result;
    }

    /**
     * Get license and languages
     *
     * @return array
     */
    public function getLicenseAndLanguages(): array
    {
        // get languages data
        $result = [
            'languageList' => $this->getConfig()->get('languageList'),
            'language'     => $this->getConfig()->get('language'),
            'license'      => ''
        ];

        // get license
        $license = $this->getFileManager()->getContents('LICENSE.txt');
        $result['license'] = $license ? $license : '';

        return $result;
    }

    /**
     * Get default dataBase settings
     *
     * @return array
     */
    public function getDefaultDbSettings(): array
    {
        return $this->getConfig()->getDefaults()['database'];
    }

    /**
     * Set language
     *
     * @param $lang
     *
     * @return array
     */
    public function setLanguage(string $lang): array
    {
        $result = ['status' => false, 'message' => ''];

        if (!in_array($lang, $this->getConfig()->get('languageList'))) {
            $result['message'] = $this->translateError('languageNotCorrect');
            $result['status'] = false;
        } else {
            $this->getConfig()->set('language', $lang);
            $result['status'] = $this->getConfig()->save();
        }

        return $result;
    }

    /**
     * Set DataBase settings
     *
     * @param array $data
     *
     * @return array
     */
    public function setDbSettings(array $data): array
    {
        $result = ['status' => false, 'message' => ''];

        /** @var Config $config */
        $config = $this->getConfig();

        $dbParams = $config->get('database');

        // prepare input params
        $dbSettings = $this->prepareDbParams($data);

        // check if params already exists
        if (!empty($dbParams['dbname']) && !empty($dbParams['user'])) {
            $result['message'] = $this->translateError('dbSettingsAlreadyExists');
        } else {
            try {
                // check connect to db
                $this->isConnectToDb($dbSettings);

                // update config
                $config->set('database', array_merge($dbParams, $dbSettings));

                $result['status'] = $config->save();
            } catch (\Exception $e) {
                $result['message'] = $e->getMessage();
                $result['status'] = false;
            }
        }

        return $result;
    }

    /**
     * Create admin
     *
     * array $params
     *
     * @param $params
     *
     * @return array
     */
    public function createAdmin(array $params): array
    {
        $result = ['status' => false, 'message' => ''];

        // check password
        if ($params['password'] !== $params['confirmPassword']) {
            $result['message'] = $this->translateError('differentPass');
        } else {
            try {
                // rebuild database
                $result['status'] = $this->getInjection('dataManager')->rebuild();

                // create user
                $user = $this->getEntityManager()->getEntity('User');
                $user->set([
                    'id'       => '1',
                    'userName' => $params['userName'],
                    'password' => $this->getPasswordHash()->hash($params['password']),
                    'lastName' => 'Admin',
                    'isAdmin'  => '1'
                ]);
                $result['status'] = $this->getEntityManager()->saveEntity($user) && $result['status'];

                // set installed
                $this->getConfig()->set('isInstalled', true);
                $this->getConfig()->save();
            } catch (\Exception $e) {
                $result['status'] = false;
                $result['message'] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Check connect to DB
     *
     * @param $dbSettings
     *
     * @return array
     */
    public function checkDbConnect(array $dbSettings): array
    {
        $result = ['status' => false, 'message' => ''];

        try {
            $result['status'] = $this->isConnectToDb($this->prepareDbParams($dbSettings));
        } catch (\PDOException $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Check if is install
     *
     * @return bool
     */
    public function isInstalled(): bool
    {
        $config = $this->getConfig();

        return file_exists($config->getConfigPath()) && $config->get('isInstalled');
    }

    /**
     * Check permissions
     *
     * @return bool
     * @throws Exceptions\InternalServerError
     */
    public function checkPermissions(): bool
    {
        $this->getFileManager()->getPermissionUtils()->setMapPermission();

        $error = $this->getFileManager()->getPermissionUtils()->getLastError();

        if (!empty($error)) {
            $message = is_array($error) ? implode($error, ' ;') : string($error);

            throw new Exceptions\InternalServerError($message);
        }

        return true;
    }

    /**
     * Prepare DB params
     *
     * @param array $data
     *
     * @return array
     */
    protected function prepareDbParams(array $data): array
    {
        // prepare params
        return [
            'host'     => (string)$data['host'],
            'port'     => isset($data['port']) ? (string)$data['port'] : '',
            'dbname'   => (string)$data['dbname'],
            'user'     => (string)$data['user'],
            'password' => isset($data['password']) ? (string)$data['password'] : ''
        ];
    }


    /**
     * Check connect to db
     *
     * @param array $dbSettings
     *
     * @return bool
     */
    protected function isConnectToDb(array $dbSettings)
    {
        $port = !empty($dbSettings['port']) ? '; port=' . $dbSettings['port'] : '';

        $dsn = 'mysql' . ':host=' . $dbSettings['host'] . $port . ';dbname=' . $dbSettings['dbname'] . ';';

        $this->createDataBaseIfNotExists($dbSettings, $port);

        new \PDO($dsn, $dbSettings['user'], $dbSettings['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);

        return true;
    }

    /**
     * Create database if not exists
     *
     * @param array  $dbSettings
     * @param string $port
     */
    protected function createDataBaseIfNotExists(array $dbSettings, string $port)
    {
        $dsn = 'mysql' . ':host=' . $dbSettings['host'] . $port;

        $pdo = new \PDO(
            $dsn, $dbSettings['user'], $dbSettings['password'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbSettings['dbname'] . "`");
    }

    /**
     * Get file manager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->getInjection('fileManager');
    }

    /**
     * Get passwordHash
     *
     * @return PasswordHash
     */
    protected function getPasswordHash(): PasswordHash
    {
        if (!isset($this->passwordHash)) {
            $config = $this->getConfig();
            $this->passwordHash = new PasswordHash($config);
        }

        return $this->passwordHash;
    }

    /**
     * Translate error
     *
     * @param string $error
     *
     * @return mixed
     */
    protected function translateError(string $error): string
    {
        return $this->getInjection('language')->translate($error, 'errors', 'Installer');
    }
}
