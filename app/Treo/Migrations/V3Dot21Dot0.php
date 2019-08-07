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

use Treo\Core\FilePathBuilder;
use Treo\Core\Migration\AbstractMigration;
use Treo\Core\Utils\Util;

/**
 * Migration class for version 3.20.3
 *
 * @author r.ratsun@treolabs.com
 */
class V3Dot21Dot0 extends AbstractMigration
{
    const OLD_BASE_PATH = "data/upload/";
    const NEW_BASE_PATH = "data/upload/files/";

    /**
     * @throws \Espo\Core\Exceptions\Error
     */
    public function up(): void
    {
        foreach ($this->getAttachments() as $attachment) {
            if (!file_exists(self::OLD_BASE_PATH . $attachment['id'])) {
                continue;
            }

            $path = $this->getPathBuilder()->createPath(FilePathBuilder::UPLOAD);

            $oldPath = self::OLD_BASE_PATH . $attachment['id'];
            $newPath = self::NEW_BASE_PATH . $path . '/' . $attachment['name'];

            if (!$this->getFileManager()->move($oldPath, $newPath)) {
                continue;
            }

            $this->setDAM($attachment['id'], "UploadDir", $path);
        }

        Util::removedir("data/upload/thumbs");
    }

    /**
     * @throws \Espo\Core\Exceptions\Error
     */
    public function down(): void
    {
        foreach ($this->getAttachments() as $attachment) {
            if (!file_exists(self::NEW_BASE_PATH . $attachment['storage_file_path'] . "/" . $attachment['name'])) {
                continue;
            }

            $oldPath = self::NEW_BASE_PATH . $attachment['storage_file_path'] . '/' . $attachment['name'];
            $newPath = self::OLD_BASE_PATH . $attachment['id'];

            if (!rename($oldPath, $newPath)) {
                continue;
            }

            $this->unsetDAM($attachment['id']);
        }

        Util::removedir("data/upload/thumbs");
    }

    protected function unsetDAM($id)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $pdo->exec("UPDATE attachment SET `storage` = NULL, `storage_file_path` = NULL WHERE id = '{$id}'");
    }

    protected function setDAM($id, $storage, $filePath)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $pdo->exec("UPDATE attachment SET `storage`='{$storage}', `storage_file_path`='{$filePath}' WHERE id='{$id}'");
    }

    /**
     * @return array
     */
    protected function getAttachments()
    {
        $query = $this->getEntityManager()->getPDO()->query("SELECT * FROM attachment WHERE deleted = 0");

        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return mixed
     */
    protected function getFileManager()
    {
        return $this->container->get("fileManager");
    }

    protected function getPathBuilder()
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
}
