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

use Espo\Core\Exceptions\Error;

/**
 * Class ClassParser
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ClassParser extends \Espo\Core\Utils\File\ClassParser
{
    /**
     * @inheritdoc
     */
    public function getData($paths, $cacheFile = false)
    {
        $data = null;

        if (is_string($paths)) {
            $paths = [
                'corePath' => $paths,
            ];
        }

        // prepare treoPath
        if (empty($paths['treoPath'])) {
            $paths['treoPath'] = str_replace("application/Espo/", "application/Treo/", $paths['corePath']);
        }

        if ($cacheFile && $this->fileExists($cacheFile) && $this->getConfig()->get('useCache')) {
            $data = $this->getFileManager()->getPhpContents($cacheFile);
        } else {
            // core
            $data = $this->getClassNameHash($paths['corePath']);

            // treo
            $data = array_merge($data, $this->getClassNameHash($paths['treoPath']));

            if (isset($paths['modulePath'])) {
                foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                    $path = str_replace('{*}', $moduleName, $paths['modulePath']);

                    // module
                    $data = array_merge($data, $this->getClassNameHash($path));
                }
            }

            if (isset($paths['customPath'])) {
                // custom
                $data = array_merge($data, $this->getClassNameHash($paths['customPath']));
            }

            if ($cacheFile && $this->getConfig()->get('useCache')) {
                $result = $this->getFileManager()->putPhpContents($cacheFile, $data);
                if ($result == false) {
                    throw new Error();
                }
            }
        }

        return $data;
    }

    /**
     * Checks whether a file or directory exists
     *
     * @param string|bool $filename
     *
     * @return bool
     */
    protected function fileExists($filename): bool
    {
        return file_exists($filename);
    }
}
