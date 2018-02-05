Espo.define('treo-crm:controllers/about', 'controllers/base', function (Dep) {
    return Dep.extend({
        defaultAction: 'about',
        about: function () {
            this.error404();
        }
    });

});
