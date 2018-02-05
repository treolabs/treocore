<div>{{#if isNotEmpty}}{{value}}{{else}}{{translate 'None'}}{{/if}}</div>
{{#each valueList}}
    <div>
        <label class="control-label" data-name="{{name}}">
            <span class="label-text">{{shortLang}}:</span>
        </label>
        <span>{{#if isNotEmpty}}{{value}}{{else}}{{translate 'None'}}{{/if}}</span>
    </div>
{{/each}}
