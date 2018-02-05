<select name="{{name}}" class="form-control main-element">
    {{options params.options value scope=scope field=name translatedOptions=translatedOptions}}
</select>
{{#each valueList}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
        <span class="required-sign"> *</span>
    </label>
    <select name="{{name}}" class="form-control main-element">
        {{options params.options value scope=scope field=name translatedOptions=translatedOptions}}
    </select>
{{/each}}