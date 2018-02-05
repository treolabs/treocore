<?php
declare(strict_types = 1);

namespace TreoComposer;

use Composer\Script\Event;

/**
 * Class of Install
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Install extends AbstractEvent
{

    /**
     * Post install event
     *
     * @param Event $event
     *
     * @return void
     */
    public static function postEvent(Event $event)
    {
        // get vendor dir
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        // prepare treo crm vendor dir path
        $path = "{$vendorDir}/treo-crm/";

        if (file_exists($path) && is_dir($path)) {
            // update backend files
            self::updateBackend($path);

            // update frontend files
            self::updateFrontend($path);

            // show message
            self::echoSuccess('TreoCRM modules successfully installed!');
        }
    }
}
