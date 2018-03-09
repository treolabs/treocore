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

Espo.define('pim:views/record/search', 'views/record/search',
    Dep => Dep.extend({

        template: 'pim:record/search',

        hiddenBoolFilterList: [],

        boolFilterData: [],

        setup() {
            this.hiddenBoolFilterList = this.options.hiddenBoolFilterList || this.hiddenBoolFilterList;
            this.boolFilterData = this.options.boolFilterData || this.boolFilterData;

            Dep.prototype.setup.call(this);
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.boolFilterListLength = 0;
            data.boolFilterListComplex = data.boolFilterList.map(item => {
                let includes = this.hiddenBoolFilterList.includes(item);
                if (!includes) {
                    data.boolFilterListLength++;
                }
                return {name: item, hidden: includes};
            });
            return data;
        },

        manageBoolFilters() {
            (this.boolFilterList || []).forEach(item => {
                if (this.bool[item] && !this.hiddenBoolFilterList.includes(item)) {
                    this.currentFilterLabelList.push(this.translate(item, 'boolFilters', this.entityType));
                }
            });
        },

        updateCollection() {
            this.collection.reset();
            this.notify('Please wait...');
            this.listenTo(this.collection, 'sync', function () {
                this.notify(false);
            }.bind(this));
            let where = this.searchManager.getWhere();
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
            this.collection.fetch();
        },

    })
);
