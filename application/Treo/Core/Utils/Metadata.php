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
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Util;
use Treo\Core\Utils\File\Unifier;

/**
 * Metadata class
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Metadata extends Base
{
    /**
     * @var Unifier
     */
    protected $unifier;

    /**
     * @var Unifier
     */
    protected $objUnifier;

    /**
     * @var Module
     */
    protected $moduleConfig = null;

    /**
     * @inheritdoc
     */
    public function init($reload = false)
    {
        parent::init($reload);
    }

    /**
     * @inheritdoc
     */
    public function getAllForFrontend($reload = false)
    {
        return parent::getAllForFrontend();
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
     * Get module config
     *
     * @return Module
     */
    protected function getModuleConfig(): Module
    {
        if (!isset($this->moduleConfig)) {
            $this->moduleConfig = new Module($this->getFileManager(), $this->useCache);
        }

        return $this->moduleConfig;
    }

    /**
     * @inheritdoc
     */
    protected function getUnifier()
    {
        if (!isset($this->unifier)) {
            $this->unifier = new Unifier($this->getFileManager(), $this, false);
        }

        return $this->unifier;
    }

    /**
     * @inheritdoc
     */
    protected function getObjUnifier()
    {
        if (!isset($this->objUnifier)) {
            $this->objUnifier = new Unifier($this->getFileManager(), $this, true);
        }

        return $this->objUnifier;
    }
}
