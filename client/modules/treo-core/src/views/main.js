Espo.define('treo-core:views/main', 'class-replace!treo-core:views/main', function (Dep) {

    return Dep.extend({

        buildHeaderHtml: function (arr) {
            var a = [];
            arr.forEach(function (item) {
                a.push('<span>' + item + '</span>');
            }, this);

            return '<div class="header-breadcrumbs">' + a.join('<span class="breadcrumb-separator"> &rsaquo; </span>') + '</div>';
        },

    });
});