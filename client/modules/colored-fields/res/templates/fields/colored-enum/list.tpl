{{#if isNotEmpty}}
<span class="label" style="background:#{{color}};">{{translateOption value scope=scope field=name translatedOptions=translatedOptions}}</span>
{{else}}
{{translate 'None'}}
{{/if}}
