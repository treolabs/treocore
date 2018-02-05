<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Listeners;

use Espo\Modules\TreoCrm\Listeners\AbstractListener;
use Espo\Modules\Multilang\Services\MultiLang as MultiLangService;
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
    public function beforeUpdate(array $data): void
    {
        // regenerate multilang fields
        $data = Json::decode(Json::encode($data), true);
        if (isset($data['data']['inputLanguageList'])) {
            $this->getMultilangService()->regenerateMultiLang($data['data']['inputLanguageList']);
            $this->getContainer()->get('dataManager')->rebuild();
        }
    }

    /**
     * Get multilang service
     *
     * @return MultiLangService
     */
    protected function getMultilangService(): MultiLangService
    {
        return $this->getContainer()->get('serviceFactory')->create('MultiLang');
    }
}
