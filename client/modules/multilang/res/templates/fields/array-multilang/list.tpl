{{#unless isEmpty}}{{{value}}}{{else}}{{translate 'None'}}{{/unless}}
{{#each valueList}}
    <div>
        <label class="control-label" data-name="{{name}}">
            <span class="label-text">{{shortLang}}:</span>
        </label>
        <span>{{#unless isEmpty}}{{{value}}}{{else}}{{translate 'None'}}{{/unless}}</span>
    </div>
{{/each}}