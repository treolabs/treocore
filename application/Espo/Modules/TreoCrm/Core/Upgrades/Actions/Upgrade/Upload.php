<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Upgrades\Actions\Upgrade;

use Espo\Core\Upgrades\Actions\Upgrade\Upload as EspoUpload;
use Espo\Core\Utils\System;

/**
 * Class of Upload
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Upload extends EspoUpload
{

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM
     *
     * @return boolean
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function isAcceptable()
    {
        // prepare params
        $manifest = $this->getManifest();
        $res      = $this->checkPackageType();

        // check author
        if (empty($manifest['author']) || $manifest['author'] != 'TreoPIM') {
            $this->throwErrorAndRemovePackage('Your should use TreoPIM package.');
        }

        //check php version
        if (isset($manifest['php'])) {
            $error = 'Your PHP version does not support this installation package.';

            $res &= $this->checkVersions($manifest['php'], System::getPhpVersion(), $error);
        }

        //check acceptableVersions
        if (isset($manifest['acceptableVersions'])) {
            $error = 'Your EspoCRM version doesn\'t match for this installation package.';

            $res &= $this->checkVersions($manifest['acceptableVersions'], $this->getConfig()->get('version'), $error);
        }

        //check dependencies
        if (!empty($manifest['dependencies'])) {
            $res &= $this->checkDependencies($manifest['dependencies']);
        }

        return (bool) $res;
    }
}
