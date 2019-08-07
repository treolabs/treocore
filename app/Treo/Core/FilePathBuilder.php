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

use Espo\Core\Exceptions\Error;
use Espo\ORM\Metadata;
use Treo\Core\FileStorage\Storages\UploadDir;
use Treo\Core\Utils\File\Manager;
use Treo\Core\Utils\Random;
use Treo\Core\Utils\Util;

/**
 * Class FilePathBuilder
 * @package Treo\Core
 */
class FilePathBuilder
{
    const UPLOAD = 'upload';
    const LAST_CREATED = "lastCreated";

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

    public static function folderPath()
    {
        return [
            "upload" => UploadDir::BASE_PATH,
        ];
    }


    public function createPath(string $type): string
    {
        $typeEl = explode("/", $type);
        $type = array_shift($typeEl);
        $folderPath = implode("/", $typeEl);

        $baseFolder = static::folderPath()[$type] . ($folderPath ? ($folderPath . "/") : "");

        if (!file_exists($baseFolder . "lastCreated")) {
            $path = $this->init($type);
        } else {
            $path = $this->buildPath($type, $folderPath);
        }
        $res = implode("/", $path);
        array_pop($path);
        $this->getFileManager()->putContents($baseFolder . self::LAST_CREATED, implode('/', $path));

        return $res;
    }

    protected function buildPath(string $type, string $folderPath)
    {
        $basePath = static::folderPath()[$type];
        $folderInfo = $this->getMeta()->get(['app', 'fileStorage', $type]);
        $iter = 0;
        $backIter = 0;
        $path = file_get_contents($basePath . self::LAST_CREATED);

        if (empty($path)) {
            throw new Error();
        }

        $pathEl = explode("/", $path);

        while (!$iter) {
            $count = Util::countItems($this->getPath($basePath) . implode("/", $pathEl));

            if ($count < $folderInfo['maxFilesInFolder']) {
                $iter = $folderInfo['folderDepth'] - $backIter;
                break;
            }
            if (!$pathEl) {
                throw new Error("Folder limit");
            }
            array_pop($pathEl);

            $backIter++;
        }

        for ($iter; $iter <= $folderInfo['folderDepth']; $iter++) {
            while ($iter) {
                $folderName = Random::getString($folderInfo['folderNameLength']);
                if (is_dir($this->getPath($basePath) . implode("/", $pathEl) . "/" . $folderName)) {
                    continue;
                }
                $pathEl[] = $folderName;
                break;
            }
        }


        return $pathEl;
    }

    protected function getPath($path)
    {
        return realpath($path);
    }

    protected function init(string $type): array
    {
        $depth = $this->getMeta()->get(['app', 'fileStorage', $type, 'folderDepth']) ?? 3;
        $folderNameLength = $this->getMeta()->get(['app', 'fileStorage', $type, 'folderNameLength']) ?? 3;
        $path = [];

        for ($i = 1; $i < $depth; $i++) {
            $folderName = Random::getString($folderNameLength);
            $path[] = $folderName;
        }
        $path[] = Random::getString($folderNameLength);

        return $path;
    }

    /**
     * @return Metadata|null
     */
    protected function getMeta()
    {
        return $this->container->get('metadata');
    }

    protected function getFileManager(): Manager
    {
        return $this->container->get("fileManager");
    }
}
