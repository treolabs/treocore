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

namespace Treo\EntryPoints;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\Attachment;
use Treo\PHPUnit\Framework\TestCase;

/**
 * Class TreoImageTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class TreoImageTest extends TestCase
{
    /**
     * Test is run method throw exception
     */
    public function testIsRunThrowException()
    {
        try {
            $service = $this->createMockService(TreoImage::class, ['getEntity']);

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn(null);

            $service->run(['id' => 'some-id']);
        } catch (\Exception $e) {
            // test 1
            $this->assertInstanceOf(NotFound::class, $e);
        }

        try {
            $service = $this->createMockService(TreoImage::class, ['getEntity', 'checkEntity']);
            $attachment = $this->createMockService(Attachment::class);

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($attachment);
            $service
                ->expects($this->any())
                ->method('checkEntity')
                ->willReturn(false);

            $service->run(['id' => 'some-id']);
        } catch (\Exception $e) {
            // test 2
            $this->assertInstanceOf(Forbidden::class, $e);
        }

        try {
            $service = $this->createMockService(
                TreoImage::class,
                ['getEntity', 'checkEntity', 'getFilePath', 'fileExists']
            );
            $attachment = $this->createMockService(Attachment::class);

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($attachment);
            $service
                ->expects($this->any())
                ->method('checkEntity')
                ->willReturn(true);
            $service
                ->expects($this->any())
                ->method('getFilePath')
                ->willReturn('not/existing/file.file');
            $service
                ->expects($this->any())
                ->method('fileExists')
                ->willReturn(false);

            $service->run(['id' => 'some-id']);
        } catch (\Exception $e) {
            // test 3
            $this->assertInstanceOf(NotFound::class, $e);
        }

        try {
            $service = $this->createMockService(
                TreoImage::class,
                ['getEntity', 'checkEntity', 'getFilePath', 'fileExists']
            );
            $attachment = $this->createMockService(Attachment::class, ['get']);
            $attachment
                ->expects($this->any())
                ->method('get')
                ->willReturn('file/file');

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($attachment);
            $service
                ->expects($this->any())
                ->method('checkEntity')
                ->willReturn(true);
            $service
                ->expects($this->any())
                ->method('getFilePath')
                ->willReturn('path/to/file.file');
            $service
                ->expects($this->any())
                ->method('fileExists')
                ->willReturn(true);

            $service->run(['id' => 'some-id']);
        } catch (\Exception $e) {
            // test 4
            $this->assertInstanceOf(NotFound::class, $e);
        }

        try {
            $service = $this->createMockService(
                TreoImage::class,
                ['getEntity', 'checkEntity', 'getFilePath', 'fileExists']
            );
            $attachment = $this->createMockService(Attachment::class, ['get']);
            $attachment
                ->expects($this->any())
                ->method('get')
                ->willReturn('file/file');

            $service
                ->expects($this->any())
                ->method('getEntity')
                ->willReturn($attachment);
            $service
                ->expects($this->any())
                ->method('checkEntity')
                ->willReturn(true);
            $service
                ->expects($this->any())
                ->method('getFilePath')
                ->willReturn('path/to/file.file');
            $service
                ->expects($this->any())
                ->method('fileExists')
                ->willReturn(true);

            $service->run(['id' => 'some-id', 'mime-type' => 'jpg']);
        } catch (\Exception $e) {
            // test 5
            $this->assertInstanceOf(NotFound::class, $e);
        }
    }
}
