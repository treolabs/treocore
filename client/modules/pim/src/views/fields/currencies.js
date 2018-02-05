Espo.define('pim:views/fields/currencies', 'views/fields/multi-enum',
    Dep => Dep.extend({

        setupOptions() {
            this.params.options = Espo.Utils.clone(this.getConfig().get('currencyList')) || []
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                let baseCurrency = this.getConfig().get('baseCurrency');
                if (!this.selected.includes(baseCurrency)) {
                    this.selected.unshift(baseCurrency);
                    this.reRender();
                }

                this.$element[0].selectize.settings.onDelete = item => item[0] !== baseCurrency;
            }
        }

    })
);
