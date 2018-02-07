<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Listeners;

use Espo\Modules\TreoCrm\Listeners\AbstractListener;
use Espo\Core\Utils\Json;

/**
 * SettingsController listener
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class SettingsController extends AbstractListener
{

    /**
     * Before update
     *
     * @param array $data
     *
     * @return void
     */
    public function afterUpdate(array $data): void
    {
        // regenerate multilang fields
        $data = Json::decode(Json::encode($data), true);
        if (isset($data['data']['inputLanguageList']) || $data['data']['isMultilangActive']) {
            $this->getContainer()->get('dataManager')->rebuild();
        }
    }
}
