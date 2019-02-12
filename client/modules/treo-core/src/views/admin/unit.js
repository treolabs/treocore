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

Espo.define('treo-core:views/admin/unit', 'views/settings/record/edit',
    Dep => Dep.extend({

        layoutName: 'unit',

        initialUnitsOfMeasure: {},

        setup() {
            Dep.prototype.setup.call(this);


            this.initialUnitsOfMeasure = Espo.Utils.cloneDeep(this.getConfig().get('unitsOfMeasure') || {});

            this.listenTo(this.model, 'after:save', () => {
                this.ajaxPostRequest('LabelManager/action/saveLabels', {
                    labels: this.getLabelsForSaveFromUnits(),
                    language: this.getPreferences().get('language') || this.getConfig().get('language'),
                    scope: 'Global'
                });
            }, this);
        },

        getLabelsForSaveFromUnits() {
            let labels = {};
            let attrs = this.getModifiedMeasurements();
            let translates = this.getTranslations();

            Object.keys(attrs).forEach(attr => {
                let measureLabelName = `options[.]measureSelect[.]${attr}`;
                labels[measureLabelName] = (translates.measureSelect || {})[attr];
                Object.keys(attrs[attr]).forEach(unit => {
                    let unitLabelName = `options[.]unitSelect[.]${unit}`;
                    labels[unitLabelName] = (translates.unitSelect || {})[unit];
                });
            });
            return labels;
        },

        getModifiedMeasurements() {
            let measurements = Espo.Utils.cloneDeep(this.initialUnitsOfMeasure);
            let data = this.model.get('unitsOfMeasure') || {};
            let attrs = {};
            for (let name in data) {
                if (_.isEqual(data[name], measurements[name])) {
                    continue;
                }
                attrs[name] = data[name];
            }
            return attrs;
        },

        getTranslations() {
            let translations = {};
            let middle = this.getView('middle');
            if (middle) {
                let unitsOfMeasure = middle.getView('unitsOfMeasure');
                if (unitsOfMeasure) {
                    translations = unitsOfMeasure.getTranslations() || {};
                }
            }
            return translations;
        }

    })
);

