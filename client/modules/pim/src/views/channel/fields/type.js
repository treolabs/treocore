Espo.define('pim:views/channel/fields/type', 'views/fields/enum',
    Dep => Dep.extend({

        data() {
            return _.extend({
                optionList: this.model.options || []
            }, Dep.prototype.data.call(this));
        },

        setupOptions() {
            var channelType = Espo.Utils.clone(this.getConfig().get('modules').Pim.channelType);
            var typeName = {};
            this.params.options = [];
            for (var type in channelType) {
                this.params.options.push(type)
                typeName[type] = channelType[type].name;
            }
            this.translatedOptions = Espo.Utils.clone(this.getLanguage().translate('type', 'options', 'Channel') || {});
            // Add default name if not exist translate
            if(typeof this.translatedOptions !== 'object') {
                this.translatedOptions = typeName;
            }
        }

    })
);
