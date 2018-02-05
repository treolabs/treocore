Espo.define('multilang:views/fields/text-multilang', 'views/fields/text',
    Dep => Dep.extend({

        listTemplate: 'multilang:fields/text-multilang/list',

        editTemplate: 'multilang:fields/text-multilang/edit',

        detailTemplate: 'multilang:fields/text-multilang/detail',

        langFieldNameList: [],

        expandedFields: [],

        events: {
            'click a[data-action="seeMoreText"]': function (e) {
                this.expandedFields.push($(e.currentTarget).closest('[data-field]').data('field'));
                this.reRender();
            },
        },

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
            data.value = this.getTextValueForDisplay(this.model.get(this.name), this.name);
            data.valueList = this.langFieldNameList.map(name => {
                let value = this.model.get(name);
                return {
                    name: name,
                    value: this.getTextValueForDisplay(value, name),
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
                data[name] = this.$el.find(`[name="${name}"]`).val();
            });
            return data;
        },

        validateRequired() {
            let error = false
            if (this.isRequired()) {
                if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                    error = true;
                }

                this.langFieldNameList.forEach(name => error = error || this.model.get(name) === '' || this.model.get(name) === '', this)

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
        },

        getTextValueForDisplay(text, field) {
            if (text && (this.mode == 'detail' || this.mode == 'list') && !this.params.seeMoreDisabled && !this.expandedFields.includes(field)) {
                let isCut = false;

                if (text.length > this.detailMaxLength) {
                    text = text.substr(0, this.detailMaxLength);
                    isCut = true;
                }

                let nlCount = (text.match(/\n/g) || []).length;
                if (nlCount > this.detailMaxNewLineCount) {
                    let a = text.split('\n').slice(0, this.detailMaxNewLineCount);
                    text = a.join('\n');
                    isCut = true;
                }

                if (isCut) {
                    text += ' ...\n[#see-more-text]';
                }
            }
            return text || '';
        }

    })
);

