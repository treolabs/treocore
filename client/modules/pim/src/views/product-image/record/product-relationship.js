Espo.define('pim:views/product-image/record/product-relationship', 'views/record/list',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'after:save', function () {
                this.collection.fetch();
            }, this);
        }

    })
);
