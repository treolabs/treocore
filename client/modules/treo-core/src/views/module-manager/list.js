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

        data() {
            return {
                disabledRunUpdateButton: this.getConfig().get('isSystemUpdating'),
                hideLoader: !this.getConfig().get('isSystemUpdating')
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.getConfig().fetch({
                success: () => {
                    this.wait(false);
                }
            });

            this.listenToOnce(this, 'remove', () => {
                if (this.configCheckInterval) {
                    window.clearInterval(this.configCheckInterval);
                    this.configCheckInterval = null;
                }
            });
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.getConfig().get('isSystemUpdating')) {
                this.initConfigCheck(true);
            }
        },

        loadList() {
            this.loadInstalledModulesList();
            this.loadAvailableModulesList();
            this.loadLogList();
        },

        loadLogList() {
            this.createView('logList', 'treo-core:views/module-manager/record/panels/log', {
                el: `${this.options.el} .log-list-container`
            }, view => {
                view.render();
                this.listenTo(this, 'composerUpdate:started composerUpdate:failed', () => {
                    view.actionRefresh();
                });
            })
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
                        this.listenTo(view, 'after:render', () => {
                            let rows = view.nestedViews || {};
                            let showCancelAction = false;
                            collection.each(currentModel => {
                                let status = currentModel.get('status');
                                if (status) {
                                    showCancelAction = true;
                                    rows[currentModel.id].$el.addClass(`${status}-module-row`);
                                }
                            });
                            this.toggleActionButton('cancelUpdate', showCancelAction);
                        });
                        this.listenToOnce(view, 'after:render', () => {
                            this.installedCollection.trigger('disableActions', this.getConfig().get('isSystemUpdating'));
                        });
                        view.render();
                    });
                });

                collection.fetch();
            });
        },

        loadAvailableModulesList() {
            this.getCollectionFactory().create('ModuleManager', collection => {
                this.availableCollection = collection;
                collection.maxSize = 200;
                collection.url = 'Store/action/list';

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
                        this.listenToOnce(view, 'after:render', () => {
                            this.availableCollection.trigger('disableActions', this.getConfig().get('isSystemUpdating'));
                        });
                        view.render();
                    });
                });

                collection.fetch();
            });
        },

        getHeader() {
            return '<a href="#Admin">' + this.translate('Administration') + "</a> &rsaquo; " + this.getLanguage().translate('Module Manager', 'labels', 'Admin');
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
            if (this.getConfig().get('isSystemUpdating')) {
                this.notify(this.translate('updateInProgress', 'labels', 'ModuleManager'), 'warning');
                return;
            }

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
                            if (data.mode === 'install') {
                                this.availableCollection.fetch();
                            }
                            this.installedCollection.fetch();
                        }
                    }).always(() => {
                        this.actionsInProgress--;
                    });
                });
            });
        },

        actionRemoveModule(data) {
            if (this.getConfig().get('isSystemUpdating')) {
                this.notify(this.translate('updateInProgress', 'labels', 'ModuleManager'), 'warning');
                return;
            }

            if (!data.id) {
                return;
            }

            this.actionsInProgress++;
            this.notify(this.translate('settingModuleForRemoving', 'labels', 'ModuleManager'));
            this.ajaxRequest('ModuleManager/deleteModule', 'DELETE', JSON.stringify({id: data.id})).then(response => {
                if (response) {
                    this.notify(this.translate('settedModuleForRemoving', 'labels', 'ModuleManager'), 'success');
                    this.installedCollection.fetch();
                }
            }).always(() => {
                this.actionsInProgress--;
            });
        },

        actionCancelModule(data) {
            if (this.getConfig().get('isSystemUpdating')) {
                this.notify(this.translate('updateInProgress', 'labels', 'ModuleManager'), 'warning');
                return;
            }

            if (!data.id || !data.status) {
                return;
            }

            let beforeSaveLabel;
            let afterSaveLabel;
            if (data.status = 'install') {
                beforeSaveLabel = 'cancelingModuleUpdate';
                afterSaveLabel = 'canceledModuleUpdate';
            } else {
                beforeSaveLabel = 'cancelingModuleInstall';
                afterSaveLabel = 'canceledModuleInstall';
            }

            this.actionsInProgress++;
            this.notify(this.translate(beforeSaveLabel, 'labels', 'ModuleManager'));
            this.ajaxPostRequest('ModuleManager/cancel', {id: data.id}).then(response => {
                if (response) {
                    this.notify(this.translate(afterSaveLabel, 'labels', 'ModuleManager'), 'success');
                    if (data.status = 'install') {
                        this.availableCollection.fetch();
                    }
                    this.installedCollection.fetch();
                }
            }).always(() => {
                this.actionsInProgress--;
            });
        },

        actionRunUpdate() {
            if (this.actionsInProgress) {
                this.notify(this.translate('anotherActionInProgress', 'labels', 'ModuleManager'), 'warning');
                return;
            }

            this.confirm({
                message: this.translate('confirmRun', 'labels', 'ModuleManager'),
                confirmText: this.translate('Run Update', 'labels', 'ModuleManager')
            }, () => {
                this.actionsInProgress++;
                this.notify(this.translate('updating', 'labels', 'ModuleManager'));
                this.ajaxPostRequest('Composer/update', {}, {timeout: 180000}).then(response => {
                    if (response) {
                        this.notify(this.translate('updateStarted', 'labels', 'ModuleManager'), 'success');
                    } else {
                        this.notify(this.translate('updateInProgress', 'labels', 'ModuleManager'), 'danger');
                    }
                    this.initConfigCheck();
                }).always(() => {
                    this.actionsInProgress--;
                    this.trigger('composerUpdate:started');
                });
            });
        },

        actionCancelUpdate() {
            if (this.actionsInProgress) {
                this.notify(this.translate('anotherActionInProgress', 'labels', 'ModuleManager'), 'warning');
                return;
            }

            this.actionsInProgress++;
            this.notify(this.translate('canceling', 'labels', 'ModuleManager'));
            this.ajaxRequest('Composer/cancel', 'DELETE').then(response => {
                if (response) {
                    this.notify(this.translate('canceled', 'labels', 'ModuleManager'), 'success');
                    this.availableCollection.fetch();
                    this.installedCollection.fetch();
                }
            }).always(() => {
                this.actionsInProgress--;
            });
        },

        toggleActionButton(action, show) {
            let button = this.$el.find(`.detail-button-container button[data-action="${action}"]`);
            if (show) {
                button.show();
            } else {
                button.hide();
            }
        },

        disableActionButton(action, disabled) {
            let button = this.$el.find(`.detail-button-container button[data-action="${action}"]`);
            button.prop('disabled', disabled);
        },

        reloadPage(timeout) {
            if (timeout && typeof timeout === 'number') {
                setTimeout(function () {
                    window.location.reload(true);
                }, timeout);
            } else {
                window.location.reload(true);
            }
        },

        initConfigCheck(skipInitRun) {
            let configCheck = () => {
                this.getConfig().fetch({
                    success: function (config) {
                        let isSystemUpdating = !!config.get('isSystemUpdating');
                        if (!isSystemUpdating) {
                            window.clearInterval(this.configCheckInterval);
                            this.configCheckInterval = null;
                            this.notify(this.translate('updateFailed', 'labels', 'ModuleManager'), 'danger');
                            this.trigger('composerUpdate:failed');
                        }
                        this.disableActionButton('runUpdate', isSystemUpdating);
                        this.disableActionButton('cancelUpdate', isSystemUpdating);

                        this.installedCollection.trigger('disableActions', isSystemUpdating);
                        this.availableCollection.trigger('disableActions', isSystemUpdating);

                        this.loaderShow();
                    }.bind(this)
                });
            };
            window.clearInterval(this.configCheckInterval);
            this.configCheckInterval = window.setInterval(configCheck, 10000);
            if (!skipInitRun) {
                configCheck();
            }
            this.loaderShow();
        },

        loaderShow() {
            let loader = this.$el.find('.loader');
            this.getConfig().get('isSystemUpdating') ? loader.removeClass('hidden') : loader.addClass('hidden');
        },

    })
);