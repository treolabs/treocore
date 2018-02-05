<input name="{{name}}" type="text">
{{#each valueList}}
    <label class="control-label" data-name="{{name}}">
        <span class="label-text">{{#if customLabel}}{{customLabel}}{{else}}{{translate ../../name category='fields' scope=../../scope}}{{/if}} &rsaquo; {{shortLang}}</span>
        <span class="required-sign"> *</span>
    </label>
    <input name="{{name}}" type="text">
{{/each}}