/*
 * ColoredFields
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

Espo.define('colored-fields:views/admin/field-manager/fields/colored-options', ['views/admin/field-manager/fields/options', 'lib!jscolor'], function (Dep) {
    return Dep.extend({

        optionColors: null,

        setup() {
            Dep.prototype.setup.call(this);

            this.optionColors = Espo.Utils.cloneDeep(this.model.get('optionColors') || {});
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'edit') {
                this.$list.find('[name="coloredValue"]').get().forEach(item => {
                    new jscolor(item)
                });
            }
        },

        fetch() {
            var data = Dep.prototype.fetch.call(this);

            if (data) {
                data.optionColors = {};
                (data[this.name] || []).forEach(function (value) {
                    let valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '&quot;');

                    data.optionColors[value] = this.$el.find('input[name="coloredValue"][data-value="' + valueSanitized + '"]').val().toString();
                }, this);
            }

            return data;
        },

        addValue(value) {
            if (this.selected.indexOf(value) == -1) {
                var html = this.getItemHtml(value);
                this.$list.append(html);
                this.selected.push(value);
                this.trigger('change');

                var valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '\\"');
                this.$list.find('[data-value="' + valueSanitized + '"] [name="coloredValue"]').get().forEach(item => {
                    new jscolor(item)
                });
            }
        },

        getItemHtml: function (value) {
            var valueSanitized = this.getHelper().stripTags(value);
            var translatedValue = this.translatedOptions[value] || valueSanitized;

            var valueSanitized = valueSanitized.replace(/"/g, '&quot;');

            let coloredValue = this.optionColors[value] || '333333';

            var html = '' +
            '<div class="list-group-item link-with-role form-inline" data-value="' + valueSanitized + '">' +
                '<div class="pull-left" style="width: 92%; display: inline-block;">' +
                    '<input name="coloredValue" data-value="' + valueSanitized + '" class="role form-control input-sm pull-right" value="' + coloredValue + '">' +
                    '<input name="translatedValue" data-value="' + valueSanitized + '" class="role form-control input-sm pull-right" value="' + translatedValue + '">' +
                    '<div>' + valueSanitized + '</div>' +
                '</div>' +
                '<div style="width: 8%; display: inline-block; vertical-align: top;">' +
                    '<a href="javascript:" class="pull-right" data-value="' + valueSanitized + '" data-action="removeValue"><span class="fas fa-times"></a>' +
                '</div><br style="clear: both;" />' +
            '</div>';

            return html;
        },

    });

});
