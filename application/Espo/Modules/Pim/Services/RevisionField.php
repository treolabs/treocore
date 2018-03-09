<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

declare(strict_types = 1);

namespace Espo\Modules\Pim\Services;

use Espo\Modules\Multilang\Services\RevisionField as MultilangRevisionField;
use Espo\ORM\EntityCollection;
use Espo\Core\Utils\Json;
use Slim\Http\Request;

/**
 * RevisionField service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class RevisionField extends MultilangRevisionField
{

    /**
     * Prepare data
     *
     * @param array $params
     * @param EntityCollection $notes
     * @param int $max
     *
     * @return array
     */
    protected function prepareData(array $params, EntityCollection $notes, Request $request): array
    {
        if (!empty($request->get('isAttribute'))) {
            // prepare result
            $result = [
                'total' => 0,
                'list'  => []
            ];

            // prepare params
            $max    = (int) $request->get('maxSize');
            $offset = (int) $request->get('offset');
            if (empty($max)) {
                $max = $this->maxSize;
            }

            foreach ($notes as $note) {
                if (!empty($note->get('attributeId')) && $note->get('attributeId') == $params['field']) {
                    // prepare data
                    $data = Json::decode(Json::encode($note->get('data')), true);

                    foreach ($data['fields'] as $field) {
                        if ($max > count($result['list']) && $result['total'] >= $offset) {
                            // prepare locale
                            $locale = '';
                            foreach ($this->getConfig()->get('inputLanguageList') as $loc) {
                                if (strpos($field, " ($loc)") !== false) {
                                    $locale = $loc;
                                }
                            }

                            $result['list'][] = [
                                "id"       => $note->get('id').$locale,
                                "date"     => $note->get('createdAt'),
                                "userId"   => $note->get('createdById'),
                                "userName" => $note->get('createdBy')->get('name'),
                                "locale"   => $locale,
                                "was"      => $data['attributes']['was'][$field],
                                "became"   => $data['attributes']['became'][$field]
                            ];
                        }
                        $result['total'] = $result['total'] + 1;
                    }
                }
            }
        } else {
            $result = parent::prepareData($params, $notes, $request);
        }

        return $result;
    }
}
