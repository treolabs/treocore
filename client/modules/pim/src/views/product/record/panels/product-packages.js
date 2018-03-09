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

Espo.define('pim:views/product/record/panels/product-packages', 'views/record/panels/bottom',
    Dep => Dep.extend({

        packageModel: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.once('after:render', () => {
                this.setupGrid();
            });
        },

        setupGrid() {
            this.ajaxGetRequest(`Markets/ProductTypePackage/${this.model.id}/view`)
                .then(response => {
                    this.clearNestedViews();

                    this.getModelFactory().create('ProductTypePackage', model => {
                        this.packageModel = model;

                        model.set(response);
                        if (response.id) {
                            model.id = response.id;
                        }

                        this.createView('grid', 'pim:views/product-type-package/grid', {
                            model: model,
                            el: this.options.el + ' .row',
                            attributes: response
                        }, function (view) {
                            view.render();
                        }, this);
                    });

                });
        },

        clearNestedViews() {
            for (let key in this.nestedViews) {
                this.clearView(key);
            }
        },

        getDetailView() {
            let panelView = this.getParentView();
            if (panelView) {
                return panelView.getParentView()
            }
            return null;
        },

        getFieldViews() {
            let gridView = this.getView('grid');
            return gridView ? gridView.nestedViews : null;
        },

        getInitAttributes() {
            return this.getView('grid').attributes || [];
        },

        cancelEdit() {
            let gridView = this.getView('grid');
            if (gridView) {
                gridView.model.set(gridView.attributes);
            }
        },

        save() {
            let data = {};
            let fieldViews = this.getFieldViews() || {};
            for (let key in fieldViews) {
                data = _.extend(data, fieldViews[key].fetch());
            }

            if (this.packageModel) {
                this.ajaxPutRequest(`Markets/ProductTypePackage/${this.model.id}/update`, this.packageModel.getClonedAttributes())
                    .then(response => {});
            }
        },

        actionRefresh: function () {
            this.setupGrid();
        },

    })
);