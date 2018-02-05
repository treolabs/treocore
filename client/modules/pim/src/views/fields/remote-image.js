Espo.define('pim:views/fields/remote-image', 'pim:views/fields/full-width-list-image',
    Dep => Dep.extend({

        urlField: null,

        sizeImage:{
            'x-small': [64, 64],
            'small': [128, 128],
            'medium': [256, 256],
            'large': [512, 512]
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.events['click a[data-action="showRemoteImagePreview"]'] = (e) => {
                e.preventDefault();

                var url = this.model.get(this.urlField);
                this.createView('preview', 'pim:views/modals/remote-image-preview', {
                    url: url,
                    model: this.model,
                    name: this.model.get(this.nameName)
                }, function (view) {
                    view.render();
                });
            };
        },

        getValueForDisplay() {
            let imageSize = [];
            let id = this.model.get(this.idName);
            let url = this.model.get(this.urlField);
            if (this.sizeImage.hasOwnProperty(this.previewSize)) {
                imageSize = this.sizeImage[this.previewSize]
            } else {
                imageSize = this.sizeImage['small']
            }

            if (!id && url && this.showPreview) {
                return `<div class="attachment-preview"><a data-action="showRemoteImagePreview" href="${url}"><img src="${url}" style="max-width:${imageSize[0]}px; max-height:${imageSize[1]}px;"></a></div>`;
            } else {
                return Dep.prototype.getValueForDisplay.call(this);
            }
        }

    })
);