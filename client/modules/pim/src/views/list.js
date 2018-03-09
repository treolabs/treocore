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

Espo.define('pim:views/list', 'views/list',
    Dep => Dep.extend({

        searchView: 'pim:views/record/search',

        setup() {
            this.quickCreate = this.getMetadata().get(`clientDefs.${this.scope}.quickCreate`);

            Dep.prototype.setup.call(this);
        },

        setupSearchPanel() {
            let hiddenBoolFilterList = this.getMetadata().get(`clientDefs.${this.scope}.hiddenBoolFilterList`) || [];
            let searchView = this.getMetadata().get(`clientDefs.${this.scope}.recordViews.search`) || this.searchView;

            this.createView('search', searchView, {
                collection: this.collection,
                el: '#main > .search-container',
                searchManager: this.searchManager,
                scope: this.scope,
                hiddenBoolFilterList: hiddenBoolFilterList,
            }, function (view) {
                this.listenTo(view, 'reset', function () {
                    this.collection.sortBy = this.defaultSortBy;
                    this.collection.asc = this.defaultAsc;
                    this.getStorage().clear('listSorting', this.collection.name);
                }, this);
            }.bind(this));
        },

        navigateToEdit(id) {
            let router = this.getRouter();

            let url = `#${this.scope}/view/${id}`;

            router.dispatch(this.scope, 'view', {
                id: id,
                mode: 'edit',
                setEditMode: true
            });
            router.navigate(url, {trigger: false});
        },

        actionQuickCreate() {
            let options = _.extend({
                scope: this.scope,
                attributes: this.getCreateAttributes() || {}
            }, this.getMetadata().get(`clientDefs.${this.scope}.quickCreateOptions`) || {})

            this.notify('Loading...');
            this.createView('quickCreate', 'views/modals/edit', options, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.collection.fetch();

                    if (this.getMetadata().get(`clientDefs.${this.scope}.navigateToEntityAfterQuickCreate`)) {
                        this.navigateToEdit(view.getView('edit').model.id);
                    }
                }, this);
            }.bind(this));
        }
    })
);

