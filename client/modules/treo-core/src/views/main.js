Espo.define('treo-core:views/main', 'class-replace!treo-core:views/main', function (Dep) {

    return Dep.extend({

        buildHeaderHtml: function (arr) {
            var a = [];
            arr.forEach(function (item) {
                a.push('<div class="pull-left">' + item + '</div>');
            }, this);

            return '<div class="clearfix header-breadcrumbs">' + a.join('<div class="pull-left breadcrumb-separator"> &rsaquo; </div>') + '</div>';
        },

    });
});