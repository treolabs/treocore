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

Espo.define('treo-core:search-manager', 'class-replace!treo-core:search-manager', function (SearchManager) {

     _.extend(SearchManager.prototype, {
        getWhere: function () {
            var where = [];

            if (this.data.textFilter && this.data.textFilter != '') {
                where.push({
                    type: 'textFilter',
                    value: this.data.textFilter
                });
            }

            if (this.data.bool) {
                var o = {
                    type: 'bool',
                    value: [],
                };
                for (var name in this.data.bool) {
                    if (this.data.bool[name]) {
                        o.value.push(name);
                    }
                }
                if (o.value.length) {
                    where.push(o);
                }
            }

            if (this.data.primary) {
                var o = {
                    type: 'primary',
                    value: this.data.primary,
                };
                if (o.value.length) {
                    where.push(o);
                }
            }

            if (this.data.advanced) {
                var groups = {};
                for (var name in this.data.advanced) {
                    var defs = this.data.advanced[name];
                    if (!defs) {
                        continue;
                    }
                    var clearedName = name.split('-')[0];
                    var part = this.getWherePart(clearedName, defs);
                    (groups[clearedName] = groups[clearedName] || []).push(part);
                }
                var finalPart = [];
                for (var name in groups) {
                    var group;
                    if (groups[name].length > 1) {
                        group = {
                            type: 'or',
                            value: groups[name]
                        };
                    } else {
                        group = groups[name][0];
                    }
                    finalPart.push(group);
                }
                where = where.concat(finalPart);
            }
            return where;
        },

         getWherePart: function (name, defs) {
             var attribute = name;

             if ('where' in defs) {
                 return defs.where;
             } else {
                 var type = defs.type;

                 if (type == 'or' || type == 'and') {
                     var a = [];
                     var value = defs.value || {};
                     for (var n in value) {
                         a.push(this.getWherePart(n, _.extend(value[n], {isAttribute: defs.isAttribute, isImport: defs.isImport})));
                     }
                     return {
                         type: type,
                         value: a
                     };
                 }
                 if ('field' in defs) { // for backward compatibility
                     attribute = defs.field;
                 }
                 if ('attribute' in defs) {
                     attribute = defs.attribute;
                 }
                 if (defs.dateTime) {
                     return {
                         type: type,
                         attribute: attribute,
                         value: defs.value,
                         dateTime: true,
                         timeZone: this.dateTime.timeZone || 'UTC'
                     };
                 } else {
                     value = defs.value;
                     return {
                         isAttribute: defs.isAttribute,
                         isImport: defs.isImport,
                         type: type,
                         attribute: defs.isImport ? undefined : attribute,
                         value: value
                     };
                 }
             }
         },
    });

    return SearchManager;
});