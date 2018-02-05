Espo.define('treo-crm:views/record/detail-side', 'class-replace!treo-crm:views/record/detail-side', function (Dep) {

    return Dep.extend({

        streamPanel: true,

        defaultPanelDefs: {
            name: 'default',
            label: false,
            view: 'views/record/panels/default-side',
            options: {
                fieldList: [
                    {
                        name: 'ownerUser',
                        view: 'views/fields/user-with-avatar'
                    },
                    {
                        name: 'assignedUser',
                        view: 'views/fields/assigned-user'
                    },
                    {
                        name: 'teams',
                        view: 'views/fields/teams'
                    }
                ]
            }
        },

        setupStreamPanel: function () {
            var streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);
            if (streamAllowed === null) {
                this.listenToOnce(this.model, 'sync', function () {
                    streamAllowed = this.getAcl().checkModel(this.model, 'stream', true);
                    if (streamAllowed) {
                        this.showPanel('stream', function () {
                            this.getView('stream').collection.fetch();
                        });
                    }
                }, this);
            }
            if (streamAllowed !== false) {
                this.panelList.push({
                    "name":"stream",
                    "label":"Stream",
                    "view":"views/stream/panel",
                    "hidden": !streamAllowed
                });
            }
        },

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            if (this.defaultPanel) {
                this.setupDefaultPanel();
            }

            this.setupPanels();

            var additionalPanels = this.getMetadata().get('clientDefs.' + this.scope + '.sidePanels.' + this.type) || [];
            additionalPanels.forEach(function (panel) {
                this.panelList.push(panel);
            }, this);

            this.panelList = this.panelList.filter(function (p) {
                if (p.aclScope) {
                    if (!this.getAcl().checkScope(p.aclScope)) {
                        return;
                    }
                }
                return true;
            }, this);

            this.panelList = this.panelList.map(function (p) {
                var item = Espo.Utils.clone(p);
                if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                    item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                } else {
                    this.recordHelper.setPanelStateParam(p.name, item.hidden || false);
                }
                return item;
            }, this);

            this.wait(true);
            this.getHelper().layoutManager.get(this.scope, 'sidePanels' + Espo.Utils.upperCaseFirst(this.type), function (layoutData) {
                if (layoutData) {
                    this.alterPanels(layoutData);
                }

                if (this.streamPanel && this.getMetadata().get('scopes.' + this.scope + '.stream') && this.getConfig().get('isStreamSide') && !this.model.isNew()) {
                    this.setupStreamPanel();
                }

                this.setupPanelViews();
                this.wait(false);
            }.bind(this));
        },

    });
});

