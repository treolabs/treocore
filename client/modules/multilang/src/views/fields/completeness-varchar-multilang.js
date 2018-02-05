Espo.define('multilang:views/fields/completeness-varchar-multilang', 'multilang:views/fields/varchar-multilang',
    Dep => Dep.extend({

        listTemplate: 'multilang:fields/completeness-varchar-multilang/list',

        detailTemplate: 'multilang:fields/completeness-varchar-multilang/detail',

        setup() {
            Dep.prototype.setup.call(this);

            let inputLanguageList = this.getConfig().get('isMultilangActive') ? this.getConfig().get('inputLanguageList') : [];
            this.langFieldNameList = Array.isArray(inputLanguageList) ? inputLanguageList.map(lang => this.getInputLangName(lang)) : [];

            if (this.model.isNew() && this.defs.params && this.defs.params.default) {
                let data = {};
                this.langFieldNameList.forEach(name => data[name] = this.defs.params.default, this);
                this.model.set(data);
            }

            this.events[`focusout [name="${this.name}"]`] = function (e) {
                let mainField = $(e.currentTarget);
                this.langFieldNameList.forEach(item => {
                    let secondaryField = this.$el.find(`[name="${item}"]`);
                    if (!secondaryField.val()) {
                        secondaryField.val(mainField.val());
                    }
                });
            }.bind(this);

            if (this.getPreferences().has('decimalMark')) {
                this.decimalMark = this.getPreferences().get('decimalMark');
            } else {
                if (this.getConfig().has('decimalMark')) {
                    this.decimalMark = this.getConfig().get('decimalMark');
                }
            }
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.value = Math.round(this.formatNumber(data.value) * 100) / 100;
            data.valueList = this.langFieldNameList.map(name => {
                let value = Math.round(this.formatNumber(this.model.get(name)) * 100) / 100;
                return {
                    name: name,
                    value: value,
                    isNotEmpty: value !== null && value !== '',
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel,
                    index: this.langFieldNameList.indexOf(name)
                }
            }, this);
            return data;
        },

        afterRender: function() {
            if(this.mode === 'list') {
                this.floatColor(parseFloat(this.$el.find('div.completeness.general')[0].innerText), this.$el.find('div.completeness.general'));
                this.langFieldNameList.forEach(function (e, i) {
                    this.floatColor(parseFloat(this.$el.find('div.completeness.list-elem-' + i + ' > span')[0].innerText), this.$el.find('div.completeness.list-elem-' + i));
                }, this);
            }
            if(this.mode === 'detail') {
                this.progressBarColor(parseFloat(this.$el.find('div.completeness.general')[0].innerText), this.$el.find('div.completeness.general .progress-bar'));
                this.langFieldNameList.forEach(function (e, i) {
                    this.progressBarColor(parseFloat(this.$el.find('.list-elem-' + i)[0].innerText), this.$el.find('.list-elem-' + i + ' .progress-bar'));
                }, this);
            }
        },

        floatColor: function (value, element) {
            if(value === 100) {
                element.addClass('green');
            } else if (value === 0) {
                element.addClass('red');
            } else {
                element.addClass('orange');
            }
        },

        progressBarColor: function (value, element) {
            if(value === 100) {
                element.addClass('progress-bar-success');
            } else if (value === 0) {
                element.addClass('progress-bar-danger');
            } else {
                element.addClass('progress-bar-warning');
            }
        },

        formatNumber: function (value) {
            if (this.disableFormatting) {
                return value;
            }
            if (value !== null) {
                var parts = value.toString().split(".");
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.thousandSeparator);
                return parts.join(this.decimalMark);
            }
            return '';
        },
    })
);