<?php
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
