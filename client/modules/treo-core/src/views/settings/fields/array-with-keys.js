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

Espo.define('treo-core:views/settings/fields/array-with-keys', 'views/fields/array',
    Dep => Dep.extend({

        editTemplate: 'treo-core:settings/fields/array-with-keys/edit',

        notValidKeys: [],

        events: _.extend({
            'change .abbreviation': function (e) {
                let currentTarget = $(e.currentTarget);
                let previousAbbreviation = currentTarget.parent().data('value');
                let currentAbbreviation = currentTarget.val();
                this.changeAbbreviation(previousAbbreviation, currentAbbreviation);
            }
        }, Dep.prototype.events),

        setup() {
            Dep.prototype.setup.call(this);

            if (!this.validations.includes('key')) {
                this.validations.push('key');
            }
        },

        changeAbbreviation(previousAbbreviation, currentAbbreviation) {
            debugger;
            if (!currentAbbreviation.length) {
                return;
            }

            currentAbbreviation = this.clearValue(currentAbbreviation);
            currentAbbreviation = this.modifyValue(currentAbbreviation);

            this.selected.splice(this.selected.indexOf(previousAbbreviation), 1);
            this.selected.push(currentAbbreviation);

            this.translatedOptions[currentAbbreviation] = this.translatedOptions[previousAbbreviation];
            delete this.translatedOptions[previousAbbreviation];

            this.trigger('change');
        },

        addValue(value) {
            let clearedValue = this.clearValue(value);
            let modifiedValue = this.modifyValue(clearedValue);
            this.translatedOptions[modifiedValue] = value;

            Dep.prototype.addValue.call(this, modifiedValue);
        },

        removeValue(value) {
            debugger;
            let valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '\\"');

            this.$list.children('[data-value="' + valueSanitized + '"]').remove();
            let index = this.selected.indexOf(value);
            this.selected.splice(index, 1);
            delete this.translatedOptions[value];
            this.trigger('change');
        },

        clearValue(value) {
            let cleared = value;
            if (cleared) {
                cleared = cleared.replace(/-/g, '').replace(/_/g, '').replace(/[^\w\s]/gi, '').replace(/ (.)/g, (match, g) => g.toUpperCase()).replace(' ', '');
            }
            return cleared;
        },

        modifyValue(value) {
            return `${this.options.parentModel.get('measureSelect')}_${value}`;
        },

        getAbbreviationFromValue(value) {
            let result = value;
            if (value) {
                let parts = value.split('_').slice(1);
                result = parts.join('_');
            }
            return result;
        },

        getItemHtml(value) {
            if (this.translatedOptions !== null) {
                for (let item in this.translatedOptions) {
                    if (this.translatedOptions[item] === value) {
                        value = item;
                        break;
                    }
                }
            }

            value = value.toString();

            let valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '&quot;');

            let label = valueSanitized;
            if (this.translatedOptions) {
                label = ((value in this.translatedOptions) ? this.translatedOptions[value] : label);
                label = label.toString();
                label = this.getHelper().stripTags(label);
                label = label.replace(/"/g, '&quot;');
            }

            let html = `
                    <div class="list-group-item" data-value="${valueSanitized}" style="cursor: default;">
                        ${label}&nbsp;
                        <a href="javascript:" class="pull-right" data-value="${valueSanitized}" data-action="removeValue"><span class="fas fa-times"></a>
                    </div>`;

            if (this.options.editableKey) {
                html = `
                    <div class="list-group-item" data-value="${valueSanitized}" style="cursor: default;">
                        <a href="javascript:" class="pull-right" data-value="${valueSanitized}" data-action="removeValue"><span class="fas fa-times"></span></a>
                        <span>${label}&nbsp;</span>
                        <div class="key-array-abbreviation">
                            <label class="control-label">${this.translate('Abbreviation', 'labels', 'Global')}:</label><input class="form-control abbreviation" value="${this.getAbbreviationFromValue(valueSanitized)}" type="text" autocomplete="off">
                        </div>
                    </div>`;
            }

            return html;
        },

        validateKey: function () {
            if (this.notValidKeys && this.notValidKeys.length > 0) {
                let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate('Abbreviation', 'labels', 'Global'));
                this.showValidationMessage(msg, '.list-group-item .abbreviation');
                return true;
            }
        },

    })
);
