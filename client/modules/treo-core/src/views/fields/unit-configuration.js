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

            this.configuration = Espo.Utils.cloneDeep(this.getConfig().get('unitsOfMeasure') || {});

            this.getModelFactory().create(null, model => {
                this.model = model;
                this.setupFields();
            });
        },

        setupFields() {
            let measurements = Object.keys(this.configuration);
            let unitTranslates = this.getUnitsTranslates();
            let units = Object.keys(unitTranslates);

            this.model.set({measure: measurements[0], unit: units[0]}, {silent: true});

            this.createView('measure', 'treo-core:views/fields/enum-with-edit-options', {
                el: `${this.options.el} .field[data-name="measure"]`,
                model: this.model,
                name: 'measure',
                params: {
                    options: measurements,
                    translation: 'Global.measure'
                },
                mode: 'edit'
            }, view => {
                view.listenTo(view, 'options-updated', params => {
                    this.updateConfiguration(view, params);
                });
                view.render();
            });

            this.createView('unit', 'treo-core:views/fields/enum-with-edit-options', {
                el: `${this.options.el} .field[data-name="unit"]`,
                model: this.model,
                name: 'unit',
                params: {
                    options: units,
                    translatedOptions: unitTranslates
                },
                mode: 'edit',
                editableKey: true
            }, view => {
                view.listenTo(view, 'options-updated', params => {
                    this.updateConfiguration(view, params);
                });
                view.listenTo(this.model, 'change:measure', () => {
                    let translates = this.getUnitsTranslates();
                    view.translatedOptions = translates;
                    view.params.options = Object.keys(translates);
                    view.reRender();
                });
                view.render();
            });

        },

        updateConfiguration(view, params) {
            if (view.name === 'measure') {
                let previousMeasurements = Object.keys(this.configuration);
                let currentMeasurements = view.params.options || [];
                let allMeasurements = _.union(previousMeasurements, currentMeasurements);
                allMeasurements.forEach(measure => {
                    if (!currentMeasurements.includes(measure)) {
                        delete this.configuration[measure];
                    } else if (!previousMeasurements.includes(measure)) {
                        this.configuration[measure] = {};
                    }
                });
            } else {
                let unitList = params.unitSymbols || {};
                let baseUnit = Object.keys(unitList)[0];
                let unitRates = {};
                Object.keys(unitList).forEach(unitSymbol => {
                    if (unitSymbol !== baseUnit) {
                        unitRates[unitSymbol] = 1;
                    }
                });
                this.configuration[this.model.get('measure')] = {
                    unitList: unitList,
                    baseUnit: baseUnit,
                    unitRates: unitRates
                }
            }
            view.reRender();
            view.trigger('change');
        },

        getUnitsTranslates() {
            let translates = {};
            let measure = this.model.get('measure') || Object.keys(this.configuration)[0];
            let measureConfig =  this.configuration[measure] || {};
            let unitList = measureConfig.unitList || {};
            Object.keys(unitList).forEach(unit => {
                let units = this.getLanguage().get('Global', 'unit', measure);
                translates[unit] = Espo.Utils.isObject(units) ? units[unit] : unit;
            });
            return translates;
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
                measure: this.getView('measure').translatedOptions || {},
                unit: this.getView('unit').translatedOptions || {}
            }
        }

    })
);