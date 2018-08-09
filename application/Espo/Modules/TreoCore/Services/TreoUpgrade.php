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

namespace Espo\Modules\TreoCore\Services;

use Espo\Core\Services\Base;

/**
 * TreoUpgrade service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class TreoUpgrade extends Base
{
    const TREO_PACKAGES_URL = 'http://treo-packages.zinit1.com/api/v1/Packages/';

    /**
     * @var null|array
     */
    protected $versionData = null;

    /**
     * Get available version
     *
     * @return string|null
     */
    public function getAvailableVersion(): ?string
    {
        // prepare result
        $result = null;

        if (!empty($data = $this->getVersionData($this->getConfig()->get('version')))
            && !empty($data['version'])) {
            $result = (string)$data['version'];
        }

        return $result;
    }

    /**
     * Get version data
     *
     * @param string $version
     *
     * @return array
     */
    protected function getVersionData(string $version): array
    {
        if (is_null($this->versionData)) {
            // prepare result
            $this->versionData = [];

            try {
                $json = file_get_contents(self::TREO_PACKAGES_URL . $version);
                if (is_string($json)) {
                    $data = json_decode($json, true);
                }
            } catch (\Exception $e) {
            }

            if (!empty($data) && is_array($data)) {
                $item = array_pop($data);
                if (is_array($item)) {
                    $this->versionData = $item;
                }
            }
        }

        return $this->versionData;
    }
}
