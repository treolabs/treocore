/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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

Espo.define('pim:views/product/search/filter', 'views/search/filter', function (Dep) {

    return Dep.extend({

        template: 'pim:product/search/filter',

        setup: function () {
            var name = this.name = this.options.name;
            name = name.split('-')[0];
            this.clearedName = name;
            var type = this.model.getFieldType(name) || this.options.type;

            if (type) {
                var viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    defs: {
                        name: name,
                    },
                    searchParams: this.options.params,
                });
            }
        },

        data: function () {
            return _.extend({
                label: this.options.isAttribute ? this.options.label : this.getLanguage().translate(this.name, 'fields', this.scope),
                clearedName: this.clearedName
            }, Dep.prototype.data.call(this));
        }
    });
});

