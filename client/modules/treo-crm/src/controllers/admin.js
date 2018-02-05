Espo.define('treo-crm:controllers/admin', 'class-replace!treo-crm:controllers/admin', function (Dep) {
    return Dep.extend({
        error404: function () {
            this.entire('views/base', {template: 'errors/404'}, function (view) {
                view.render();
            });
        },
        currency: function () {
            // blocking page
            this.error404();
        },
        extensions: function (options) {
            // blocking page
            this.error404();
        },
    });

});
