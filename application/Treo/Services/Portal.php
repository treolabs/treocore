<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
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
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Services;

use Espo\ORM\Entity;
use Treo\Core\Portal\Application as PortalApp;

/**
 * Portal service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Portal extends \Espo\Services\Record
{
    /**
     * @var null|array
     */
    protected $urls = null;

    /**
     * @param Entity $entity
     */
    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $this->setUrl($entity);
    }

    /**
     * @param Entity $entity
     */
    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFieldsForList($entity);

        $this->setUrl($entity);
    }

    /**
     * Set url
     *
     * @param Entity $entity
     */
    protected function setUrl(Entity $entity): void
    {
        if (!empty($url = $this->getUrls()[$entity->get('id')])) {
            $entity->set('url', $url);
        }
    }

    /**
     * Get urls
     *
     * @return array
     */
    protected function getUrls(): array
    {
        if (is_null($this->urls)) {
            $this->urls = PortalApp::getUrlFileData();
        }

        return $this->urls;
    }
}
