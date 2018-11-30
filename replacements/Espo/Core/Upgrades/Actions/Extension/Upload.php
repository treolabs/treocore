<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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
declare(strict_types=1);

namespace Espo\Core\Upgrades\Actions\Extension;

/**
 * Class Upload
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Upload extends \Espo\Core\Upgrades\Actions\Base\Upload
{
    /**
     * @param array $dependencyList
     *
     * @return bool
     */
    protected function checkDependencies($dependencyList)
    {
        return $this->getHelper()->checkDependencies($dependencyList);
    }

    /**
     * Check if version of upgrade/extension is acceptable to current version of EspoCRM
     *
     * @param  string $version
     *
     * @return boolean
     */
    protected function isAcceptable()
    {
        // get manifest
        $manifest = $this->getManifest();

        if ($manifest['author'] == 'EspoCRM') {
            // prepare espo version
            $version = json_decode(file_get_contents(CORE_PATH . "/composer.json"), true)['extra']['espoVersion'];

            // set espo version
            $this->getConfig()->set('version', $version);
        }

        return parent::isAcceptable();
    }

    /**
     * @param string $errorMessage
     */
    protected function throwErrorAndRemovePackage($errorMessage = '')
    {
        // prepare message
        $errorMessage = str_replace("EspoCRM", "System", $errorMessage);

        // call parent
        parent::throwErrorAndRemovePackage($errorMessage);
    }
}