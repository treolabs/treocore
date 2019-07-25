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
    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $attachmentId;

    /**   t
     * FileMigrate constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setAttachmentId($id)
    {
        $this->attachmentId = $id;

        return $this;
    }

    /**
     * @return bool
     */
    public function fileExist()
    {
        return file_exists(UploadDir::BASE_PATH . $this->attachmentId);
    }

    /**
     * @return bool
     * @throws \Espo\Core\Exceptions\Error
     */
    public function moveFile()
    {
        $entity = $this->getEntityManager()->getEntity("Attachment", $this->attachmentId);

        if (!$entity) {
            $this->removeFile();

            return false;
        }

        $path = $this->getFilePathBuilder()->createPath(FilePathBuilder::UPLOAD);

        if (!$this->getFileManager()->move(UploadDir::BASE_PATH . $entity->get('id'), UploadDir::BASE_PATH . $path . '/' . $entity->get('name'))) {
            return false;
        }

        $entity->set("storageFilePath", $path);
        return $this->getRepository()->save($entity);
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
        return $this->getFileManager()->remove($this->attachmentId, UploadDir::BASE_PATH, true);
    }
}