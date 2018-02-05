Espo.define('multilang:views/fields/varchar-multilang', 'views/fields/varchar',
    Dep => Dep.extend({

        listTemplate: 'multilang:fields/varchar-multilang/list',

        editTemplate: 'multilang:fields/varchar-multilang/edit',

        detailTemplate: 'multilang:fields/varchar-multilang/detail',

        langFieldNameList: [],

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
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.valueList = this.langFieldNameList.map(name => {
                let value = this.model.get(name);
                return {
                    name: name,
                    value: value,
                    isNotEmpty: value !== null && value !== '',
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel
                }
            }, this);
            return data;
        },

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            this.langFieldNameList.forEach(name => {
                let value = this.$el.find(`[name="${name}"]`).val();
                if (this.params.trim) {
                    if (typeof value.trim === 'function') {
                        value = value.trim();
                    }
                }
                data[name] = value;
            });
            return data;
        },

        validateRequired() {
            let error = false;
            if (this.isRequired()) {
                if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                    error = true;
                }

                this.langFieldNameList.forEach(name => error = error || this.model.get(name) === '' || this.model.get(name) === null, this);

                if (error) {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    error = true;
                }
            }
            return error;
        },

        showRequiredSign() {
            Dep.prototype.showRequiredSign.call(this);
            this.langFieldNameList.forEach(name => this.$el.find(`[data-name=${name}] .required-sign`).show(), this);
        },

        hideRequiredSign() {
            Dep.prototype.hideRequiredSign.call(this);
            this.langFieldNameList.forEach(name => this.$el.find(`[data-name=${name}] .required-sign`).hide(), this);
        },

        getInputLangName(lang) {
            return lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), this.name);
        }

    })
);

