{{#unless isEmpty}}
{{#each selectedValues}}
<span class="label" style="background:#{{color}};">{{translateOption value scope=../scope field=../name translatedOptions=../translatedOptions}}</span>
{{/each}}
{{else}}{{translate 'None'}}{{/unless}}