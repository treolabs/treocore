<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;
use \Espo\Modules\TreoCrm\Core\Utils\Config;

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

    public function generateConfig()
    {
        /** @var Config $config */
        $config = $this->getConfig();

        $pathToConfig = $config->getConfigPath();

        // create config if not exists
        if (!file_exists($pathToConfig)) {
            $this->getInjection('fileManager')->putPhpContents($pathToConfig, $config->getDefaults(), true);
        }
    }


}
