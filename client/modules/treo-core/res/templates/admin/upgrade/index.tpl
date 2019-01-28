<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default">
    <div class="panel-body">
        {{#unless alreadyUpdated}}
        <div class="current-status">
            <span>{{translate 'Current version' category='labels' scope='Global'}}: {{systemVersion}}</span>
        </div>
        <div class="row">
            <div class="cell form-group col-xs-12 col-sm-6 col-md-4 col-lg-3" data-name="versionToUpgrade">
                <label class="control-label" data-name="versionToUpgrade">{{{translate 'versionToUpgrade' category='labels' scope='Admin'}}}</label>
                <div class="field" data-name="versionToUpgrade">
                    {{{versionToUpgrade}}}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="button-container">
                    <button class="btn btn-primary" data-action="validateSystem">
                        {{translate 'Upgrade' category='labels' scope='Admin'}}
                    </button>
                    <button class="btn btn-primary hidden" data-action="upgradeSystem">
                        {{translate 'Apply' category='labels' scope='Global'}}
                    </button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="spinner hidden">
                    <div class="bounce1"></div>
                    <div class="bounce2"></div>
                    <div class="bounce3"></div>
                </div>
                <span class="progress-status"></span>
            </div>
        </div>
        {{else}}
        <div class="current-status">
            <span class="text-success">{{translate 'systemAlreadyUpgraded' category='messages' scope='Admin'}}</span>
        </div>
        {{/unless}}
    </div>
</div>
