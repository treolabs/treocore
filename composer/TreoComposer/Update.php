<?php
declare(strict_types = 1);

namespace TreoComposer;

use Composer\Script\Event;

/**
 * Class of Update
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Update extends AbstractEvent
{

    /**
     * Post update event
     *
     * @param Event $event
     *
     * @return void
     */
    public static function postEvent(Event $event)
    {
        // prepare treo dir name
        $treoDir = self::TREODIR;

        // prepare treo crm vendor dir path
        $path = "vendor/{$treoDir}/";

        if (file_exists($path) && is_dir($path)) {
            // update backend files
            self::updateBackend();

            // update frontend files
            self::updateFrontend();
        }
    }
}
