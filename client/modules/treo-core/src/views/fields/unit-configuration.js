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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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

Espo.define('treo-core:views/fields/unit-configuration', 'view',
    Dep => Dep.extend({

        template: 'treo-core:fields/unit-configuration/edit',

        initConfiguration: null,

        configuration: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.name = this.options.name || this.options.defs.name;
            this.configurableMode = this.configurableMode || this.model.getFieldParam(this.name, 'configurableMode');
            this.initConfiguration = this.getConfig().get('unitsOfMeasure') || {};
            this.configuration = Espo.Utils.cloneDeep(this.initConfiguration);
            this.getModelFactory().create(null, model => {
                this.model = model;
                this.setupFields();
            });
        },

        setupFields() {
            let viewName = 'views/fields/enum';
            if (this.configurableMode) {
                viewName = 'treo-core:views/fields/enum-with-edit-options';
                this.model.set({measureSelect: Object.keys(this.configuration)[0], unitSelect: this.getUnits()[0]});
            }

            this.createView('measureSelect', viewName, {
                el: `${this.options.el} .field[data-name="measureSelect"]`,
                model: this.model,
                name: 'measureSelect',
                params: {
                    options: Object.keys(this.configuration)
                },
                mode: 'edit'
            }, view => {
                view.listenTo(view, 'options-updated', () => {
                    this.updateConfiguration(view);
                });
                view.render();
            });

            this.createView('unitSelect', viewName, {
                el: `${this.options.el} .field[data-name="unitSelect"]`,
                model: this.model,
                name: 'unitSelect',
                params: {
                    options: this.getUnits()
                },
                mode: 'edit',
                editableKey: this.configurableMode
            }, view => {
                view.listenTo(view, 'options-updated', () => {
                    this.updateConfiguration(view);
                });
                view.listenTo(this.model, 'change:measureSelect', () => {
                    view.params.options = this.getUnits();
                    view.reRender();
                });
                view.render();
            });

        },

        updateConfiguration(view) {
            if (this.configurableMode) {
                let measurements = Object.keys(this.configuration);
                if (view.name === 'measureSelect') {
                    measurements = measurements.concat((view.params.options|| []).filter(item => !measurements.includes(item)));
                    measurements.forEach(measurement => {
                        if (!Object.keys(this.configuration).includes(measurement)) {
                            this.configuration[measurement] = [];
                        }
                    });
                } else {
                    let measure = this.model.get('measureSelect');
                    this.configuration[measure] = this.getUnits();
                }
            }
        },

        getUnits() {
            let measure = this.model.get('measureSelect') || Object.keys(this.configuration)[0];
            return this.configuration[measure].map(unit => `${measure}_${unit}`);
        }

    })
);