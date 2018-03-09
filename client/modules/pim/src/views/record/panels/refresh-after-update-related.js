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

Espo.define('pim:views/record/panels/refresh-after-update-related', 'views/record/panels/relationship',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.__proto__.setup.call(this);

            this.link = this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            var url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!this.readOlny && !this.defs.readOnly) {
                if (!('create' in this.defs)) {
                    this.defs.create = true;
                }
                if (!('select' in this.defs)) {
                    this.defs.select = true;
                }
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            if (this.defs.create) {
                if (this.getAcl().check(this.scope, 'create') && !~['User', 'Team'].indexOf()) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'create',
                        aclScope: this.scope,
                        html: '<span class="glyphicon glyphicon-plus"></span>',
                        data: {
                            link: this.link,
                        }
                    });
                }
            }

            if (this.defs.select) {
                var data = {link: this.link};
                if (this.defs.selectPrimaryFilterName) {
                    data.primaryFilterName = this.defs.selectPrimaryFilterName;
                }
                if (this.defs.selectBoolFilterList) {
                    data.boolFilterList = this.defs.selectBoolFilterList;
                }
                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: data
                });
            }

            var layoutName = 'listSmall';
            var listLayout = null;
            var layout = this.defs.layout || null;
            if (layout) {
                if (typeof layout == 'string') {
                    layoutName = layout;
                } else {
                    layoutName = 'listRelationshipCustom';
                    listLayout = layout;
                }
            }
            var sortBy = this.defs.sortBy || null;
            var asc = this.defs.asc || null;

            this.wait(true);
            this.getCollectionFactory().create(this.scope, function (collection) {
                collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                if (this.defs.filters) {
                    var searchManager = new SearchManager(collection, 'listRelationship', false, this.getDateTime());
                    searchManager.setAdvanced(this.defs.filters);
                    collection.where = searchManager.getWhere();
                }

                collection.url = collection.urlRoot = url;
                if (sortBy) {
                    collection.sortBy = sortBy;
                }
                if (asc) {
                    collection.asc = asc;
                }
                this.collection = collection;

                this.setFilter(this.filter);

                if (this.fetchOnModelAfterRelate) {
                    this.listenTo(this.model, 'after:relate', function () {
                        collection.fetch();
                    }, this);
                }

                var viewName = this.defs.recordListView || this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'Record.List';

                this.once('after:render', function () {
                    collection.once('sync', function () {
                        this.createView('list', viewName, {
                            collection: collection,
                            layoutName: layoutName,
                            listLayout: listLayout,
                            checkboxes: false,
                            rowActionsView: this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView),
                            buttonsDisabled: true,
                            el: this.options.el + ' .list-container',
                        }, function (view) {
                            view.render();

                            this.listenTo(view, 'after:save', function (model) {
                                view.reRender();
                                this.model.trigger('after:updateRelated');
                            }, this);
                        });
                    }, this);
                    collection.fetch();
                }, this);

                this.wait(false);
            }, this);

            this.setupFilterActions();
        },

    })
);