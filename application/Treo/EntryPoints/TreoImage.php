<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
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

namespace Treo\EntryPoints;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

/**
 * EntryPoint Image
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class TreoImage extends \Espo\Core\EntryPoints\Base
{
    /**
     * @var bool
     */
    public static $authRequired = true;

    /**
     * @var array
     */
    protected $mimeTypes
        = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif'
        ];

    /**
     * @param array $data
     */
    public function run(array $data = [])
    {
        // get attachment
        $attachment = $this->getEntityManager()->getEntity('Attachment', $data['id']);
        if (!$attachment) {
            throw new NotFound();
        }

        // is granted ?
        if (!$this->getAcl()->checkEntity($attachment)) {
            throw new Forbidden();
        }

        // get file path
        $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($attachment);
        if (!file_exists($filePath)) {
            throw new NotFound();
        }

        // get mime type
        $mimeType = $attachment->get('type');
        if (empty($this->mimeTypes[$mimeType]) || $this->mimeTypes[$mimeType] != $data['mimeType']) {
            throw new NotFound();
        }

        // prepare file name
        $fileName = $data['id'] . '.' . $data['mimeType'];

        header('Content-Disposition:inline;filename="' . $fileName . '"');
        header('Content-Type: ' . $mimeType);
        header('Pragma: public');
        header('Cache-Control: max-age=360000, must-revalidate');
        $fileSize = filesize($filePath);
        if ($fileSize) {
            header('Content-Length: ' . $fileSize);
        }
        readfile($filePath);
        exit;
    }
}
