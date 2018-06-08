<ul class="nav nav-pills">
{{#each panelList}}
    <li style="margin-left: 0">
        <a href="javascript:" data-action="scrollToPanel" data-name="{{name}}">{{title}}</a>
    </li>
{{/each}}
</ul>