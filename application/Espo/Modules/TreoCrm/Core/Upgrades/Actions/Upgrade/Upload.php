<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

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
