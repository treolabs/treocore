<div class="page-header"><h3><a href="#Admin">{{translate 'Administration'}}</a> &raquo {{translate 'Upgrade' scope='Admin'}}</h3></div>

<div class="panel panel-default upload">
    <div class="panel-body">
        <p class="current-status"></p>
        <button class="btn btn-primary {{#if disableUpgrade}}hidden{{/if}}" data-action="upgradeSystem">
            {{translate 'upgradeTo' category='labels' scope='Admin'}}&nbsp;{{latestVersion}}
        </button>
        <div class="loader {{#if hideLoader}}hidden{{/if}}"></div>
    </div>
</div>
