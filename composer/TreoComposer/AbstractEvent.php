<?php
declare(strict_types = 1);

namespace TreoComposer;

/**
 * Class of AbstractEvent
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractEvent
{
    /**
     * @var array
     */
    const SKIP = ['.', '..'];

    /**
     * @var string
     */
    const VENDOR = 'vendor';

    /**
     * @var string
     */
    const TREODIR = 'treo-crm';

    /**
     * Get treo modules
     *
     * @return array
     */
    public static function getTreoModules(): array
    {
        // prepare result
        $result = [];

        // prepare treo crm vendor dir path
        $path = "".self::VENDOR."/".self::TREODIR."/";

        if (file_exists($path) && is_dir($path)) {
            foreach (scandir($path) as $row) {
                if (!in_array($row, self::SKIP)) {
                    $modulePath = "{$path}/{$row}/application/Espo/Modules/";
                    if (file_exists($modulePath) && is_dir($modulePath)) {
                        foreach (scandir($modulePath) as $moduleId) {
                            if (!in_array($moduleId, self::SKIP)) {
                                $result[$moduleId] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Recursively copy files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function copyDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), "$dest/".$f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::copyDir($f->getRealPath(), "$dest/$f");
            }
        }

        return true;
    }

    /**
     * Recursively move files from one directory to another
     *
     * @param string $src
     * @param string $dest
     *
     * @return bool
     */
    protected static function moveDir(string $src, string $dest): bool
    {
        if (!is_dir($src)) {
            return false;
        }

        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new \DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), "$dest/".$f->getFilename());
            } elseif (!$f->isDot() && $f->isDir()) {
                self::moveDir($f->getRealPath(), "$dest/$f");
                unlink($f->getRealPath());
            }
        }
        unlink($src);

        return true;
    }

    /**
     * Delete directory
     *
     * @param string $dirname
     *
     * @return bool
     */
    protected static function deleteDir(string $dirname): bool
    {
        if (!file_exists($dirname)) {
            return false;
        }

        if (is_dir($dirname)) {
            $dir_handle = opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file)) {
                    unlink($dirname."/".$file);
                } else {
                    self::deleteDir($dirname.'/'.$file);
                }
            }
        }
        closedir($dir_handle);
        rmdir($dirname);

        return true;
    }

    /**
     * Update backend
     *
     * @return bool
     */
    protected static function updateBackend(): bool
    {
        foreach (self::getTreoModules() as $moduleId => $moduleKey) {
            // prepare params
            $source = self::VENDOR."/".self::TREODIR."/{$moduleKey}/application/Espo/Modules/{$moduleId}/";
            $dest   = "application/Espo/Modules/{$moduleId}/";

            // delete dir
            self::deleteDir($dest);

            // copy dir
            self::copyDir($source, $dest);
        }

        return true;
    }

    /**
     * Update frontend
     *
     * @return bool
     */
    protected static function updateFrontend(): bool
    {
        foreach (self::getTreoModules() as $moduleId => $moduleKey) {
            // module path
            $modulePath = self::VENDOR."/".self::TREODIR."/{$moduleKey}/client/modules/";

            if (file_exists($modulePath) && is_dir($modulePath)) {
                foreach (scandir($modulePath) as $module) {
                    if (!in_array($module, self::SKIP)) {
                        // prepare params
                        $source = "{$modulePath}{$module}/";
                        $dest   = "client/modules/{$module}/";

                        // delete dir
                        self::deleteDir($dest);

                        // copy dir
                        self::copyDir($source, $dest);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Echo success message
     *
     * @param string $message
     *
     * @return void
     */
    protected static function echoSuccess(string $message)
    {
        echo "\033[1;32m{$message}\033[0m".PHP_EOL;
    }

    /**
     * Echo error message
     *
     * @param string $message
     *
     * @return void
     */
    protected static function echoError(string $message)
    {
        echo "\033[1;37m\033[41m{$message}\033[0m".PHP_EOL;
    }
}
