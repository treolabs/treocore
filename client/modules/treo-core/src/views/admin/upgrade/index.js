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

Espo.define('treo-core:views/admin/upgrade/index', 'class-replace!treo-core:views/admin/upgrade/index', function (Dep) {

    return Dep.extend({

        template: 'treo-core:admin/upgrade/index',

        latestVersion: null,

        data: function () {
            return {
                disableUpgrade: this.upgradingInProgress || !this.latestVersion,
                latestVersion: this.latestVersion,
                systemVersion: this.systemVersion
            };
        },

        events: {
            'click button[data-action="upgradeSystem"]': function () {
                this.upgradeSystem();
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.getConfig().fetch({
                success: () => {
                    this.upgradingInProgress = this.getConfig().get('isSystemUpdating');
                    this.systemVersion = this.getConfig().get('version');
                    this.ajaxGetRequest('/TreoUpgrade/availableVersion')
                    .then(response => {
                        this.latestVersion = response.version;
                    })
                    .always(() => {
                        this.wait(false)
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

        upgradeSystem() {
            this.disableUpgrade(true);
            this.ajaxPostRequest('/TreoUpgrade/upgrade').then(response => {
                if (response) {
                    this.notify(this.translate('upgradeStarted', 'messages', 'Admin'), 'success');
                } else {
                    this.notify(this.translate('upgradeInProgress', 'messages', 'Admin'), 'danger');
                }
                this.initConfigCheck();
            });
        },

        showCurrentStatus() {
            let el = this.$el.find('.current-status');
            let type, text;
            if (this.upgradingInProgress) {
                type = 'text-success';
                text = this.translate('upgradeInProgress', 'messages', 'Admin');
            } else if (this.latestVersion) {
                type = 'text-danger';
                text = this.translate('Current version', 'labels', 'Global') + ': ' + this.systemVersion;
            } else {
                type = 'text-success';
                text = this.translate('systemAlreadyUpgraded', 'messages', 'Admin');
            }
            el.removeClass();
            el.addClass('current-status ' + type);
            el.text(text);
        },

        disableUpgrade(property) {
            let button = this.$el.find('button[data-action="upgradeSystem"]');
            button.prop('disabled', property);
            if (!property) {
                button.removeClass('hidden');
            }
        },

        initConfigCheck(skipInitRun) {
            let configCheck = () => {
                this.getConfig().fetch({
                    success: function (config) {
                        this.upgradingInProgress = !!config.get('isSystemUpdating');
                        if (!this.upgradingInProgress) {
                            window.clearInterval(this.configCheckInterval);
                            this.configCheckInterval = null;
                            this.showCurrentStatus();
                            this.disableUpgrade(false);
                            this.notify(this.translate('upgradeFailed', 'messages', 'Admin'), 'danger');
                        }
                    }.bind(this)
                });
            };
            window.clearInterval(this.configCheckInterval);
            this.configCheckInterval = window.setInterval(configCheck, 10000);
            if (!skipInitRun) {
                configCheck();
            }
        },

    });
});