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

Espo.define('treo-core:views/progress-manager/badge', 'view',
    Dep => Dep.extend({

        interval: null,

        isPanelShowed: false,

        template: 'treo-core:progress-manager/badge',

        progressCheckInterval: 2,

        events: {
            'click a[data-action="showProgress"]': function (e) {
                this.showProgress();
            },
        },

        setup() {
            this.progressCheckInterval = this.getConfig().get('progressCheckInterval') || this.progressCheckInterval;

            this.listenToOnce(this, 'after:render', () => {
                this.initProgressShowInterval();
            });

            this.listenToOnce(this, 'remove', () => {
                if (this.interval) {
                    window.clearInterval(this.interval);
                }
            });
        },

        initProgressShowInterval() {
            this.interval = window.setInterval(() => {
                if (!this.isPanelShowed) {
                    this.ajaxGetRequest('ProgressManager/isShowPopup', {})
                        .then(response => {
                            if (response && !this.isPanelShowed) {
                                this.showProgress();
                            }
                        });
                } else if (this.hasView('panel') && !this.isProgressModalShowed()) {
                    this.getView('panel').reloadList();
                }
            }, 1000 * this.progressCheckInterval);
        },

        showProgress() {
            this.closeProgress();
            this.isPanelShowed = true;

            this.createView('panel', 'treo-core:views/progress-manager/panel', {
                el: `${this.options.el} .progress-panel-container`
            }, function (view) {
                view.render();
            }.bind(this));

            // set popup as showed
            this.ajaxPostRequest('ProgressManager/popupShowed', {"userId": this.getUser().get('id')});

            $(document).on('mouseup.progress', function (e) {
                let container = this.$el.find('.progress-panel-container');
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    this.closeProgress();
                }
            }.bind(this));
        },

        closeProgress() {
            this.isPanelShowed = false;

            if (this.hasView('panel')) {
                this.getView('panel').remove();
            };

            $(document).off('mouseup.progress');
        },

        isProgressModalShowed() {
            return $(document).find('.progress-modal').length;
        }

    })
);
