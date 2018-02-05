<select name="{{name}}" class="form-control main-element" style="color: #fff; background: #{{color}};">
    {{#each options}}
    <option style="color: #fff; background: #{{color}};" value="{{value}}" {{#if selected}}selected{{/if}}>{{translateOption value scope=../scope field=../name translatedOptions=../translatedOptions}}</option>
    {{/each}}
</select>
