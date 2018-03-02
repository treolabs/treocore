/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
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

Espo.define('treo-crm:views/site/navbar', 'class-replace!treo-crm:views/site/navbar', function (Dep) {

    return Dep.extend({

        init() {
            Dep.prototype.init.call(this);

            this.listenToOnce(this, 'after:render', () => {
                this.initProgressBadge();
            });
        },

        initProgressBadge() {
            this.$el.find('.notifications-badge-container').before('<li class="dropdown progress-badge-container"></li>');
            this.createView('progressBadge', 'treo-crm:views/progress-manager/badge', {
                el: `${this.options.el} .progress-badge-container`
            }, view => {
                view.render();
            });
        },

        getMenuDefs: function () {
            var menuDefs = [
                {
                    link: '#Preferences',
                    label: this.getLanguage().translate('Preferences')
                }
            ];

            if (!this.getConfig().get('actionHistoryDisabled')) {
                menuDefs.push({
                    divider: true
                });
                menuDefs.push({
                    action: 'showLastViewed',
                    link: '#LastViewed',
                    label: this.getLanguage().translate('LastViewed', 'scopeNamesPlural')
                });
            }

            menuDefs = menuDefs.concat([
                {
                    divider: true
                },
                {
                    link: '#clearCache',
                    label: this.getLanguage().translate('Clear Local Cache')
                },
                {
                    divider: true
                },
                {
                    link: '#logout',
                    label: this.getLanguage().translate('Log Out')
                }
            ]);

            if (this.getUser().isAdmin()) {
                menuDefs.unshift({
                    link: '#Admin',
                    label: this.getLanguage().translate('Administration')
                });
            }
            return menuDefs;
        },

        setupTabDefsList: function () {
            var tabDefsList = [];
            var moreIsMet = false;;
            this.tabList.forEach(function (tab, i) {
                if (tab === '_delimiter_') {
                    moreIsMet = true;
                    return;
                }
                if (typeof tab === 'object') {
                    return;
                }
                var label = this.getLanguage().translate(tab, 'scopeNamesPlural');
                var o = {
                    link: '#' + tab,
                    label: label,
                    shortLabel: label.substr(0, 2),
                    name: tab,
                    isInMore: moreIsMet
                };
                tabDefsList.push(o);
            }, this);
            this.tabDefsList = tabDefsList;
        },

    });

});


