<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Services;

use Espo\Modules\Revisions\Services\RevisionField as ParentRevisionField;
use Espo\ORM\EntityCollection;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Slim\Http\Request;

/**
 * RevisionField service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class RevisionField extends ParentRevisionField
{
    /**
     * @var array
     */
    protected $dependencies = [
        'config',
        'entityManager',
        'user',
        'metadata',
    ];

    /**
     * @var array
     */
    protected $multilangConfig = null;

    /**
     * Prepare data
     *
     * @param array $params
     * @param EntityCollection $notes
     * @param Request $request
     *
     * @return array
     */
    protected function prepareData(array $params, EntityCollection $notes, Request $request): array
    {
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

        // get field type
        $fieldType = $this
            ->getInjection('metadata')
            ->get('entityDefs.'.$params['entity'].'.fields.'.$params['field'].'.type');

        if ($this->getConfig()->get('isMultilangActive') && in_array($fieldType, $this->getMultilangFields())) {
            foreach ($notes as $note) {
                // prepare data
                $data = Json::decode(Json::encode($note->get('data')), true);

                // prepare list
                if (isset($data['fields']) && in_array($params['field'], $data['fields'])) {
                    if ($max > count($result['list']) && $result['total'] >= $offset) {
                        $result['list'][] = [
                            "id"       => $note->get('id'),
                            "date"     => $note->get('createdAt'),
                            "userId"   => $note->get('createdById'),
                            "userName" => $note->get('createdBy')->get('name'),
                            "locale"   => '',
                            "was"      => $data['attributes']['was'][$params['field']],
                            "became"   => $data['attributes']['became'][$params['field']]
                        ];
                    }
                    $result['total'] = $result['total'] + 1;

                    foreach ($this->getConfig()->get('inputLanguageList') as $locale) {
                        // prepare data
                        $fieldName = Util::toCamelCase($params['field'].'_'.strtolower($locale));
                        $was       = $data['attributes']['was'][$fieldName];
                        $became    = $data['attributes']['became'][$fieldName];

                        if ($was != $became) {
                            if ($max > count($result['list']) && $result['total'] >= $offset) {
                                $result['list'][] = [
                                    "id"       => $note->get('id').$locale,
                                    "date"     => $note->get('createdAt'),
                                    "userId"   => $note->get('createdById'),
                                    "userName" => $note->get('createdBy')->get('name'),
                                    "locale"   => $locale,
                                    "was"      => $data['attributes']['was'][$fieldName],
                                    "became"   => $data['attributes']['became'][$fieldName]
                                ];
                            }
                            $result['total'] = $result['total'] + 1;
                        }
                    }
                }
            }
        } else {
            $result = parent::prepareData($params, $notes, $request);
        }

        return $result;
    }

    /**
     * Get multilang fields
     *
     * @return array
     */
    protected function getMultilangFields(): array
    {
        // get config
        $config = $this->getMultilangConfig();

        return (!empty($config['multilangFields'])) ? $config['multilangFields'] : [];
    }

    /**
     * Get multilang config
     *
     * @return array
     */
    protected function getMultilangConfig(): array
    {
        if (is_null($this->multilangConfig)) {
            $this->multilangConfig = include 'application/Espo/Modules/Multilang/Configs/Config.php';
        }

        return $this->multilangConfig;
    }
}
