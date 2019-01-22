<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default">
    <div class="panel-body">
        <p class="current-status"></p>
        {{#unless disableUpgrade}}
        <div class="row">
            <div class="cell form-group col-xs-12 col-sm-6 col-md-4 col-lg-3" data-name="versionToUpgrade">
                <label class="control-label" data-name="versionToUpgrade">{{{translate 'versionToUpgrade' category='labels' scope='Admin'}}}</label>
                <div class="field" data-name="versionToUpgrade">
                    {{{versionToUpgrade}}}
                </div>
            </div>
        </div>
        {{/unless}}
        <div class="row">
            <div class="col-sm-6">
                <button class="btn btn-primary {{#if disableUpgrade}}hidden{{/if}}" data-action="upgradeSystem">
                    {{translate 'Upgrade' category='labels' scope='Admin'}}
                </button>
                <div class="loader {{#if hideLoader}}hidden{{/if}}"></div>
            </div>
        </div>
    </div>
</div>
