Espo.define('colored-fields:views/fields/colored-multi-enum', 'views/fields/multi-enum', function (Dep) {

    return Dep.extend({

        listTemplate: 'colored-fields:fields/colored-multi-enum/detail',

        detailTemplate: 'colored-fields:fields/colored-multi-enum/detail',

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode == 'edit') {
                this.setColors();
                this.$element.on('change', this.setColors.bind(this));
            }
        },

        setColors() {
            let value = this.$element.val();
            let values = value.split(':,:');
            if (values.length) {
                let optionColors = this.model.getFieldParam(this.name, 'optionColors') || {};
                values.forEach(item => this.$el.find(`[data-value='${item}']`).css({ 'background': `#${optionColors[item]}`, 'color': '#fff'}));
            }
        },

        data() {
            let data = Dep.prototype.data.call(this);
            let optionColors = this.model.getFieldParam(this.name, 'optionColors') || {};
            data.selectedValues = (data.selected || []).map(item => {
                return {
                    color: optionColors[item],
                    value: item
                };
            });
            data.color = (this.model.getFieldParam(this.name, 'optionColors') || {})[data.value];
            return data;
        }

    });
});
