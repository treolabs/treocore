Espo.define('treo-crm:views/site/header', 'class-replace!treo-crm:views/site/header', function (Dep) {

    return Dep.extend({

        title: 'TreoCRM',

        setup: function () {
            this.navbarView = this.getMetadata().get('app.clientDefs.navbarView') || this.navbarView;

            Dep.prototype.setup.call(this);
        }

    });

});


