Espo.define('pim:views/product/record/panels/product-images', 'views/record/panels/relationship',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this.collection, 'sync', () => {
                this.model.trigger('updateProductImage');
            });
        }

    })
);
