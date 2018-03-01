<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core;

use Espo\Core\Application as EspoApplication;
use Espo\Modules\TreoCrm\Core\Utils\Auth;
use Espo\Modules\TreoCrm\Services\Installer;

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
        $result = ['status' => false, 'message' => ''];

        // check permissions and generate config
        try {
            /** @var Installer $installer */
            $installer = $this->getContainer()->get('serviceFactory')->create('Installer');
            $result['status'] = $installer->checkPermissions();
            $result['status'] = $installer->generateConfig() && $result['status'];
        } catch (\Exception $e) {
            $result['status'] = 'false';
            $result['message'] = $e->getMessage();
        }

        $modules = $this->getContainer()->get('config')->get('modules');
        $version = !empty($modules['TreoCrm']['version']) ? 'v.' . $modules['TreoCrm']['version'] : "";

        $this->getContainer()->get('clientManager')->display(
            null,
            'html/treo-installation.html',
            [
                'classReplaceMap' => json_encode($this->getMetadata()->get(['app', 'clientClassReplaceMap'], [])),
                'year'    => date('Y'),
                'version' => $version,
                'status'  => $result['status'],
                'message' => $result['message']
            ]
        );
    }

    /**
     * Create auth
     *
     * @return \Espo\Core\Utils\Auth|Auth
     */
    protected function createAuth()
    {
        return new Auth($this->container);
    }
}
