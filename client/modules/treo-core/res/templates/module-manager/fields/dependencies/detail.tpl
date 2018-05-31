<table class="table">
{{#each value}}
<tr>
    <td>{{version}}</td>
    <td>
        {{#each require}}
        <div>{{name}}: {{version}}</div>
        {{/each}}
    </td>
</tr>
{{/each}}
</table>