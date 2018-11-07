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

namespace Treo\Services;

/**
 * Class Store
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Store extends AbstractService
{
    /**
     * Get list
     *
     * @return array
     */
    public function getList(): array
    {
        // prepare result
        $result = [
            'total' => 0,
            'list'  => []
        ];

        if (!empty($packages = $this->getPackages())) {
            // get installed panel data
            $installed = $this->getInstalled();

            foreach ($packages as $package) {
                if (!in_array($package['treoId'], $installed)) {
                    $result['list'][] = [
                        'id'          => $package['treoId'],
                        'name'        => $this->packageTranslate($package['name'], $package['treoId']),
                        'description' => $this->packageTranslate($package['description'], '-'),
                        'status'      => $package['status'],
                        'versions'    => $package['versions']
                    ];
                }

            }

            // prepare total
            $result['total'] = count($result['list']);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getPackages(): array
    {
        return $this->getContainer()->get('serviceFactory')->create('Packagist')->getPackages(true);
    }

    /**
     * @return array
     */
    protected function getComposerDiff(): array
    {
        return $this->getContainer()->get('serviceFactory')->create('Composer')->getComposerDiff();
    }

    /**
     * @return array
     */
    protected function getModuleList(): array
    {
        return $this->getContainer()->get('metadata')->getModuleList();
    }

    /**
     * @return array
     */
    protected function getInstalled()
    {
        return array_merge($this->getModuleList(), array_column($this->getComposerDiff()['install'], 'id'));
    }

    /**
     * @param array  $field
     * @param string $default
     *
     * @return string
     */
    protected function packageTranslate(array $field, string $default = ''): string
    {
        // get current language
        $currentLang = $this->getContainer()->get('language')->getLanguage();

        $result = $default;
        if (!empty($field[$currentLang])) {
            $result = $field[$currentLang];
        } elseif ($field['default']) {
            $result = $field['default'];
        }

        return $result;
    }
}
