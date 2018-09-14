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

namespace Espo\Modules\TreoCore\Hooks\Portal;

use Espo\Core\Exceptions\BadRequest;
use Treo\Core\Portal\Application as PortalApp;
use Espo\ORM\Entity;
use Espo\Modules\TreoCore\Core\Hooks\AbstractHook;

/**
 * Portal hook
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Hook extends AbstractHook
{
    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function beforeSave(Entity $entity, $options = [])
    {
        // prepare url
        $this->preparePortalUrl($entity);

        // validate url
        $this->validateUrl($entity);
    }

    /**
     * @param Entity $entity
     * @param array  $options
     *
     * @throws BadRequest
     */
    public function afterSave(Entity $entity, $options = [])
    {
        // set url
        $this->setUrl($entity);
    }

    /**
     * @param Entity $entity
     * @param array  $options
     */
    public function afterRemove(Entity $entity, $options = [])
    {
        // unsetUrl
        $this->unsetUrl($entity);
    }

    /**
     * Prepare portal url
     *
     * @param Entity $entity
     */
    protected function preparePortalUrl(Entity $entity): void
    {
        // get site url
        $siteUrl = $this->getConfig()->get('siteUrl');

        // get domain
        $domain = str_replace(['http://', 'https://'], ['', ''], $siteUrl);

        // get entity data
        $data = $entity->toArray();

        // prepare url
        if (isset($data['url']) && empty($data['url'])) {
            $entity->set('url', $siteUrl . '/portal-' . $entity->get('id'));
        } else {
            if (!empty($data['url'])
                && preg_match_all("/^(http|https)\:\/\/{$domain}\/(.*)$/", $data['url'], $matches)) {
                $parts = explode('/', $matches[2][0]);
                if (count($parts) > 1) {
                    $path = implode('-', $parts);
                } else {
                    $path = $matches[2][0];
                }

                $entity->set('url', $siteUrl . '/' . $path);
            }
        }
    }

    /**
     * Validate URL
     *
     * @return null
     * @throws BadRequest
     */
    protected function validateUrl(Entity $entity)
    {
        if (empty($url = $entity->get('url'))) {
            return;
        }

        // validate url
        if (!filter_var($url, FILTER_VALIDATE_URL)
        ) {
            throw new BadRequest($this->translate('URL is invalid', 'exceptions'));
        }

        if (preg_match_all('/^(http|https)\:\/\/(.*)\/(.*)$/', $url, $matches)) {
            if (!empty($path = $matches[3][0])) {
                if (!preg_match('/^[a-z0-9\-]*$/', $path)) {
                    throw new BadRequest($this->translate('URL is invalid', 'exceptions'));
                }
            }
        }

        // get all urls
        $urls = PortalApp::getUrlFileData();

        // validate by unique
        if (in_array($url, $urls)) {
            if (array_search($url, $urls) != $entity->get('id')) {
                throw new BadRequest($this->translate('Such URL is already exists', 'exceptions'));
            }
        }

        return;
    }

    /**
     * Set url
     *
     * @param Entity $entity
     */
    protected function setUrl(Entity $entity): void
    {
        if (!empty($url = $entity->get('url'))) {
            // get urls
            $urls = PortalApp::getUrlFileData();

            // push
            $urls[$entity->get('id')] = $url;

            // save
            PortalApp::saveUrlFile($urls);
        }
    }

    /**
     * Unset url
     *
     * @param Entity $entity
     */
    protected function unsetUrl(Entity $entity): void
    {
        // get urls
        $urls = PortalApp::getUrlFileData();

        if (isset($urls[$entity->get('id')])) {
            // delete
            unset($urls[$entity->get('id')]);

            // save
            PortalApp::saveUrlFile($urls);
        }
    }
}
