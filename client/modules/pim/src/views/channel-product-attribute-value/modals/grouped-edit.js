Espo.define('pim:views/channel-product-attribute-value/modals/grouped-edit', 'views/modals/edit',
    Dep => Dep.extend({

        template: 'pim:channel-product-attribute-value/modals/grouped-edit',

        fullFormDisabled: true,

        setup() {
            this.buttonList = [];

            if ('saveDisabled' in this.options) {
                this.saveDisabled = this.options.saveDisabled;
            }

            if (!this.saveDisabled) {
                this.buttonList.push({
                    name: 'save',
                    label: 'Save',
                    style: 'primary',
                });
            }

            this.fullFormDisabled = this.options.fullFormDisabled || this.fullFormDisabled;

            this.layoutName = this.options.layoutName || this.layoutName;

            if (!this.fullFormDisabled) {
                this.buttonList.push({
                    name: 'fullForm',
                    label: 'Full Form'
                });
            }

            this.buttonList.push({
                name: 'cancel',
                label: 'Cancel'
            });

            this.scope = this.scope || this.options.scope;
            this.id = this.options.id;

            if (!this.id) {
                this.header = this.getLanguage().translate('Create ' + this.scope, 'labels', this.scope);
            } else {
                this.header = this.getLanguage().translate('Edit');
                this.header += ': ' + this.getLanguage().translate(this.scope, 'scopeNames');
            }

            if (!this.fullFormDisabled) {
                if (!this.id) {
                    this.header = '<a href="#' + this.scope + '/create" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                } else {
                    this.header = '<a href="#' + this.scope + '/edit/' + this.id+'" class="action" title="'+this.translate('Full Form')+'" data-action="fullForm">' + this.header + '</a>';
                }
            }
        },

        data() {
            return {
                attributeName: this.model.get('attributeName')
            };
        },

        afterRender() {
            this.createRecordView();
        },

        createRecordView() {
            let view = this.getFieldManager().getViewName(this.model.getFieldType('attributeValue'));
            let options = {
                mode: 'edit',
                inlineEditDisabled: true,
                model: this.model,
                el: this.options.el + ' .field[data-name="attributeValue"]',
                customLabel: this.model.get('attributeName'),
                defs: {
                    name: 'attributeValue',
                }
            };
            this.createView('attributeValue', view, options, view => {
                view.render();
            });
        },

        actionSave() {
            let $buttons = this.dialog.$el.find('.modal-footer button');
            $buttons.addClass('disabled').attr('disabled', 'disabled');

            let attributeView = this.getView('attributeValue');

            if (attributeView.validate()) {
                return;
            }

            let newData = {};
            let data = attributeView.fetch();
            for (let key in data) {
                newData[Espo.utils.lowerCaseFirst(key.replace('attribute', ''))] = data[key];
            }

            this.ajaxPatchRequest(`ChannelProductAttributeValue/${this.model.id}`, newData)
                .then(response => {
                    $buttons.removeClass('disabled').removeAttr('disabled');
                    this.model.set(data);
                    this.getParentView().reRender();
                    this.dialog.close();
                }, reason => {
                    $buttons.removeClass('disabled').removeAttr('disabled');
                });
        }

    })
);

