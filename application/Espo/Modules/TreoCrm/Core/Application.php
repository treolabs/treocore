<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core;

use Espo\Core\Application as EspoApplication;
use Espo\Modules\TreoCrm\Core\Utils\Auth;

/**
 * Application class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Application extends EspoApplication
{

    /**
     * Init container
     */
    protected function initContainer()
    {
        $this->container = new Container();
    }

    /**
     * Run client
     */
    public function runClient()
    {
        $modules = $this->getContainer()->get('config')->get('modules');
        $version = !empty($modules['TreoCrm']['version']) ? 'v.' . $modules['TreoCrm']['version'] : "";

        $this->getContainer()->get('clientManager')->display(
            null,
            'html/treo-main.html',
            [
                'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
                'year' => date('Y'),
                'version' => $version
            ]
        );
    }

    /**
     * Run client
     */
    public function runInstaller()
    {
        $this->getContainer()->get('serviceFactory')->create('Installer')->generateConfig();

        $modules = $this->getContainer()->get('config')->get('modules');
        $version = !empty($modules['TreoCrm']['version']) ? 'v.' . $modules['TreoCrm']['version'] : "";

        $this->getContainer()->get('clientManager')->display(
            null,
            'html/treo-installation.html',
            [
                'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
                'year' => date('Y'),
                'version' => $version
            ]
        );
    }

    protected function createAuth()
    {
        return new Auth($this->container);
    }
}
