Espo.define('pim:views/product/fields/image', 'pim:views/fields/full-width-list-image',
    Dep => Dep.extend({

        urlImage: null,

        imageId: null,

        imageName: null,

        sizeImage:{
            'x-small': [64, 64],
            'small': [128, 128],
            'medium': [256, 256],
            'large': [512, 512]
        },

        setup() {
            Dep.prototype.setup.call(this);
            this.getMainImage();


            this.events['click a[data-action="showRemoteImagePreview"]'] = (e) => {
                e.preventDefault();

                var url = this.urlImage;
                this.createView('preview', 'pim:views/modals/remote-image-preview', {
                    url: url,
                    model: this.model,
                    name: this.model.get(this.nameName)
                }, function (view) {
                    view.render();
                });
            };

            this.listenTo(this.model, 'updateProductImage', () => {
                this.getMainImage();
            });

        },
        getMainImage() {
            if (this.model.id) {
                this.ajaxGetRequest(`Product/${this.model.id}/productImages`, {
                    where: [{
                        type: 'isTrue',
                        attribute: 'isMain'
                    }]
                })
                    .then(data => {
                        if (data.list.length) {
                            this.urlImage = data.list[0].imageLink;
                            this.imageId = data.list[0].imageId;
                            this.imageName = data.list[0].imageName;
                            this.reRender();
                        }
                    })
            }
        },
        getValueForDisplay() {
            let imageSize = [];

            if (this.sizeImage.hasOwnProperty(this.previewSize)) {
                imageSize = this.sizeImage[this.previewSize]
            } else {
                imageSize = this.sizeImage['small']
            }

            if (!this.imageId && this.urlImage && this.showPreview) {
                return `<div class="attachment-preview"><a data-action="showRemoteImagePreview" href="${this.urlImage}"><img src="${this.urlImage}" style="max-width:${imageSize[0]}px; max-height:${imageSize[1]}px;"></a></div>`;
            } else {
                this.model.set({
                    [this.idName]: this.imageId,
                    [this.nameName]: this.imageName
                });
                return Dep.prototype.getValueForDisplay.call(this);
            }
        }

    })
);