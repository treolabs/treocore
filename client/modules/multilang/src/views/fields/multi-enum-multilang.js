Espo.define('multilang:views/fields/multi-enum-multilang', 'views/fields/multi-enum',
    Dep => Dep.extend({

        allTranslatedOptions : {},

        langFieldNameList: [],

        listTemplate: 'multilang:fields/multi-enum-multilang/list',

        editTemplate: 'multilang:fields/multi-enum-multilang/edit',

        detailTemplate: 'multilang:fields/multi-enum-multilang/detail',

        setup() {
            Dep.prototype.setup.call(this);

            this.allTranslatedOptions = this.translate(this.name, 'options', this.model.name) || {};

            this.translatedOptions = this.allTranslatedOptions['options'];

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
                let value = this.model.get(name) || [];
                let translatedOptions = (this.allTranslatedOptions[`options${name.replace(this.name, '')}`] || {});
                return {
                    name: name,
                    shortLang: name.slice(-4, -2).toLowerCase() + '_' + name.slice(-2).toUpperCase(),
                    customLabel: this.options.customLabel,
                    value: value.map(val => val in translatedOptions ? translatedOptions[val] : val).join(', '),
                    isEmpty: value.length === 0,
                }
            }, this);
            return data;
        },

        afterRender() {
            if (this.mode == 'edit') {
                this.$element = this.$el.find('[name="' + this.name + '"]');
                this.$element.val(this.selected.join(':,:'));

                let data = [];
                (this.params.options || []).forEach(function (value) {
                    var label = this.getLanguage().translateOption(value, this.name, this.scope);
                    if (this.translatedOptions) {
                        if (value in this.translatedOptions) {
                            label = this.translatedOptions[value];
                        }
                    }
                    data.push({
                        value: value,
                        label: label
                    });
                }, this);

                this.$element.selectize({
                    options: data,
                    delimiter: ':,:',
                    labelField: 'label',
                    valueField: 'value',
                    highlight: false,
                    searchField: ['label'],
                    plugins: ['remove_button', 'drag_drop'],
                    score: function (search) {
                        var score = this.getScoreFunction(search);
                        search = search.toLowerCase();
                        return function (item) {
                            if (item.label.toLowerCase().indexOf(search) === 0) {
                                return score(item);
                            }
                            return 0;
                        };
                    }
                });

                this.$element.on('change', function () {
                    this.trigger('change');
                }.bind(this));

                this.langFieldNameList.forEach(name => {
                    let element = this.$el.find('[name="' + name + '"]');
                    element.val((this.model.get(name) || []).join(':,:'));

                    let data = [];
                    let translatedOptions = (this.allTranslatedOptions[`options${name.replace(this.name, '')}`] || {});
                    (this.model.getFieldParam(this.name, `options${name.replace(this.name, '')}`) || []).forEach(value => {
                        data.push({
                            value: value,
                            label: value in translatedOptions ? translatedOptions[value] : value
                        });
                    });

                    element.selectize({
                        options: data,
                        delimiter: ':,:',
                        labelField: 'label',
                        valueField: 'value',
                        highlight: false,
                        searchField: ['label'],
                        plugins: ['remove_button', 'drag_drop'],
                        score: function (search) {
                            var score = this.getScoreFunction(search);
                            search = search.toLowerCase();
                            return function (item) {
                                if (item.label.toLowerCase().indexOf(search) === 0) {
                                    return score(item);
                                }
                                return 0;
                            };
                        }
                    });

                    element.on('change', function () {
                        this.trigger('change');
                    }.bind(this));
                }, this);
            }

            if (this.mode == 'search') {
                this.renderSearch();
            }
        },

        fetch: function () {
            var list = this.$element.val().split(':,:');
            if (list.length == 1 && list[0] == '') {
                list = [];
            }
            var data = {};
            data[this.name] = list;

            this.langFieldNameList.forEach(name => {
                let list = this.$el.find(`[name="${name}"]`).val().split(':,:');
                if (list.length == 1 && list[0] == '') {
                    list = [];
                }
                data[name] = list;
            });

            return data;
        },

        validateRequired() {
            let error = false;
            if (this.isRequired()) {
                let value = this.model.get(this.name);
                if (!value || value.length == 0) {
                    error = true;
                }

                this.langFieldNameList.forEach(name => {
                    let value = this.model.get(name);
                    error = error || !value || value.length == 0;
                }, this);

                if (error) {
                    let msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg, '.selectize-control');
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
