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
     * @return bool
     * @throws Exceptions\Forbidden
     */
    public function setDbSettings(array $data): bool
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $dbParams = $config->get('database');

        $issetDbParams = !empty($dbParams['dbname']) || !empty($dbParams['user']);

        // check if is install
        if ($this->isInstall() || $issetDbParams) {
            throw new Exceptions\Forbidden();
        }

        $dbSettings = $this->prepareDbParams($data);

        $this->isConncetToDb($dbSettings);

        array_merge($dbParams, $dbSettings);

        $this->getConfig()->set('database', $dbParams);


        return $this->getConfig()->save();
    }

    /**
     * @param $dbSettings
     *
     * @return array
     */
    public function checkDbConnect(array $dbSettings): array
    {
        $result = [
            'status' => false,
            'message' => ''
        ];

        try {
            $result['status'] = $this->isConncetToDb($this->prepareDbParams($dbSettings));
        } catch (\PDOException $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
        }

         return $result;
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
    protected function isConncetToDb($dbSettings)
    {
        $port = !empty($dbSettings['port']) ? ';port=' . $dbSettings['port'] . ';' : '';

        $dsn = 'mysql' . ':host=' . $dbSettings['host'] . ';' . 'dbname=' . $dbSettings['dbname'] . $port;

        // todo handle error  check port
        new \PDO($dsn, $dbSettings['user'], $dbSettings['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING]);

        return true;
    }


    /**
     * Check if is install
     *
     * @return bool
     */
    protected function isInstall(): bool
    {
        $config = $this->getConfig();

        return file_exists($config->getConfigPath()) && $config->get('isInstalled');
    }
}
