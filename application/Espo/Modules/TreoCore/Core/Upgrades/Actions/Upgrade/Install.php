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

namespace Espo\Modules\TreoCore\Core\Upgrades\Actions\Upgrade;

use Espo\Core\Upgrades\Actions\Upgrade\Install as EspoInstall;
use Espo\Modules\TreoCore\Services\Composer;

class Install extends EspoInstall
{
    /**
     * Main installation process
     *
     * @param  string $processId Upgrade/Extension ID, gotten in upload stage
     *
     * @return bool
     */
    public function run($data)
    {
        $processId = $data['id'];

        $GLOBALS['log']->debug('Installation process [' . $processId . ']: start run.');

        if (empty($processId)) {
            throw new Error('Installation package ID was not specified.');
        }

        $this->setProcessId($processId);

        $this->initialize();

        /** check if an archive is unzipped, if no then unzip */
        $packagePath = $this->getPackagePath();
        if (!file_exists($packagePath)) {
            $this->unzipArchive();
            $this->isAcceptable();
        }

        //check permissions copied and deleted files
        $this->checkIsWritable();

        $this->beforeRunAction();

        $this->backupExistingFiles();

        //beforeInstallFiles
        if (!$this->copyFiles('before')) {
            $this->throwErrorAndRemovePackage('Cannot copy beforeInstall files.');
        }

        /* run before install script */
        if (!isset($data['skipBeforeScript']) || !$data['skipBeforeScript']) {
            $this->runScript('before');
        }

        /* remove files defined in a manifest "deleteBeforeCopy" */
        $this->deleteFiles('deleteBeforeCopy', true);

        /* copy files from directory "Files" to EspoCRM files */
        if (!$this->copyFiles()) {
            $this->throwErrorAndRemovePackage('Cannot copy files.');
        }

        /* remove files defined in a manifest */
        $this->deleteFiles('delete', true);

        $this->deleteFiles('vendor');
        $this->copyFiles('vendor');

        if (!isset($data['skipSystemRebuild']) || !$data['skipSystemRebuild']) {
            if (!$this->systemRebuild()) {
                $this->throwErrorAndRemovePackage('Error occurred while EspoCRM rebuild.');
            }
        }

        //afterInstallFiles
        if (!$this->copyFiles('after')) {
            $this->throwErrorAndRemovePackage('Cannot copy afterInstall files.');
        }

        /* run before install script */
        if (!isset($data['skipAfterScript']) || !$data['skipAfterScript']) {
            $this->runScript('after');
        }

        $this->afterRunAction();

        // call composer
        $composerData = $this->getComposerService()->runUpdate();
        if ($composerData['status'] != 0) {
            $this->throwErrorAndRemovePackage('Composer requirements error! Log:' . $composerData['output']);
        }

        $this->clearCache();

        /* delete unziped files */
        $this->deletePackageFiles();

        $this->finalize();

        $GLOBALS['log']->debug('Installation process [' . $processId . ']: end run.');
    }

    /**
     * Get Composer service
     *
     * @return Composer
     */
    protected function getComposerService(): Composer
    {
        return $this->getContainer()->get('serviceFactory')->create('Composer');
    }
}
