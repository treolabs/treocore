Espo.define('pim:views/attribute/fields/type-value', 'multilang:views/fields/array-multilang',
    Dep => Dep.extend({

        disableMultiLang: false,

        multiLangFieldTypes: ['enumMultiLang', 'multiEnumMultiLang'],

        setup() {
            Dep.prototype.setup.call(this);

            this.setDisableMultiLang();

            this.listenTo(this.model, 'change:type', function () {
                if (this.model.get('type') === 'arrayMultiLang') {
                    this.resetValue();
                }
                this.setDisableMultiLang();
                this.reRender();
            }, this);
        },

        setDisableMultiLang() {
            this.disableMultiLang = !this.multiLangFieldTypes.includes(this.model.get('type'));
        },

        getLangFieldNameList() {
            return this.disableMultiLang ? [] : this.langFieldNameList;
        },

        resetValue() {
            let data = {};
            data[this.name] = null;
            this.langFieldNameList.forEach(lang => data[lang] = null);
            this.model.set(data);
        }

    })
);

