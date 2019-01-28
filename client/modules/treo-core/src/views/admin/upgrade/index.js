/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

        inProgress: false,

        log: null,

        messageText: null,

        messageType: null,

        validationSuccessful: false,

        versionList: [],

        data: function () {
            return {
                systemVersion: this.systemVersion,
                alreadyUpdated: !(this.versionList || []).length
            };
        },

        events: {
            'click button[data-action="validateSystem"]': function () {
                this.actionUpgradeSystem('validateSystem');
            },
            'click button[data-action="upgradeSystem"]': function () {
                this.actionUpgradeSystem('upgradeSystem');
            },
            'click a[data-action="showLog"]': function () {
                this.actionShowLog();
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.getModelFactory().create(null, model => {
                this.model = model;

                this.getConfig().fetch({
                    success: () => {
                        this.systemVersion = this.getConfig().get('version');
                        this.ajaxGetRequest('TreoUpgrade/versions')
                            .then(response => {
                                this.versionList = (response || []).map(item => item.version).reverse();
                                if (this.versionList.length) {
                                    this.model.set({versionToUpgrade: this.versionList[0]});
                                    this.createField();
                                }
                            })
                            .always(() => {
                                this.wait(false);
                            });
                    }
                });
            });

            this.listenToOnce(this, 'remove', () => {
                if (this.logCheckInterval) {
                    window.clearInterval(this.logCheckInterval);
                    this.logCheckInterval = null;
                }
            });

            this.listenTo(this.model, 'change:versionToUpgrade', () => {
                if (this.validationSuccessful) {
                    this.reRender();
                }
            });
        },

        createField() {
            this.createView('versionToUpgrade', 'views/fields/enum', {
                model: this.model,
                el: `${this.options.el} .field[data-name="versionToUpgrade"]`,
                defs: {
                    name: 'versionToUpgrade',
                    params: {
                        options: this.versionList
                    }
                },
                mode: 'edit'
            });
        },

        actionUpgradeSystem(actionName) {
            let url;
            let logFile;
            if (actionName === 'validateSystem') {
                url = 'TreoUpgrade/action/createUpdateLog';
                this.messageText = this.translate('validationStarted', 'messages', 'Admin');
                logFile = 'composer-validate.log';
            } else if (actionName === 'upgradeSystem') {
                if (!this.validationSuccessful) {
                    this.actionFinished(actionName);
                    return;
                }
                url = 'TreoUpgrade/action/Upgrade';
                this.messageText = this.translate('upgradeStarted', 'messages', 'Admin');
                logFile = 'composer.log';
            }

            this.actionStarted();

            this.ajaxPostRequest(url, {version: this.model.get('versionToUpgrade')}).then(response => {
                this.messageType = 'success';
                setTimeout(() => {
                    this.initLogCheck(actionName, logFile);
                    this.showCurrentStatus(this.messageText, this.messageType);
                }, 2000);
            }, error => {
                this.messageText = this.getLanguage().translate('Error occurred');
                this.messageType = 'danger';
                this.showCurrentStatus(this.messageText, this.messageType);
            });
        },

        initLogCheck(actionName, file) {
            let logCheck = () => {
                $.ajax({
                    type: 'GET',
                    dataType: 'text',
                    url: `../../data/${file}`,
                    cache: false,
                    success: response => {
                        this.log = response;
                        this.checkLog(actionName);
                    },
                    error: xhr => {
                        this.notify('Error occurred', 'error');
                        window.clearInterval(this.logCheckInterval);
                        this.reRender();
                    }
                });
            };
            window.clearInterval(this.logCheckInterval);
            this.logCheckInterval = window.setInterval(logCheck, 1000);
            logCheck();
        },

        checkLog(actionName) {
            let pos = this.log.indexOf('{{finished}}');
            if (pos > -1) {
                window.clearInterval(this.logCheckInterval);
                this.log = this.log.slice(0, pos);

                if (this.log.indexOf('Problem 1') > -1 || this.log.indexOf('exceeded the timeout') > -1) {
                    this.messageType = 'danger';
                    if (actionName === 'validateSystem') {
                        this.validationSuccessful = false;
                        this.messageText = this.translate('validationFailed', 'messages', 'Admin');
                    } else if (actionName === 'upgradeSystem') {
                        this.messageText = this.translate('upgradeFailed', 'messages', 'Admin');
                    }
                } else {
                    this.messageType = 'success';
                    if (actionName === 'validateSystem') {
                        this.validationSuccessful = true;
                        this.messageText = this.translate('validationSuccessful', 'messages', 'Admin');
                    } else if (actionName === 'upgradeSystem') {
                        this.messageText = this.translate('upgradeSuccessful', 'messages', 'Admin');
                        this.getConfig().fetch();
                    }
                }
                this.actionFinished(actionName);
                this.showCurrentStatus(this.messageText, this.messageType);
            }
            this.trigger('log-updated');
        },

        toggleButtonDisabling(action, property) {
            let button = this.$el.find(`button[data-action="${action}"]`);
            button.prop('disabled', property);
            if (!property) {
                button.removeClass('hidden');
            }
        },

        toggleButtonHiding(action, hide) {
            let button = this.$el.find(`button[data-action="${action}"]`);
            hide ? button.addClass('hidden') : button.removeClass('hidden');
        },

        actionStarted() {
            this.inProgress = true;
            let select = this.getView('versionToUpgrade');
            select.$element.prop('disabled', true);
            this.toggleButtonDisabling('validateSystem', true);
            this.toggleButtonDisabling('upgradeSystem', true);
            this.$el.find('.spinner').removeClass('hidden');
            this.$el.find('.progress-status').addClass('hidden');
        },

        actionFinished(actionName) {
            this.inProgress = false;
            let select = this.getView('versionToUpgrade');
            select.$element.prop('disabled', false);

            if (actionName === 'validateSystem') {
                if (this.validationSuccessful) {
                    this.toggleButtonHiding('validateSystem', true);
                    this.toggleButtonDisabling('upgradeSystem', false);
                } else {
                    this.toggleButtonDisabling('validateSystem', false);
                }
            } else if (actionName === 'upgradeSystem') {
                if (this.validationSuccessful) {
                    this.toggleButtonDisabling('upgradeSystem', false);
                } else {
                    this.toggleButtonDisabling('validateSystem', false);
                }
            }
            this.$el.find('.spinner').addClass('hidden');
        },

        showCurrentStatus(text, type) {
            text = text + ` (<a href="javascript:" class="action" data-action="showLog">${this.translate('log', 'labels', 'Admin')}</a>)`;
            let el = this.$el.find('.progress-status');
            el.removeClass();
            el.addClass('progress-status text-' + type);
            el.html(text);
        },

        actionShowLog() {
            this.createView('progress-log', 'treo-core:views/modals/progress-log', {
                progressData: this.getProgressData()
            }, view => {
                this.listenTo(this, 'log-updated', () => {
                    view.trigger('log-updated', this.getProgressData());
                });
                view.render()
            });
        },

        getProgressData() {
            return {
                log: this.log,
                inProgress: this.inProgress,
                messageText: this.messageText,
                messageType: this.messageType
            }
        },

    });
});