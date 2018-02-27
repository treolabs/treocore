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

        // prepare params
        $dbParams['host']     = (string)$data['host'];
        $dbParams['port']     = isset($data['port']) ? (string)$data['port'] : '';
        $dbParams['dbname']   = (string)$data['dbname'];
        $dbParams['user']     = (string)$data['user'];
        $dbParams['password'] = isset($data['password']) ? (string)$data['password'] : '';

        $this->getConfig()->set('database', $dbParams);

        return $this->getConfig()->save();
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
