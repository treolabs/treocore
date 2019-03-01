<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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

namespace Treo\Core\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Class LabelManagerTest
 *
 * @author r.zablodskiy@treolabs.com
 */
class LabelManagerTest extends TestCase
{
    /**
     * Test getScopeData method
     */
    public function testGetScopeDataMethod()
    {
        $mock = $this->createPartialMock(LabelManager::class, ['getLanguage']);
        $language = $this->createPartialMock(Language::class, ['get']);

        $language
            ->expects($this->any())
            ->method('get')
            ->willReturn([]);
        $mock

            ->expects($this->any())
            ->method('getLanguage')
            ->willReturn($language);

        // test 1
        $this->assertEquals((object)[], $mock->getScopeData('en_US', 'Test'));

        $mock = $this->createPartialMock(
            LabelManager::class,
            ['getLanguage', 'getEntityLabels', 'getOptionsLabels', 'getScopeNames']
        );
        $language = $this->createPartialMock(Language::class, ['get']);

        $language
            ->expects($this->any())
            ->method('get')
            ->willReturn([
                'fields' => [
                    'field1' => 'field1Label'
                ],
                'links' => [
                    'link1' => 'link1Label'
                ],
                'options' => [
                    'field3' => 'field3Link'
                ]
            ]);

        $mock
            ->expects($this->any())
            ->method('getLanguage')
            ->willReturn($language);
        $mock
            ->expects($this->any())
            ->method('getEntityLabels')
            ->willReturn([
                'fields' => [
                    'field1' => 'field1Label',
                    'field2' => 'field2Label'
                ],
                'links' => [
                    'link1' => 'link1Label',
                    'link2' => 'link2Label'
                ],
                'options' => [
                    'field3' => 'field3Link'
                ]
            ]);
        $mock
            ->expects($this->any())
            ->method('getOptionsLabels')
            ->willReturn([
                'fields' => [
                    'field1' => 'field1Label',
                    'field2' => 'field2Label'
                ],
                'links' => [
                    'link1' => 'link1Label',
                    'link2' => 'link2Label'
                ],
                'options' => [
                    'field3' => 'field3Link',
                    'field4' => 'field4Link'
                ]
            ]);
        $mock
            ->expects($this->any())
            ->method('getScopeNames')
            ->willReturn([
                'fields' => [
                    'field1' => 'field1Label',
                    'field2' => 'field2Label'
                ],
                'links' => [
                    'link1' => 'link1Label',
                    'link2' => 'link2Label'
                ],
                'options' => [
                    'field3' => 'field3Link',
                    'field4' => 'field4Link'
                ]
            ]);

        // test 2
        $expects = [
            'fields' => [
                'fields[.]field1' => 'field1Label',
                'fields[.]field2' => 'field2Label'
            ],
            'links' => [
                'links[.]link1' => 'link1Label',
                'links[.]link2' => 'link2Label'
            ],
            'options' => [
                'options[.]field3' => 'field3Link',
                'options[.]field4' => 'field4Link'
            ]];
        $this->assertEquals($expects, $mock->getScopeData('en_US', 'Test'));
    }
}
