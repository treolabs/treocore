<?php
declare(strict_types=1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use Espo\Modules\TreoCrm\Core\Utils\Config;
use Espo\Core\Exceptions;

/**
 * Class Installer
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Installer extends Base
{

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
    }

    /**
     *  Generate default config if not exists
     *
     * @throws Exceptions\Forbidden
     */
    public function generateConfig()
    {

        // check if is install
        if ($this->isInstall()) {
            throw new Exceptions\Forbidden();
        }

        /** @var Config $config */
        $config = $this->getConfig();

        $pathToConfig = $config->getConfigPath();

        // create config if not exists
        if (!file_exists($pathToConfig)) {
            $this->getInjection('fileManager')->putPhpContents($pathToConfig, $config->getDefaults(), true);
        }
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

        $dbSettings = $this->prepareDbParams($data);

        try {
            $this->isConnectToDb($dbSettings);


            $this->getConfig()->set('database',  array_merge($dbParams, $dbSettings));

            $result['status'] = $this->getConfig()->save();
        } catch (\Exception $e) {
            $result['message'] = $e->getMessage();
            $result['status'] = false;
        }

        return $result;
    }

    /**
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

        $dsn = 'mysql' . ':host=' . $dbSettings['host'] . ';' . $port . ';dbname=' . $dbSettings['dbname'];

        // todo handle error  check port
        $pdo = new \PDO($dsn, $dbSettings['user'], $dbSettings['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);

        return true;
    }
}
