Espo.define('pim:views/channel/fields/locales', 'views/fields/multi-enum',
    Dep => Dep.extend({

        data() {
            return _.extend({
                optionList: this.model.options || []
            }, Dep.prototype.data.call(this));
        },

        setupOptions() {
            this.params.options = Espo.Utils.clone(this.getConfig().get('languageList'));
            this.translatedOptions = Espo.Utils.clone(this.getLanguage().translate('language', 'options') || {});
        }

    })
);
