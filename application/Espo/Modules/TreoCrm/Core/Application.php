<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core;

use Espo\Core\Application as EspoApplication;

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
}
