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

Espo.define('treo-core:views/modals/edit-enum-options', 'views/modal',
    Dep => Dep.extend({

        template: 'treo-core:modals/edit-enum-options',

        buttonList: [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel'
            }
        ],

        setup() {
            Dep.prototype.setup.call(this);

            this.setupHeader();
            this.setupOptionFields();
        },

        setupOptionFields() {
            this.createView('enumOptions', 'treo-core:views/settings/fields/array-with-keys', {
                el: `${this.options.el} .field[data-name="enumOptions"]`,
                model: this.model,
                parentModel: this.options.parentModel,
                name: 'enumOptions',
                mode: 'edit',
                params: {
                    translatedOptions: this.options.translates,
                    required: true,
                    noEmptyString: true
                },
                editableKey: this.editableKey || this.options.editableKey
            }, view => view.render());
        },

        setupHeader() {
            this.header = this.translate('edit', 'labels');
        },

        actionSave() {
            if (this.validate()) {
                this.notify('Not valid', 'error');
                return;
            }

            let view = this.getView('enumOptions');
            let data = {options: view.selected, translatedOptions: (view.params || {}).translatedOptions};
            if ((this.editableKey || this.options.editableKey) && view.abbreviations && Espo.Utils.isObject(view.abbreviations)) {
                data = _.extend(data, {abbreviations: view.abbreviations});
            }
            this.trigger('after:save', data);
            this.close();
        },

        validate() {
            let notValid = false;
            let fields = this.nestedViews || {};
            for (let i in fields) {
                if (fields[i].mode === 'edit') {
                    if (!fields[i].disabled && !fields[i].readOnly) {
                        notValid = fields[i].validate() || notValid;
                    }
                }
            }
            return notValid
        },


    })
);