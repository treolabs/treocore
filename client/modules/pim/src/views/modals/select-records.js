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

Espo.define('pim:views/modals/select-records', ['views/modals/select-records', 'search-manager'],
    (Dep, SearchManager) => Dep.extend({

        listLayout: null,

        searchView: 'pim:views/record/search',

        boolFilterData: [],

        setup() {
            this.boolFilterData = this.options.boolFilterData || this.boolFilterData;

            this.listLayout = this.options.listLayout || this.listLayout;

            Dep.prototype.setup.call(this);
        },

        loadList() {
            let viewName = this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.listSelect') ||
                this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') ||
                'views/record/list';

            this.listenToOnce(this.collection, 'sync', function () {
                this.createView('list', viewName, {
                    collection: this.collection,
                    el: this.containerSelector + ' .list-container',
                    selectable: true,
                    checkboxes: this.multiple,
                    massActionsDisabled: true,
                    rowActionsView: false,
                    layoutName: this.listLayout || 'listSmall',
                    searchManager: this.searchManager,
                    checkAllResultDisabled: !this.massRelateEnabled,
                    buttonsDisabled: true
                }, function (list) {
                    list.once('select', function (model) {
                        this.trigger('select', model);
                        this.close();
                    }.bind(this));
                }.bind(this));

            }.bind(this));
        },

        loadSearch: function () {
            var searchManager = this.searchManager = new SearchManager(this.collection, 'listSelect', null, this.getDateTime());
            searchManager.emptyOnReset = true;
            if (this.filters) {
                searchManager.setAdvanced(this.filters);
            }

            var boolFilterList = this.boolFilterList || this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.boolFilterList');
            if (boolFilterList) {
                var d = {};
                boolFilterList.forEach(function (item) {
                    d[item] = true;
                });
                searchManager.setBool(d);
            }
            var primaryFilterName = this.primaryFilterName || this.getMetadata().get('clientDefs.' + this.scope + '.selectDefaultFilters.filter');
            if (primaryFilterName) {
                searchManager.setPrimary(primaryFilterName);
            }

            let where = searchManager.getWhere();
            where.forEach(item => {
                if (item.type === 'bool') {
                    let data = {};
                    item.value.forEach(elem => {
                        if (elem in this.boolFilterData) {
                            data[elem] = this.boolFilterData[elem];
                        }
                    });
                    item.data = data;
                }
            });
            this.collection.where = where;


            if (this.searchPanel) {
                let hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];
                let searchView = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.search`) || this.searchView;

                this.createView('search', searchView, {
                    collection: this.collection,
                    el: this.containerSelector + ' .search-container',
                    searchManager: searchManager,
                    disableSavePreset: true,
                    hiddenBoolFilterList: hiddenBoolFilterList,
                    boolFilterData: this.boolFilterData
                });
            }
        },

    })
);

