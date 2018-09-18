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

declare(strict_types=1);

namespace Treo\Core\Utils\File;

/**
 * Class ClassParser
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class ClassParser extends \Espo\Core\Utils\File\ClassParser
{
    /**
     * @inheritdoc
     */
    public function getData($paths, $cacheFile = false)
    {
        // prepare data
        $data = null;

        if (is_string($paths)) {
            $paths = ['corePath' => $paths];
        }

        if ($cacheFile && file_exists($cacheFile) && $this->getConfig()->get('useCache')) {
            $data = $this->getFileManager()->getPhpContents($cacheFile);
        } else {
            $data = $this->getClassNameHash($paths['corePath']);

            if (!empty($paths['treoCorePath'])) {
                $data = array_merge($data, $this->getClassNameHash($paths['treoCorePath']));
            }

            if (isset($paths['modulePath'])) {
                foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                    $path = str_replace('{*}', $moduleName, $paths['modulePath']);

                    $data = array_merge($data, $this->getClassNameHash($path));
                }
            }

            if (isset($paths['customPath'])) {
                $data = array_merge($data, $this->getClassNameHash($paths['customPath']));
            }

            if ($cacheFile && $this->getConfig()->get('useCache')) {
                $result = $this->getFileManager()->putPhpContents($cacheFile, $data);
                if ($result == false) {
                    throw new \Espo\Core\Exceptions\Error();
                }
            }
        }

        return $data;
    }
}
