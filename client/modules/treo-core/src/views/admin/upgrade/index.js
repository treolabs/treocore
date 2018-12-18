/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
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

Espo.define('treo-core:views/admin/upgrade/index', 'class-replace!treo-core:views/admin/upgrade/index', function (Dep) {

    return Dep.extend({

        template: 'treo-core:admin/upgrade/index',

        versionList: [],

        events: _.extend({
            'click .action': function (e) {
                var $el = $(e.currentTarget);
                var action = $el.data('action');
                var method = 'action' + Espo.Utils.upperCaseFirst(action);
                if (typeof this[method] == 'function') {
                    var data = $el.data();
                    this[method](data, e);
                    e.preventDefault();
                }
            }
        }, Dep.prototype.events),

        data: function () {
            return {
                hideLoader: !this.upgradingInProgress,
                availableVersions: !!this.versions,
                systemVersion: this.systemVersion
            };
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.waitForView('list');
            this.getConfig().fetch({
                success: () => {
                    this.upgradingInProgress = this.getConfig().get('isSystemUpdating');
                    this.systemVersion = this.getConfig().get('version');
                    this.ajaxGetRequest('TreoUpgrade/versions')
                        .then(response => {
                            this.versions = (response || []).length;
                            this.createList(response);
                        });
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

            if (this.upgradingInProgress) {
                this.initConfigCheck(true);
            }
            this.showCurrentStatus();
        },

        showCurrentStatus() {
            let el = this.$el.find('.current-status > span');
            let type, text;
            if (this.upgradingInProgress) {
                type = 'text-success';
                text = this.translate('upgradeInProgress', 'messages', 'Admin');
            } else if (this.versions) {
                type = '';
                text = this.translate('Current version', 'labels', 'Global') + ': ' + this.systemVersion;
            } else {
                type = 'text-success';
                text = this.translate('systemAlreadyUpgraded', 'messages', 'Admin');
            }
            el.removeClass();
            el.addClass('current-status ' + type);
            el.text(text);
        },

        initConfigCheck(skipInitRun) {
            let configCheck = () => {
                this.getConfig().fetch({
                    success: function (config) {
                        this.upgradingInProgress = !!config.get('isSystemUpdating');
                        if (!this.upgradingInProgress) {
                            this.getUser().fetch().then(() => {
                                window.clearInterval(this.configCheckInterval);
                                this.configCheckInterval = null;
                                this.notify(this.translate('upgradeFailed', 'messages', 'Admin'), 'danger');
                                this.reRender();
                            });
                        }
                        this.collection.trigger('disableUpgrading', this.upgradingInProgress);
                        this.loaderShow();
                        this.showCurrentStatus();
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
            this.upgradingInProgress ? loader.removeClass('hidden') : loader.addClass('hidden');
        },

        createList(data) {
            this.getCollectionFactory().create('Versions', collection => {
                this.collection = collection;
                data = (data || []).reverse();
                this.collection.add(data.map(item => {
                    item.id = item.version;
                    return item;
                }));

                this.createView('list', 'views/record/list', {
                    el: `${this.options.el} .list-container`,
                    collection: this.collection,
                    type: 'list',
                    listLayout: [
                        {
                            type: 'varchar',
                            name: 'version',
                            notSortable: true,
                            customLabel: this.translate('version', 'labels', 'ModuleManager'),
                            width: '30'
                        },
                        {
                            type: 'text',
                            name: 'description',
                            notSortable: true,
                            customLabel: this.translate('description', 'fields'),
                            view: 'treo-core:views/fields/varchar-html'
                        }
                    ],
                    searchManager: false,
                    selectable: false,
                    checkboxes: false,
                    massActionsDisabled: true,
                    checkAllResultDisabled: false,
                    buttonsDisabled: true,
                    paginationEnabled: false,
                    showCount: false,
                    showMore: false,
                    rowActionsView: 'treo-core:views/admin/upgrade/record/row-actions/upgrade-action'
                }, view => {
                    view.listenToOnce(view, 'after:render', () => {
                        this.collection.trigger('disableUpgrading', this.upgradingInProgress);
                    });
                });
            });
        },

        actionUpgradeNow(data) {
            if (!this.upgradingInProgress) {
                this.notify('Loading...');
                this.collection.trigger('disableUpgrading', true);

                let dataToUpgrade = {};
                if (data && data.id) {
                    dataToUpgrade.version = data.id;
                }
                this.ajaxPostRequest('TreoUpgrade/upgrade', dataToUpgrade).then(response => {
                    if (response) {
                        this.notify(this.translate('upgradeStarted', 'messages', 'Admin'), 'success');
                    } else {
                        this.notify(this.translate('upgradeInProgress', 'messages', 'Admin'), 'danger');
                    }
                    this.initConfigCheck();
                });
            }
        }
    });
});