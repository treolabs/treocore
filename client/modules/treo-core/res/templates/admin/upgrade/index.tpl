<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default upload">
    <div class="panel-body">
        <p class="current-status"></p>
        <div class="row">
            <div class="col-sm-4 col-xs-12">
                {{#unless disableUpgrade}}
                <div class="row">
                    <div class="cell form-group col-sm-12 col-xs-12" data-name="versionToUpgrade">
                        <label class="control-label" data-name="versionToUpgrade">{{translate 'versionToUpgrade' category='labels' scope='Admin'}}</label>
                        <div class="field" data-name="versionToUpgrade">
                            {{{versionToUpgrade}}}
                        </div>
                    </div>
                </div>
                {{/unless}}
                <div class="row">
                    <div class="col-sm-12 col-xs-12">
                        <button class="btn btn-primary {{#if disableUpgrade}}hidden{{/if}}" data-action="upgradeSystem">
                            {{translate 'Upgrade' category='labels' scope='Admin'}}
                        </button>
                        <div class="loader {{#if hideLoader}}hidden{{/if}}"></div>
                    </div>
                </div>
            </div>
	        {{#unless disableUpgrade}}
            <div class="col-sm-8 col-xs-12">
                <div class="list-container">{{{list}}}</div>
            </div>
	        {{/unless}}
        </div>
    </div>
</div>
