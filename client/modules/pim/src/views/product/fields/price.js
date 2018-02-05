Espo.define('pim:views/product/fields/price', 'views/fields/currency',
    Dep => Dep.extend({

        setup() {
            Dep.prototype.setup.call(this);

            let currencyRates = this.getConfig().get('currencyRates') || [];
            let defaultCurrency = this.getConfig().get('defaultCurrency');
            this.currencyList = this.currencyList.filter(item => (item in currencyRates) || item === defaultCurrency);
        }

    })
);