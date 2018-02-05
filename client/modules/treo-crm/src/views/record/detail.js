Espo.define('treo-crm:views/record/detail', 'class-replace!treo-crm:views/record/detail', function (Dep) {

    return Dep.extend({

        template: 'treo-crm:record/detail',

        panelNavigationView: 'treo-crm:views/record/panel-navigation',

        createBottomView: function () {
            var el = this.options.el || '#' + (this.id);
            this.createView('bottom', this.bottomView, {
                model: this.model,
                scope: this.scope,
                el: el + ' .bottom',
                readOnly: this.readOnly,
                type: this.type,
                inlineEditDisabled: this.inlineEditDisabled,
                recordHelper: this.recordHelper,
                recordViewObject: this
            }, view => {
                this.listenToOnce(view, 'after:render', () => {
                    this.createPanelNavigationView(view.panelList);
                })
            });
        },

        createPanelNavigationView(panelList) {
            let el = this.options.el || '#' + (this.id);
            this.createView('panelNavigation', this.panelNavigationView, {
                panelList: panelList,
                model: this.model,
                scope: this.scope,
                el: el + ' .panel-navigation',
            }, function (view) {
                view.render();
            });
        }

    });

});

