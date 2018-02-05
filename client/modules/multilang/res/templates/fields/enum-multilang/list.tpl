{{#if isNotEmpty}}<div>{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}</div>{{/if}}
{{#each valueList}}
    {{#if isNotEmpty}}
    <div>
        <label class="control-label" data-name="{{name}}">
            <span class="label-text">{{shortLang}}:</span>
        </label>
        <span>{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}</span>
    </div>
    {{/if}}
{{/each}}