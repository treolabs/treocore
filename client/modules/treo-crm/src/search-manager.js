Espo.define('treo-crm:search-manager', 'class-replace!treo-crm:search-manager', function (SearchManager) {

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
                         a.push(this.getWherePart(n, value[n]));
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
                         type: type,
                         attribute: attribute,
                         value: value
                     };
                 }
             }
         },
    });

    return SearchManager;
});