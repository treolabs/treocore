<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
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
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

declare(strict_types=1);

namespace Treo\Core;

use Espo\ORM\Metadata;
use Treo\Core\Utils\Random;

/**
 * Class FilePathBuilder
 * @package Treo\Core
 */
class FilePathBuilder
{
    const UPLOAD = 'upload';

    /**
     * @var Container
     */
    protected $container;

    /**
     * DAMFilePathBuilder constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $type
     * @return string
     */
    public function createPath(string $type): string
    {
        $depth = $this->getMeta()->get(['app', 'fileStorage', $type, 'folderDepth']) ?? 3;
        $folderNameLength = $this->getMeta()->get(['app', 'fileStorage', $type, 'folderNameLength']) ?? 3;

        for ($i = 0; $i < $depth; $i++) {
            $part[] = Random::getString($folderNameLength);
        }


        return implode('/', $part);
    }

    /**
     * @return Metadata|null
     */
    private function getMeta()
    {
        return $this->container->get('metadata');
    }
}
