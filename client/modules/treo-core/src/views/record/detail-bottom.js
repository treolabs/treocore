/*
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

Espo.define('treo-core:views/record/detail-bottom', 'class-replace!treo-core:views/record/detail-bottom', function (Dep) {

    return Dep.extend({

        setupPanels: function () {},

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
                    "sticked": false,
                    "hidden": !streamAllowed,
                    "order": 2
                });
            }
        },

        setup: function () {
            this.type = this.mode;
            if ('type' in this.options) {
                this.type = this.options.type;
            }

            this.panelList = [];

            this.setupPanels();

            this.wait(true);

            Promise.all([
                new Promise(function (resolve) {
                    if (this.relationshipPanels) {
                        this.loadRelationshipsLayout(function () {
                            resolve();
                        });
                    } else {
                        resolve();
                    }
                }.bind(this))
            ]).then(function () {
                this.panelList = this.panelList.filter(function (p) {
                    if (p.aclScope) {
                        if (!this.getAcl().checkScope(p.aclScope)) {
                            return;
                        }
                    }
                    return true;
                }, this);

                if (this.relationshipPanels) {
                    this.setupRelationshipPanels();
                }

                this.panelList = this.panelList.map(function (p) {
                    var item = Espo.Utils.clone(p);
                    if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                        item.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                    } else {
                        this.recordHelper.setPanelStateParam(p.name, item.hidden || false);
                    }
                    return item;
                }, this);

                if (this.streamPanel && this.getMetadata().get('scopes.' + this.scope + '.stream') && !this.getConfig().get('isStreamSide')) {
                    this.setupStreamPanel();
                }

                this.setupPanelViews();
                this.wait(false);

            }.bind(this));
        },

        setupRelationshipPanels: function () {
            let scope = this.scope;

            let scopesDefs = this.getMetadata().get('scopes') || {};

            let panelList = this.relationshipsLayout;

            panelList.forEach(function (item) {
                let p;
                if (typeof item === 'string' || item instanceof String) {
                    p = {name: item};
                } else {
                    p = Espo.Utils.clone(item || {});
                }
                if (!p.name) {
                    return;
                }

                let name = p.name;

                let links = (this.model.defs || {}).links || {};
                let bottomPanels = this.getMetadata().get(['clientDefs', this.scope, 'bottomPanels', 'detail']) || [];
                let bottomPanelOptions = bottomPanels.find(panel => panel.name === name);
                if (!(name in links) && !bottomPanelOptions) {
                    return;
                }

                let defs = this.getMetadata().get('clientDefs.' + scope + '.relationshipPanels.' + name) || {};
                if (bottomPanelOptions) {
                    defs = bottomPanelOptions;
                }
                defs = Espo.Utils.clone(defs);

                if (defs.aclScopesList) {
                    if (!defs.aclScopesList.every(item => this.getAcl().checkScope(item, 'read'))) {
                        return;
                    }
                } else {
                    let foreignScope = (links[name] || {}).entity;
                    if ((scopesDefs[foreignScope] || {}).disabled) return;
                    if (foreignScope && !this.getAcl().check(foreignScope, 'read')) {
                        return;
                    }
                }

                for (let i in defs) {
                    if (i in p) continue;
                    p[i] = defs[i];
                }

                if (!p.view) {
                    p.view = bottomPanelOptions ? 'views/record/panels/bottom' : 'views/record/panels/relationship';
                }

                p.order = 5;

                if (this.recordHelper.getPanelStateParam(p.name, 'hidden') !== null) {
                    p.hidden = this.recordHelper.getPanelStateParam(p.name, 'hidden');
                } else {
                    this.recordHelper.setPanelStateParam(p.name, p.hidden || false);
                }

                this.panelList.push(p);
            }, this);
        },
    });
});


