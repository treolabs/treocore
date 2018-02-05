Espo.define('treo-crm:views/record/panel-navigation', 'view',
    Dep => Dep.extend({

        template: 'treo-crm:record/panel-navigation',

        panelList: [],

        events: {
            'click [data-action="scrollToPanel"]'(e) {
                let target = e.currentTarget;
                this.actionScrollToPanel($(target).data('name'));
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.panelList = this.options.panelList || this.panelList;
        },

        data() {
            return {
                panelList: this.panelList
            };
        },

        actionScrollToPanel(name) {
            if (!name) {
                return;
            }
            let offset = this.getParentView().$el.find(`.panel[data-name="${name}"]`).offset();
            let navbarHeight = $('#navbar .navbar-right').height() || 0;
            $(window).scrollTop(offset.top - navbarHeight);
        }

    })
);