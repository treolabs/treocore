<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default upgrade-panel">
    <div class="panel-body">
        <div class="row">
            <div class="current-status"><span></span></div>
            <div class="loader {{#if hideLoader}}hidden{{/if}}"></div>
        </div>
        <div class="row">
            {{#if availableVersions}}
            <div class="list-container versions">{{{list}}}</div>
            {{else}}
            {{translate 'noAvailableVersions' category='labels' scope='Versions'}}
            {{/if}}
        </div>
    </div>
</div>
