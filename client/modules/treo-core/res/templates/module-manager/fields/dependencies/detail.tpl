<table class="table">
    <thead>
    <tr>
        <th>{{translate 'version' scope='ModuleManager' category='labels'}}</th>
        <th>{{translate 'dependencies' scope='ModuleManager' category='labels'}}</th>
    </tr>
    </thead>
    <tbody>
    {{#each value}}
    <tr>
        <td>{{version}}</td>
        <td>
            {{#each require}}
            <div>{{@key}}: {{this}}</div>
            {{/each}}
        </td>
    </tr>
    {{/each}}
    </tbody>
</table>