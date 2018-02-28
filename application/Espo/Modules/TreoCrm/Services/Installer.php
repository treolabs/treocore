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
     * @var array
     */
    protected $recommendation = [
        'php'   => [
            'max_execution_time'  => 180,
            'max_input_time'      => 180,
            'memory_limit'        => '256M',
            'post_max_size'       => '20M',
            'upload_max_filesize' => '20M'
        ],
        'mysql' => [
            'version' => 5.1
        ]
    ];

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
    }

    /**
     *  Generate default config if not exists
     *
     * @throws Exceptions\Forbidden
    $name     *
     * @return bool
     */
    public function generateConfig(): bool
    {
        $result = false;

        // check if is install
        if ($this->isInstall()) {
            throw new Exceptions\Forbidden();
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
            $result['message'] = 'Input language is not correct';
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

        try {

            if (!empty($dbParams['dbname']) && !empty($dbParams['user'])) {
                throw new Exceptions\Forbidden('ParamsIsset');
            }

            // check connect to db
            $this->isConnectToDb($dbSettings);

            // update config
            $config->set('database', array_merge($dbParams, $dbSettings));

            $result['status'] = $config->save();
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['status'] = false;
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
            $result['message'] = 'differentPass';
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
    public function isInstall(): bool
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
     * @param $dbSettings
     *
     * @return bool
     */
    protected function isConnectToDb($dbSettings)
    {
        $port = !empty($dbSettings['port']) ? 'port=' . $dbSettings['port'] : '';

        $dsn = 'mysql' . ':host=' . $dbSettings['host'] . ';dbname=' . $dbSettings['dbname'] . ';' . $port;

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

        $pdo = new \PDO($dsn, $dbSettings['user'], $dbSettings['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);

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
     * Get recommendation
     *
     * @return array
     */
    protected function getRecommendation(): array
    {
        return $this->recommendation;
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
}
