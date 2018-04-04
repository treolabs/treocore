{{#each errorList}}
<div class="panel-group">
    <div class="panel panel-danger">
        <div class="panel-heading">
            <div class="panel-title" style="text-transform: none;">
                {{{errorMessage}}}
                <a href="javascript:" data-toggle="collapse" data-target='[data-name="{{name}}"]' class="btn-link pull-right">{{translate 'Show more'}}</a>
            </div>
        </div>
        <div data-name="{{name}}" class="panel-collapse collapse">
            <div class="panel-body">
                <pre class="message message-container" style="margin: 10px 0 0 0;">{{message}}</pre>
            </div>
        </div>
    </div>
</div>
{{/each}}