<?php
/**
 * This file is part of EspoCRM and/or TreoCORE.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Jobs;

use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Espo\Core\Utils\Database\DBAL\Schema\Comparator;
use Treo\Core\Utils\Database\Schema\Converter;
use Treo\Core\Utils\Database\Schema\Schema;

/**
 * Class TreoCleanup
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class TreoCleanup extends \Espo\Core\Jobs\Base
{
    /**
     * Run cron job
     *
     * @return bool
     */
    public function run(): bool
    {
        // cleanup deleted rows from all tables
        $this->cleanupDeleted();

        // cleanup deleted attachments
        $this->cleanupAttachments();

        // cleanup cron jobs
        $this->cleanupJobs();

        return true;
    }

    /**
     * Cleanup deleted rows from all tables
     */
    protected function cleanupDeleted(): void
    {
        // get tables
        if (!empty($tables = $this->getTables())) {
            foreach ($tables as $table) {
                $this->execute("DELETE FROM $table WHERE deleted=1");
            }
        }
    }

    /**
     * Cleanup deleted attachments
     */
    protected function cleanupAttachments(): void
    {
        // prepare path
        $upload = "data/upload";

        if (file_exists($upload) && is_dir($upload)) {
            $files = scandir($upload);
            if (!empty($files)) {
                foreach ($files as $file) {
                    if (!in_array($file, [".", ".."]) && !is_dir($upload . "/" . $file)) {
                        $ids[] = $file;
                    }
                }
            }

            if (!empty($ids)) {
                $attachments = $this
                    ->getEntityManager()
                    ->getRepository('Attachment')
                    ->select(['id', 'relatedId', 'parentId'])
                    ->where([['OR' => [['id' => $ids], ['relatedId' => $ids], ['parentId' => $ids]]]])
                    ->find();

                $attachmentsIds = [];
                if (!empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        $attachmentsIds[] = $attachment->get('id');
                        $attachmentsIds[] = $attachment->get('relatedId');
                        $attachmentsIds[] = $attachment->get('parentId');
                    }
                    $attachmentsIds = array_unique($attachmentsIds);
                }

                foreach ($ids as $id) {
                    if (!in_array($id, $attachmentsIds)) {
                        unlink($upload . "/" . $id);
                    }
                }
            }
        }
    }

    /**
     * Cleanup jobs
     */
    protected function cleanupJobs(): void
    {
        // prepare date
        $date = (new \DateTime())->modify("-1 month")->format('Y-m-d');

        $this->execute("DELETE FROM job WHERE DATE(execute_time)<'{$date}' AND status IN ('Success','Failed')");
    }

    /**
     * @param string $sql
     */
    protected function execute(string $sql): void
    {
        if (!empty($sql)) {
            try {
                $sth = $this
                    ->getEntityManager()
                    ->getPDO()
                    ->prepare($sql);
                $sth->execute();
            } catch (\Exception $e) {
                // something wrong
            }
        }
    }

    /**
     * @return array
     */
    protected function getTables(): array
    {
        // prepare metadata schema
        $metadataSchema = $this
            ->getConverter()
            ->process($this->getContainer()->get('ormMetadata')->getData());

        // get schema diff
        $schemaDiff = (new Comparator())->compare(new DoctrineSchema(), $metadataSchema);

        // prepare result
        $result = [];
        if (!empty($schemaDiff->newTables)) {
            $result = array_keys($schemaDiff->newTables);
            natsort($result);
        }

        return $result;
    }

    /**
     * @return Schema
     */
    protected function getSchema(): Schema
    {
        return new Schema(
            $this->getContainer()->get('config'),
            $this->getContainer()->get('metadata'),
            $this->getContainer()->get('fileManager'),
            $this->getContainer()->get('entityManager'),
            $this->getContainer()->get('classParser'),
            $this->getContainer()->get('ormMetadata')
        );
    }

    /**
     * @return Converter
     */
    protected function getConverter(): Converter
    {
        return new Converter(
            $this->getContainer()->get('metadata'),
            $this->getContainer()->get('fileManager'),
            $this->getSchema(),
            $this->getContainer()->get('config')
        );
    }
}
