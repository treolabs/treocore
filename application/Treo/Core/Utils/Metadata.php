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

namespace Treo\Core\Utils;

use Espo\Core\Utils\Metadata as Base;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\DataUtil;
use Treo\Core\ModuleManager;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends Base
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @inheritdoc
     */
    public function __construct(FileManager $fileManager, ModuleManager $moduleManager, bool $useCache)
    {
        parent::__construct($fileManager, $useCache);

        $this->moduleManager = $moduleManager;
    }

    /**
     * @inheritdoc
     */
    public function getEntityPath($entityName, $delim = '\\')
    {
        $path = implode($delim, ['Treo', 'Entities', Util::normilizeClassName(ucfirst($entityName))]);
        if (!class_exists($path)) {
            $path = parent::getEntityPath($entityName, $delim);
        }

        return $path;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryPath($entityName, $delim = '\\')
    {
        $path = implode($delim, ['Treo', 'Repositories', Util::normilizeClassName(ucfirst($entityName))]);
        if (!class_exists($path)) {
            $path = parent::getRepositoryPath($entityName, $delim);
        }

        return $path;
    }

    /**
     * @param bool $reload
     */
    protected function objInit($reload = false)
    {
        if (!$this->useCache) {
            $reload = true;
        }

        if (file_exists($this->objCacheFile) && !$reload) {
            $this->objData = $this->getFileManager()->getPhpContents($this->objCacheFile);
        } else {
            $this->objData = $this->addAdditionalFieldsObj($this->composeMetadata());

            if ($this->useCache) {
                $isSaved = $this->getFileManager()->putPhpContents($this->objCacheFile, $this->objData, true);
                if ($isSaved === false) {
                    $GLOBALS['log']->emergency('Metadata:objInit() - metadata has not been saved to a cache file');
                }
            }
        }
    }

    /**
     * Compose metadata
     *
     * @return \stdClass
     */
    private function composeMetadata()
    {
        // load espo
        $content = $this->unify('application/Espo/Resources/metadata');

        // load treo
        $content = DataUtil::merge($content, $this->unify('application/Treo/Resources/metadata'));

        // load modules
        foreach ($this->moduleManager->getModules() as $module) {
            $content = DataUtil::merge($content, $module->getMetadata());
        }

        // load custom
        $content = DataUtil::merge($content, $this->unify('custom/Espo/Custom/Resources/metadata'));

        return $content;
    }

    /**
     * @param string $path
     *
     * @return \stdClass
     */
    private function unify(string $path): \stdClass
    {
        return $this->getObjUnifier()->unify('metadata', ['corePath' => $path], true);
    }
}
