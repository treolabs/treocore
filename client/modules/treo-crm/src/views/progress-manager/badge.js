Espo.define('treo-crm:views/progress-manager/badge', 'view',
    Dep => Dep.extend({

        isPanelShowed: false,

        template: 'treo-crm:progress-manager/badge',

        progressCheckInterval: 2,

        events: {
            'click a[data-action="showProgress"]': function (e) {
                this.showProgress();
            },
        },

        setup() {
            this.progressCheckInterval = this.getConfig().get('progressCheckInterval') || this.progressCheckInterval;

            this.listenToOnce(this, 'after:render', () => {
                this.initProgressShowInterval();
            });
        },

        initProgressShowInterval() {
            window.setInterval(() => {
                if (!this.isPanelShowed) {
                    this.ajaxGetRequest('ProgressManager/isShowPopup', {})
                        .then(response => {
                            if (response && !this.isPanelShowed) {
                                this.showProgress();
                            }
                        });
                } else if (this.hasView('panel') && !this.isProgressModalShowed()) {
                    this.getView('panel').reloadList();
                }
            }, 1000 * this.progressCheckInterval);
        },

        showProgress() {
            this.closeProgress();
            this.isPanelShowed = true;

            this.createView('panel', 'treo-crm:views/progress-manager/panel', {
                el: `${this.options.el} .progress-panel-container`
            }, function (view) {
                view.render();
            }.bind(this));

            $(document).on('mouseup.progress', function (e) {
                let container = this.$el.find('.progress-panel-container');
                if (!container.is(e.target) && container.has(e.target).length === 0) {
                    this.closeProgress();
                }
            }.bind(this));
        },

        closeProgress() {
            this.isPanelShowed = false;

            if (this.hasView('panel')) {
                this.getView('panel').remove();
            };

            $(document).off('mouseup.progress');
        },

        isProgressModalShowed() {
            return $(document).find('.progress-modal').length;
        }

    })
);
