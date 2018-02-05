Espo.define('multilang:views/fields/enum-multilang', 'views/fields/enum',
    Dep => Dep.extend({

        listTemplate: 'multilang:fields/enum-multilang/list',

        editTemplate: 'multilang:fields/enum-multilang/edit',

        detailTemplate: 'multilang:fields/enum-multilang/detail',

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
        },

        data() {
            let data = Dep.prototype.data.call(this);
            data.valueList = this.langFieldNameList.map(name => {
                let value = this.model.get(name);
                return {
                    name: name,
                    params: {
                        options: this.model.getFieldParam(this.name, `options${name.replace(this.name, '')}`)
                    },
                    translatedOptions: (data.translatedOptions || {})[`options${name.replace(this.name, '')}`],
                    value: value,
                    isNotEmpty: value !== null && value !== '',
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel
                }
            }, this);
            data.translatedOptions = (data.translatedOptions || {})['options'];
            return data;
        },

        fetch() {
            let data = Dep.prototype.fetch.call(this);
            this.langFieldNameList.forEach(name => data[name] = this.$el.find(`[name="${name}"]`).val());
            return data;
        },

        validateRequired() {
            let error = false;
            if (this.isRequired()) {
                error = !this.model.get(this.name);

                this.langFieldNameList.forEach(name => error = error || !this.model.get(name), this);

                if (error) {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
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

