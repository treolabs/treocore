Espo.define('treo-crm:views/site/navbar', 'class-replace!treo-crm:views/site/navbar', function (Dep) {

    return Dep.extend({

        init() {
            Dep.prototype.init.call(this);

            this.listenToOnce(this, 'after:render', () => {
                this.initProgressBadge();
            });
        },

        initProgressBadge() {
            this.$el.find('.notifications-badge-container').before('<li class="dropdown progress-badge-container"></li>');
            this.createView('progressBadge', 'treo-crm:views/progress-manager/badge', {
                el: `${this.options.el} .progress-badge-container`
            }, view => {
                view.render();
            });
        },

        getMenuDefs: function () {
            var menuDefs = [
                {
                    link: '#Preferences',
                    label: this.getLanguage().translate('Preferences')
                }
            ];

            if (!this.getConfig().get('actionHistoryDisabled')) {
                menuDefs.push({
                    divider: true
                });
                menuDefs.push({
                    action: 'showLastViewed',
                    link: '#LastViewed',
                    label: this.getLanguage().translate('LastViewed', 'scopeNamesPlural')
                });
            }

            menuDefs = menuDefs.concat([
                {
                    divider: true
                },
                {
                    link: '#clearCache',
                    label: this.getLanguage().translate('Clear Local Cache')
                },
                {
                    divider: true
                },
                {
                    link: '#logout',
                    label: this.getLanguage().translate('Log Out')
                }
            ]);

            if (this.getUser().isAdmin()) {
                menuDefs.unshift({
                    link: '#Admin',
                    label: this.getLanguage().translate('Administration')
                });
            }
            return menuDefs;
        },

        setupTabDefsList: function () {
            var tabDefsList = [];
            var moreIsMet = false;;
            this.tabList.forEach(function (tab, i) {
                if (tab === '_delimiter_') {
                    moreIsMet = true;
                    return;
                }
                if (typeof tab === 'object') {
                    return;
                }
                var label = this.getLanguage().translate(tab, 'scopeNamesPlural');
                var o = {
                    link: '#' + tab,
                    label: label,
                    shortLabel: label.substr(0, 2),
                    name: tab,
                    isInMore: moreIsMet
                };
                tabDefsList.push(o);
            }, this);
            this.tabDefsList = tabDefsList;
        },

    });

});


