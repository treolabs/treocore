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

namespace Treo\Core\ModuleManager;

/**
 * Class AbstractModule
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractModule
{
    /**
     * @var bool
     */
    protected $isTreoModule = true;

    /**
     * @var string
     */
    private $appPath;

    /**
     * @var array
     */
    private $package;

    /**
     * Get module load order
     *
     * @return int
     */
    abstract public static function getLoadOrder(): int;

    /**
     * AbstractModule constructor.
     *
     * @param string $name
     * @param string $appPath
     * @param array  $package
     */
    public function __construct(string $appPath, array $package)
    {
        $this->appPath = $appPath;
        $this->package = $package;
    }

    /**
     * Get application path
     *
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * Get client path
     *
     * @return string
     */
    public function getClientPath(): string
    {
        return dirname($this->getAppPath()) . '/client/';
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getComposerName(): string
    {
        return (!empty($this->package['name'])) ? (string)$this->package['name'] : "-";
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        if (!empty($this->package['extra']['name']['default'])) {
            return (string)$this->package['extra']['name']['default'];
        }

        return "";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        if (!empty($this->package['extra']['description']['default'])) {
            return (string)$this->package['extra']['description']['default'];
        }

        return "";
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return (!empty($this->package['version'])) ? $this->package['version'] : "";
    }
}
