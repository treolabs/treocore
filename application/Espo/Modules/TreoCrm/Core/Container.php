<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core;

use Espo\Core\Container as EspoContainer;
use Espo\Modules\TreoCrm\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;

/**
 * Container class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Container extends EspoContainer
{

    /**
     * Load metadata
     *
     * @return Utils\Metadata
     */
    protected function loadMetadata(): Utils\Metadata
    {
        // create metadata
        $metadata = new Utils\Metadata($this->get('fileManager'), $this->get('config')->get('useCache'));

        // set container
        $metadata->setContainer($this);

        return $metadata;
    }

    /**
     * Load config
     *
     * @return Config
     */
    protected function loadConfig()
    {
        return new Config(new FileManager());
    }
}
