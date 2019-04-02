<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Core\Utils\File;

use Espo\Core\Utils;

/**
 * Class Unifier
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class Unifier extends \Espo\Core\Utils\File\Unifier
{
    /**
     * @inheritdoc
     */
    public function unify($name, $paths, $recursively = false)
    {
        // prepare treoPath
        if (empty($paths['treoPath'])) {
            $paths['treoPath'] = str_replace("application/Espo/", "application/Treo/", $paths['corePath']);
        }

        // core
        $content = $this->unifySingle($paths['corePath'], $name, $recursively);

        // treo
        $content = $this->merge($content, $this->unifySingle($paths['treoPath'], $name, $recursively));

        if (!empty($paths['modulePath'])) {
            $customDir = strstr($paths['modulePath'], '{*}', true);

            if (!empty($this->getMetadata())) {
                $moduleList = $this->getMetadata()->getModuleList();
            } else {
                $moduleList = $this->getFileManager()->getFileList($customDir, false, '', false);
            }

            foreach ($moduleList as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);

                // module
                $content = $this->merge($content, $this->unifySingle($curPath, $name, $recursively, $moduleName));
            }
        }

        if (!empty($paths['customPath'])) {
            // custom
            $content = $this->merge($content, $this->unifySingle($paths['customPath'], $name, $recursively));
        }

        return $content;
    }

    /**
     * @param array|object $data1
     * @param array|object $data2
     *
     * @return array|object
     */
    protected function merge($data1, $data2)
    {
        if ($this->useObjects) {
            $result = Utils\DataUtil::merge($data1, $data2);
        } else {
            $result = Utils\Util::merge($data1, $data2);
        }

        return $result;
    }
}
