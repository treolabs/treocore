Espo.define('multilang:views/fields/array-multilang', 'views/fields/array',
    Dep => Dep.extend({

        allTranslatedOptions : {},

        langFieldNameList: [],

        listTemplate: 'multilang:fields/array-multilang/list',

        detailTemplate: 'multilang:fields/array-multilang/detail',

        editTemplate: 'multilang:fields/array-multilang/edit',

        events: {
            'click [data-action="removeValue"]': function (e) {
                let name = $(e.currentTarget).data('name');
                let value = $(e.currentTarget).data('value').toString();
                this.removeValue(value, name);
            },
            'click [data-action="showAddModal"]': function (e) {
                let name = $(e.currentTarget).data('name');
                let value = this.model.get(name) || [];
                let options = (this.model.getFieldParam(this.name, `options${name.replace(this.name, '')}`) || []).filter(item => value.indexOf(item) < 0);
                let translatedOptions = (this.allTranslatedOptions[`options${name.replace(this.name, '')}`] || {});

                this.createView('addModal', 'views/modals/array-field-add', {
                    options: options,
                    translatedOptions: translatedOptions
                }, function (view) {
                    view.render();
                    this.listenToOnce(view, 'add', function (item) {
                        this.addValue(item, name);
                        view.close();
                    }.bind(this));
                }.bind(this));
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.allTranslatedOptions = this.translate(this.name, 'options', this.model.name) || {};

            this.translatedOptions = this.allTranslatedOptions['options'];

            let inputLanguageList = this.getConfig().get('isMultilangActive') ? this.getConfig().get('inputLanguageList') : [];
            this.langFieldNameList = Array.isArray(inputLanguageList) ? inputLanguageList.map(lang => this.getInputLangName(lang)) : [];

            if (this.model.isNew() && this.defs.params && this.defs.params.default) {
                let data = {};
                this.getLangFieldNameList().forEach(name => data[name] = this.defs.params.default, this);
                this.model.set(data);
            }
        },

        data() {
            let value = this.model.get(this.name) || [];
            let data = Dep.prototype.data.call(this);
            data.itemHtmlList = value.map(item => this.getItemHtml(item, this.name));
            data.isEmpty = value.length === 0

            data.valueList = this.getLangFieldNameList().map(name => {
                let value = this.model.get(name) || [];
                let translatedOptions = (this.allTranslatedOptions[`options${name.replace(this.name, '')}`] || {});
                let options = this.model.getFieldParam(this.name, `options${name.replace(this.name, '')}`);
                return {
                    itemHtmlList: value.map(item => this.getItemHtml(item, name)),
                    hasOptions: options ? true : false,
                    selected: value,
                    translatedOptions: translatedOptions,
                    isEmpty: value.length === 0,
                    name: name,
                    value: value.map(val => val in translatedOptions ? translatedOptions[val] : val).join(', '),
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel,
                    isEmpty: value.length === 0,
                }
            }, this);
            return data;
        },

        getValueForDisplay() {
            return (this.model.get(this.name) || []).map(function (item) {
                if (this.translatedOptions != null) {
                    if (item in this.translatedOptions) {
                        return this.getHelper().stripTags(this.translatedOptions[item]);
                    }
                }
                return this.getHelper().stripTags(item);
            }, this).join(', ');
        },

        afterRender() {
            if (this.mode == 'edit') {
                let that = this;
                this.$list = this.$el.find('.list-group');
                let $select = this.$el.find('.select');

                if ($select.length) {
                    $select.on('keypress', function (e) {
                        if (e.keyCode == 13) {
                            let value = $(this).val();
                            if (that.noEmptyString && value === '') {
                                return;
                            }
                            let name = $(this).data('name');
                            that.addValue(value, name);
                            $(this).val('');
                        }
                    });
                }

                this.$list.sortable({
                    stop: function () {
                        this.fetchFromDom();
                        this.trigger('change');
                    }.bind(this)
                });
            }

            if (this.mode == 'search') {
                this.renderSearch();
            }
        },

        addValue(value, name) {
            name = this.getHelper().stripTags(name).replace(/"/g, '\\"');
            let modelValue = this.model.get(name) || [];
            if (modelValue.indexOf(value) == -1) {
                let html = this.getItemHtml(value, name);
                this.$list.filter(`[data-name="${name}"]`).append(html);
                modelValue.push(value)
                let data = {};
                data[name] = modelValue
                this.model.set(data);
                this.trigger('change');
            }
        },

        removeValue(value, name) {
            name = this.getHelper().stripTags(name).replace(/"/g, '\\"');
            let valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '\\"');
            let modelValue = this.model.get(name) || [];
            this.$list.filter(`[data-name="${name}"]`).children('[data-value="' + valueSanitized + '"]').remove();
            var index = modelValue.indexOf(value);
            modelValue.splice(index, 1);
            let data = {};
            data[name] = modelValue
            this.model.set(data);
            this.trigger('change');
        },

        getItemHtml(value, name) {
            name = name || this.name;
            let translatedOptions = this.allTranslatedOptions[`options${name.replace(this.name, '')}`] || {};
            if (translatedOptions != null) {
                for (var item in translatedOptions) {
                    if (translatedOptions[item] == value) {
                        value = item;
                        break;
                    }
                }
            }

            value = value.toString();

            var valueSanitized = this.getHelper().stripTags(value).replace(/"/g, '&quot;');

            var label = valueSanitized;
            if (translatedOptions) {
                label = ((value in translatedOptions) ? translatedOptions[value] : label);
            }

            return `<div class="list-group-item" data-value="${valueSanitized}" data-name="${name}" style="cursor: default;">${label}&nbsp;
                <a href="javascript:" class="pull-right" data-value="${valueSanitized}" data-name="${name}" data-action="removeValue"><span class="glyphicon glyphicon-remove"></a>
                </div>`;
        },

        fetchFromDom() {
            let data = {};
            data[this.name] = [];
            this.getLangFieldNameList().forEach(item => data[item] = []);
            this.$el.find('.list-group .list-group-item').each(function (i, el) {
                let name = $(el).data('name').toString();
                let value = $(el).data('value').toString();
                data[name].push(value);
            });
            return data;
        },

        fetch() {
            return this.fetchFromDom();
        },

        validateRequired() {
            let error = false;
            if (this.isRequired()) {
                let value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    error = true;
                }

                this.getLangFieldNameList().forEach(name => {
                    let value = this.model.get(name);
                    error = error || !value || value.length == 0;
                }, this);

                if (error) {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                }
            }
            return error;
        },

        showRequiredSign() {
            Dep.prototype.showRequiredSign.call(this);
            this.getLangFieldNameList().forEach(name => this.$el.find(`[data-name=${name}] .required-sign`).show(), this);
        },

        hideRequiredSign() {
            Dep.prototype.hideRequiredSign.call(this);
            this.getLangFieldNameList().forEach(name => this.$el.find(`[data-name=${name}] .required-sign`).hide(), this);
        },

        getInputLangName(lang) {
            return lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), this.name);
        },

        getLangFieldNameList() {
            return this.langFieldNameList;
        }
    })
);


