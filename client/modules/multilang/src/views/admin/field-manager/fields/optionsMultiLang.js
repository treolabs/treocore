Espo.define('multilang:views/admin/field-manager/fields/optionsMultiLang', 'views/admin/field-manager/fields/options',
    Dep => Dep.extend({
        fetch: function () {
            var data = Dep.prototype.fetch.call(this) || {};

            data.translatedOptions = {};
            data.translatedOptions[this.name] = {};
            (data[this.name] || []).forEach(function (value) {
                var valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '&quot;');
                data.translatedOptions[this.name][value] = this.$el.find('input[name="translatedValue"][data-value="' + valueSanitized + '"]').val() || value;
                data.translatedOptions[this.name][value] = data.translatedOptions[this.name][value].toString();

            }, this);

            //Check if exists other options and add it
            if (typeof this.model.attributes.translatedOptions === 'object') {
                for (var key in this.model.attributes.translatedOptions) {
                    if (typeof data.translatedOptions[key] !== 'object') {
                        data.translatedOptions[key] = this.model.attributes.translatedOptions[key];
                    }
                }
            }

            return data;
        }
    })
);