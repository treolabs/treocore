Espo.define('treo-crm:views/site/footer', 'class-replace!treo-crm:views/site/footer', function (Dep) {

    return Dep.extend({

        template: 'treo-crm:site/footer',

        data() {
            let version = this.getConfig().get('version');
            return {
                version: version ? `v.${version}` : '',
                year: moment().format("YYYY")
            }
        }

    });

});


