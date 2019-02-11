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

        configuration: null,

        validations: [],

        setup() {
            Dep.prototype.setup.call(this);

            this.name = this.options.name || this.options.defs.name;
            this.mode = this.options.mode || this.mode;

            this.configurableMode = this.configurableMode || this.model.getFieldParam(this.name, 'configurableMode');
            this.configuration = Espo.Utils.cloneDeep(this.getConfig().get('unitsOfMeasure') || {});

            this.getModelFactory().create(null, model => {
                this.model = model;
                this.setupFields();
            });
        },

        setupFields() {
            let measurements = Object.keys(this.configuration);
            let viewName = 'views/fields/enum';
            if (this.configurableMode) {
                viewName = 'treo-core:views/fields/enum-with-edit-options';
                this.model.set({measureSelect: measurements[0], unitSelect: this.getUnits()[0]});
            }

            this.createView('measureSelect', viewName, {
                el: `${this.options.el} .field[data-name="measureSelect"]`,
                model: this.model,
                name: 'measureSelect',
                params: {
                    options: measurements
                },
                mode: 'edit'
            }, view => {
                view.listenTo(view, 'options-updated', params => {
                    this.updateConfiguration(view, params);
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
                view.listenTo(view, 'options-updated', params => {
                    this.updateConfiguration(view, params);
                });
                view.listenTo(this.model, 'change:measureSelect', () => {
                    view.params.options = this.getUnits();
                    view.reRender();
                });
                view.render();
            });

        },

        updateConfiguration(view, params) {
            if (this.configurableMode) {
                let measurements = Object.keys(this.configuration);
                if (view.name === 'measureSelect') {
                    measurements = measurements.concat((view.params.options|| []).filter(item => !measurements.includes(item)));
                    measurements.forEach(measurement => {
                        if (!Object.keys(this.configuration).includes(measurement)) {
                            this.configuration[measurement] = {};
                        }
                    });
                } else {
                    this.configuration[this.model.get('measureSelect')] = params.abbreviations || {};
                }
            }
        },

        getUnits() {
            let measure = this.model.get('measureSelect') || Object.keys(this.configuration)[0];
            let units =  this.configuration[measure] || {};
            return Object.keys(units);
        },

        fetch() {
            let data = {};
            data[this.name] = this.configuration || {};
            return data;
        },

        validate() {
            for (let i in this.validations) {
                let method = 'validate' + Espo.Utils.upperCaseFirst(this.validations[i]);
                if (this[method].call(this)) {
                    this.trigger('invalid');
                    return true;
                }
            }
            return false;
        },

        getTranslations() {
            return {
                measureSelect: this.getView('measureSelect').translatedOptions || {},
                unitSelect: this.getView('unitSelect').translatedOptions || {}
            }
        }

    })
);