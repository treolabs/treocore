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

Espo.define('treo-core:views/header', 'class-replace!treo-core:views/header', function (Dep) {

    return Dep.extend({

        template: 'treo-core:header',

        overviewFilters: [
            {
                name: 'fieldsFilter',
                view: 'treo-core:views/fields/overview-fields-filter'
            },
            {
                name: 'localesFilter',
                view: 'treo-core:views/fields/overview-locales-filter'
            }
        ],

        events: _.extend({
            'click a:not([data-action])': function(e){
                let path = e.currentTarget.getAttribute("href");
                e.preventDefault();
                this.getRouter().checkConfirmLeaveOut(function () {
                    this.getRouter().navigate(path, {trigger: true});
                }.bind(this), this, false);
            }
        }, Dep.prototype.events),

        setup() {
            Dep.prototype.setup.call(this);

            if (this.model && !this.model.isNew() && this.getMetadata().get(['scopes', this.scope, 'advancedFilters'])) {
                this.createOverviewFilters();
            }
        },

        createOverviewFilters() {
            this.overviewFilters = this.filterOverviewFilters();

            (this.overviewFilters || []).forEach(filter => {
                this.createView(filter.name, filter.view, {
                    el: `${this.options.el} .field[data-name="${filter.name}"]`,
                    model: this.model,
                    name: filter.name,
                    storageKey: 'overview-filters',
                    modelKey: 'advancedEntityView'
                }, view => view.render());
            });
        },

        filterOverviewFilters() {
            return (this.overviewFilters || []).filter(filter => {
                if (filter.name === 'localesFilter') {
                    return this.getConfig().get('isMultilangActive') && (this.getConfig().get('inputLanguageList') || []).length
                }
                return true;
            });
        }

    });
});

