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

namespace Treo\Services;

use Espo\ORM\EntityManager;
use Treo\Core\Container;
use Treo\Core\FilePathBuilder;
use Treo\Core\FileStorage\Storages\UploadDir;
use Treo\Core\Utils\File\Manager;

/**
 * Class FileMigrate
 * @package Treo\Services
 */
class FileMigrate
{
    const OLD_BASE_PATH = "data/upload/";
    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Treo\Entities\Attachment
     */
    private $attachment;

    /**   t
     * FileMigrate constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param \Treo\Entities\Attachment $attachment
     * @return $this
     */
    public function setAttachment(\Treo\Entities\Attachment $attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return bool
     */
    public function fileExist()
    {
        if ($this->attachment->get('storageFilePath')) {
            return file_exists(UploadDir::BASE_PATH . $this->attachment->get('storageFilePath') . "/" . $this->attachment->get('name'));
        } else {
            return file_exists(self::OLD_BASE_PATH . $this->attachment->id);
        }
    }

    /**
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     */
    public function moveFile()
    {
        $path = $this->getFilePathBuilder()->createPath(FilePathBuilder::UPLOAD);

        $oldPath = self::OLD_BASE_PATH . $this->attachment->id;
        $newPath = UploadDir::BASE_PATH . $path . '/' . $this->attachment->get('name');

        if (!$this->getFileManager()->move($oldPath, $newPath)) {
            return false;
        }

        $this->attachment->set("storageFilePath", $path);
        return $this->getRepository()->save($this->attachment);
    }

    /**
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     */
    public function backFiles()
    {
        $oldPath = UploadDir::BASE_PATH . $this->attachment->get('storageFilePath') . '/' . $this->attachment->get('name');
        $newPath = self::OLD_BASE_PATH . $this->attachment->id;

        if (!$this->getFileManager()->move($oldPath, $newPath)) {
            return false;
        }

        $this->attachment->set("storageFilePath", null);
        $this->attachment->set("storage", null);
        return $this->getRepository()->save($this->attachment);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->container->get('entityManager');
    }

    /**
     * @return Manager
     */
    protected function getFileManager(): Manager
    {
        return $this->container->get('fileManager');
    }

    /**
     * @return FilePathBuilder
     */
    protected function getFilePathBuilder(): FilePathBuilder
    {
        return $this->container->get('filePathBuilder');
    }

    /**
     * @return \Treo\Repositories\Attachment
     */
    protected function getRepository(): \Treo\Repositories\Attachment
    {
        return $this->getEntityManager()->getRepository("Attachment");
    }

    /**
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function removeFile()
    {
        return $this->getFileManager()->remove($this->attachment->id, UploadDir::BASE_PATH, true);
    }
}