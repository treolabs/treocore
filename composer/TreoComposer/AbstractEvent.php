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
     * Constants
     */
    const SKIP = ['.', '..'];

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
     * @param string $path
     *
     * @return bool
     */
    protected static function updateBackend(string $path): bool
    {
        foreach (scandir($path) as $row) {
            if (!in_array($row, self::SKIP)) {
                $modulePath = "{$path}/{$row}/application/Espo/Modules/";
                if (file_exists($modulePath) && is_dir($modulePath)) {
                    foreach (scandir($modulePath) as $module) {
                        if (!in_array($module, self::SKIP)) {
                            // prepare params
                            $source = "{$modulePath}{$module}/";
                            $dest   = "application/Espo/Modules/{$module}/";

                            // delete dir
                            self::deleteDir($dest);

                            // copy dir
                            self::copyDir($source, $dest);
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update frontend
     *
     * @param string $path
     *
     * @return bool
     */
    protected static function updateFrontend(string $path): bool
    {
        foreach (scandir($path) as $row) {
            if (!in_array($row, self::SKIP)) {
                $modulePath = "{$path}/{$row}/client/modules/";
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
