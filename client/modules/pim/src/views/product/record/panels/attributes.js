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

Espo.define('pim:views/product/record/panels/attributes', 'views/record/panels/relationship',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.__proto__.setup.call(this);

            this.link = this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            let url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

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
                            fullFormDisabled: true
                        }
                    });
                }
            }

            if (this.defs.select) {
                let data = {link: this.link};
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

            this.once('after:render', () => {
                this.setupGrid();
            });

            this.setupFilterActions();

            this.listenTo(this.model, 'after:save', () => {
                this.actionRefresh();
            });
        },

        getFieldViews() {
            let gridView = this.getView('grid');
            return gridView ? gridView.nestedViews : null;
        },

        setupGrid() {
            this.getModelFactory().create('productAttributesGrid', model => {
                let viewName = this.getMetadata().get('clientDefs.Product.relationshipPanels.attributes.gridView') || 'pim:views/attribute/grid';
                this.createView('grid', viewName, {
                    model: model,
                    gridLayout: [],
                    el: this.options.el + ' .list-container',
                    entity: this.model.name,
                    entityId: this.model.id
                }, function (view) {
                    view.render();
                    this.updateGrid();
                });
            });
        },

        updateGrid() {
            let that = this;

            this.ajaxGetRequest(`Markets/Product/${this.model.id}/attributes`).then(function (response) {
                if (Array.isArray(response) && response) {
                    let data = {};
                    let translates = {};
                    let layout = [];
                    let defs = {
                        fields: {}
                    };
                    let inputLanguageList = that.getConfig().get('inputLanguageList');

                    response.forEach(attribute => {
                        data[attribute.attributeId] = attribute.value;
                        translates[attribute.attributeId] = attribute.name;

                        defs.fields[attribute.attributeId] = {
                            type: attribute.type,
                            required: attribute.isRequired,
                            options: attribute.typeValue
                        };

                        if (['varcharMultiLang', 'textMultiLang', 'enumMultiLang', 'multiEnumMultiLang', 'arrayMultiLang'].indexOf(attribute.type) > -1) {
                            if (Array.isArray(inputLanguageList) && inputLanguageList.length) {
                                inputLanguageList.map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''))
                                    .forEach(item => {
                                        data[`${attribute.attributeId}${item}`] = attribute[`value${item}`];
                                        defs.fields[attribute.attributeId][`options${item}`] = attribute[`typeValue${item}`];
                                    });
                            }
                        }

                        let group = layout.find(item => item.id === attribute.attributeGroupId)
                        if (!group) {
                            group = {
                                id: attribute.attributeGroupId,
                                label: attribute.attributeGroupName,
                                order: attribute.attributeGroupOrder,
                                rows: []
                            };
                            layout.push(group);
                        }
                        let item = {
                            name: attribute.attributeId,
                            defs: defs.fields[attribute.attributeId],
                            label: attribute.name,
                            isCustom: attribute.isCustom
                        };
                        if (group.rows.length && group.rows[group.rows.length - 1].length === 1) {
                            group.rows[group.rows.length - 1].push(item);
                        } else {
                            group.rows.push([item]);
                        }
                    });

                    layout.sort((first, second) => first.order > second.order);

                    this.getLanguage().data['productAttributesGrid'] = {fields: translates};

                    let grid = that.getView('grid');
                    if (grid) {
                        grid.model.setDefs(defs);
                        grid.attributes = Espo.Utils.cloneDeep(data);
                        grid.model.set(data);
                        grid.gridLayout = layout;
                        grid.reRender();
                    }
                }
            });
        },

        getDetailView() {
            let panelView = this.getParentView();
            if (panelView) {
                return panelView.getParentView()
            }
            return null;
        },

        getInitAttributes() {
            return this.getView('grid').attributes || [];
        },

        save() {
            let inputLanguageList = (this.getConfig().get('inputLanguageList') || [])
                .map(lang => lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), ''));
            let data = [];
            let fields = this.getFieldViews();
            for (let i in fields) {
                let fieldValue =  fields[i].fetch();
                let item = {
                    attributeId: fields[i].name,
                    value: fieldValue[fields[i].name],
                };
                inputLanguageList.forEach(lang => item[`value${lang}`] = fieldValue[`${fields[i].name}${lang}`] || null);
                data.push(item);
            };
            this.ajaxPutRequest(`Markets/Product/${this.model.id}/attributes`, data)
                .then(response => {
                    this.updateGrid();
                    this.model.trigger('after:attributesSave');
                    this.notify('Saved', 'success');
                });
        },

        cancelEdit() {
            let gridView = this.getView('grid');
            if (gridView) {
                gridView.model.set(gridView.attributes);
            }
        },

        actionRefresh: function () {
            this.updateGrid();
        },

    })
);