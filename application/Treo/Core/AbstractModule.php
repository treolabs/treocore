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

namespace Treo\Core;

use Espo\Core\Utils\File\Unifier;

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
    private $name;

    /**
     * @var string
     */
    private $appPath;

    /**
     * @var Container
     */
    private $container;

    /**
     * Get module load order
     *
     * @return int
     */
    abstract public static function getLoadOrder(): int;

    /**
     * AbstractModule constructor.
     *
     * @param string    $name
     * @param string    $appPath
     * @param Container $container
     */
    public function __construct(string $name, string $appPath, Container $container)
    {
        $this->name = $name;
        $this->appPath = $appPath;
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        // prepare file
        $file = $this->getAppPath() . 'Resources/routes.json';

        $result = [];
        if (file_exists($file)) {
            $result = json_decode(file_get_contents($file), true);
        }

        return $result;
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
     * Get module name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
     * Get metadata
     *
     * @return \stdClass
     */
    public function getMetadata()
    {
        return $this->getObjUnifier()->unify('metadata', $this->getAppPath() . 'Resources/metadata', true);
    }

    /**
     * Get container
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @return Unifier
     */
    protected function getObjUnifier(): Unifier
    {
        return new Unifier($this->getContainer()->get('fileManager'), $this->getContainer()->get('metadata'), true);
    }
}
