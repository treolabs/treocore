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

namespace Treo\Migrations;

use Espo\ORM\EntityManager;
use Treo\Core\FileStorage\Storages\UploadDir;
use Treo\Core\Migration\AbstractMigration;
use Treo\Core\Utils\Util;
use Treo\Entities\Attachment;
use Treo\Services\FileMigrate;

/**
 * Migration class for version 3.20.3
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot21Dot0 extends AbstractMigration
{
    const OLD_BASE_PATH = "data/upload/";

    /**
     * @throws \Espo\Core\Exceptions\Error
     */
    public function up(): void
    {
        $mover = new FileMigrate($this->getContainer());

        foreach ($this->getAttachments() as $attachment) {
            $mover->setAttachment($attachment);

            if ($mover->fileExist()) {
                $mover->moveFile();
            }
        }

        Util::removedir("data/upload/thumbs");
    }

    /**
     * @throws \Espo\Core\Exceptions\Error
     */
    public function down(): void
    {
        foreach ($this->getAttachments() as $attachment) {
            if (!$this->fileExist($attachment)) {
                continue;
            }

            $oldPath = UploadDir::BASE_PATH . $attachment->get('storageFilePath') . '/' . $attachment->get('name');
            $newPath = self::OLD_BASE_PATH . $attachment->id;

            if (!$this->getFileManager()->move($oldPath, $newPath)) {
                continue;
            }

            $attachment->set("storageFilePath", null);
            $attachment->set("storage", null);
            $this->getRepository()->save($attachment);
        }

        Util::removedir("data/upload/thumbs");
    }

    /**
     * @return array
     */
    protected function getAttachments()
    {
        return $this->getEntityManager()->getRepository('Attachment')->find();
    }

    /**
     * @return mixed
     */
    protected function getFileManager()
    {
        return $this->container->get("fileManager");
    }

    /**
     * @param Attachment $attachment
     *
     * @return bool
     */
    protected function fileExist(Attachment $attachment)
    {
        if ($attachment->get('storageFilePath')) {
            return file_exists(
                UploadDir::BASE_PATH . $attachment->get('storageFilePath') . "/" .
                $attachment->get('name')
            );
        } else {
            return file_exists(self::OLD_BASE_PATH . $attachment->id);
        }
    }

    /**
     * @return \Treo\Repositories\Attachment
     */
    protected function getRepository(): \Treo\Repositories\Attachment
    {
        return $this->getEntityManager()->getRepository("Attachment");
    }
}
