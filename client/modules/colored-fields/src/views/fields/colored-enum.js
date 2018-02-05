Espo.define('colored-fields:views/fields/colored-enum', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        listTemplate: 'colored-fields:fields/colored-enum/list',

        detailTemplate: 'colored-fields:fields/colored-enum/list',

        editTemplate: 'colored-fields:fields/colored-enum/edit',

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                let that = this;
                this.$el.find(`select[name="${this.name}"]`).on('change', function () {
                    let color = (that.model.getFieldParam(that.name, 'optionColors') || {})[$(this).val()];
                    $(this).css({background: `#${color}`});
                });
            }
        },

        data() {
            let data = Dep.prototype.data.call(this);
            let optionColors = this.model.getFieldParam(this.name, 'optionColors') || {};
            data.options = (data.params.options || []).map(item => {
                return {
                    selected: item === data.value,
                    color: optionColors[item],
                    value: item
                };
            });
            data.color = (this.model.getFieldParam(this.name, 'optionColors') || {})[data.value];
            return data;
        }
    });

});
