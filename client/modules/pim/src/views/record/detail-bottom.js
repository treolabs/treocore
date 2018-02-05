Espo.define('pim:views/record/detail-bottom', 'views/record/detail-bottom',
    Dep => Dep.extend({

        setupPanelViews() {
            this.setupOptionalPanels();
            this.checkAclScopes();
            this.sortPanelList();
            Dep.prototype.setupPanelViews.call(this);
        },

        setupOptionalPanels() {
            let optionalPanels = this.getMetadata().get(`clientDefs.${this.scope}.optionalBottomPanels`) || {};

            this.panelList = this.panelList.filter(panel => {
                if (panel.name in optionalPanels) {
                    return optionalPanels[panel.name].every(condition => this.model.get(condition.field) === condition.value);
                }
                return true;
            });
        },

        checkAclScopes() {
            this.panelList = this.panelList.filter(panel => (panel.aclScopesList || []).every(item => this.getAcl().checkScope(item, 'read')));
        },

        sortPanelList() {
            this.panelList.forEach((item, index) => item.index = index);
            this.panelList.sort((a, b) => (((a.order || 0) - (b.order || 0)) || (a.index - b.index)));
        },
    })
);
