/*
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

Espo.define('treo-core:views/module-manager/list', 'views/list',
    Dep => Dep.extend({

        template: 'treo-core:module-manager/list',

        createButton: false,

        searchPanel: false,

        installedCollection: null,

        availableCollection: null,

        actionsInProgress: 0,

        loadList() {
            this.loadInstalledModulesList();
            this.loadAvailableModulesList();
        },

        loadInstalledModulesList() {
            this.getCollectionFactory().create('ModuleManager', collection => {
                this.installedCollection = collection;
                collection.maxSize = 200;
                collection.url = 'ModuleManager/list';

                this.listenToOnce(collection, 'sync', () => {
                    this.createView('list', 'views/record/list', {
                        collection: collection,
                        el: `${this.options.el} .list-container.modules-installed`,
                        type: 'list',
                        searchManager: false,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: false,
                        buttonsDisabled: false,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsView: 'treo-core:views/module-manager/record/row-actions/installed'
                    }, view => {
                        let rows = view.nestedViews || {};
                        for (let key in rows) {
                            view.listenTo(rows[key].model, `change:isActive`, model => {
                                this.notify('Saving...');
                                this.ajaxPutRequest(`ModuleManager/${model.get('id')}/updateActivation`)
                                .then(() => {
                                    this.notify(this.translate('successAndReload', 'labels', 'ModuleManager').replace('{value}', 2), 'success');
                                    for (let k in rows) {
                                        rows[k].getView('isActive').setMode('list');
                                    }
                                    this.getView('list').reRender();
                                    this.reloadPage(2000);
                                });
                            });
                        }
                        this.listenTo(view, 'after:render', () => {
                            let rows = view.nestedViews || {};
                            for (let key in rows) {
                                let setEditMode;
                                if (rows[key].model.get('isActive')) {
                                    setEditMode = collection.every(model => !model.get('isActive') || !(model.get('required') || []).includes(key)) && !rows[key].model.get('isSystem');
                                } else {
                                    setEditMode = (collection.get(key).get('required') || []).every(item => {
                                        let model = collection.get(item);
                                        return model && model.get('isActive');
                                    });
                                }
                                if (setEditMode) {
                                    rows[key].getView('isActive').setMode('edit');
                                    rows[key].getView('isActive').reRender();
                                }
                            }
                        });
                        view.render();
                        //todo: set color for rows
                    });
                });

                collection.fetch();
            });
        },

        loadAvailableModulesList() {
            this.getCollectionFactory().create('ModuleManager', collection => {
                this.availableCollection = collection;
                collection.maxSize = 200;
                collection.url = 'ModuleManager/availableModulesList';

                this.listenToOnce(collection, 'sync', () => {
                    this.createView('listAvailable', 'views/record/list', {
                        collection: collection,
                        el: `${this.options.el} .list-container.modules-available`,
                        type: 'list',
                        layoutName: 'availableModulesList',
                        searchManager: false,
                        selectable: false,
                        checkboxes: false,
                        massActionsDisabled: true,
                        checkAllResultDisabled: false,
                        buttonsDisabled: false,
                        paginationEnabled: false,
                        showCount: false,
                        showMore: false,
                        rowActionsView: 'treo-core:views/module-manager/record/row-actions/available'
                    }, view => {
                        view.render();
                        //todo: set color for rows
                    });
                });

                collection.fetch();
            });
        },

        getHeader() {
            return '<a href="#Admin">' + this.translate('Administration') + "</a> » " + this.getLanguage().translate('Module Manager', 'labels', 'Admin');
        },

        updatePageTitle() {
            this.setPageTitle(this.getLanguage().translate('Module Manager', 'labels', 'Admin'));
        },

        actionRefresh(data) {
            if (data.collection === 'installed') {
                this.installedCollection.fetch();
            } else if (data.collection === 'available') {
                this.availableCollection.fetch();
            }
        },

        actionInstallModule(data) {
            if (!data.id || !data.mode) {
                return;
            }

            let currentModel;
            let viewName;
            let beforeSaveLabel;
            let afterSaveLabel;
            let apiUrl;
            let requestType;
            if (data.mode === 'install') {
                currentModel = this.availableCollection.get(data.id);
                viewName = 'treo-core:views/module-manager/modals/install';
                beforeSaveLabel = 'settingModuleForInstalling';
                afterSaveLabel = 'settedModuleForInstalling';
                apiUrl = 'ModuleManager/installModule';
                requestType = 'POST';
            } else {
                currentModel = this.installedCollection.get(data.id);
                viewName = 'treo-core:views/module-manager/modals/update';
                beforeSaveLabel = 'settingModuleForUpdating';
                afterSaveLabel = 'settedModuleForUpdating';
                apiUrl = 'ModuleManager/updateModule';
                requestType = 'PUT';
            }

            this.createView('installModal', viewName, {
                currentModel: currentModel
            }, view => {
                view.render();
                this.listenTo(view, 'save', saveData => {
                    this.actionsInProgress++;
                    this.notify(this.translate(beforeSaveLabel, 'labels', 'ModuleManager'));
                    this.ajaxRequest(apiUrl, requestType, JSON.stringify(saveData), {timeout: 180000}).then(response => {
                        if (response) {
                            this.notify(this.translate(afterSaveLabel, 'labels', 'ModuleManager'), 'success');
                            this.actionsInProgress--;
                            currentModel.set({settingVersion: saveData.version});
                            //todo: set status in model and set color
                        }
                    });
                });
            });
        },

        actionRemoveModule(data) {
            if (!data.id) {
                return;
            }

            this.actionsInProgress++;
            this.notify(this.translate('settingModuleForRemoving', 'labels', 'ModuleManager'));
            this.ajaxRequest('ModuleManager/deleteModule', 'DELETE', JSON.stringify({id: data.id})).then(response => {
                if (response) {
                    this.notify(this.translate('settedModuleForRemoving', 'labels', 'ModuleManager'), 'success');
                    this.actionsInProgress--;
                    //todo: set status in model and set color
                }
            });
        },

        actionRunUpdate() {
            if (this.actionsInProgress) {
                this.notify(this.translate('anotherActionInProgress', 'labels', 'ModuleManager'));
                return;
            }

            this.actionsInProgress++;
            this.notify(this.translate('updating', 'labels', 'ModuleManager'));
            this.ajaxPostRequest('Composer/update', {}, {timeout: 180000}).then(response => {
                if (response.status === 0) {
                    this.notify(this.translate('updated', 'labels', 'ModuleManager').replace('{value}', 2), 'success');
                    this.reloadPage(2000);
                } else {
                    this.notify(this.translate('failed', 'labels', 'ModuleManager'), 'danger');
                    this.actionsInProgress--;
                }
            }).fail(() => this.actionsInProgress--);;
        },

        reloadPage(timeout) {
            if (timeout && typeof timeout === 'number') {
                setTimeout(function () {
                    window.location.reload(true);
                }, timeout);
            } else {
                window.location.reload(true);
            }
        }

    })
);