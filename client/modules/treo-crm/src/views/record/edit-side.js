Espo.define('treo-crm:views/record/edit-side', 'class-replace!treo-crm:views/record/edit-side', function (Dep) {

    return Dep.extend({

        defaultPanelDefs: {
            name: 'default',
            label: false,
            view: 'views/record/panels/side',
            options: {
                fieldList: [
                    {
                        name: 'ownerUser',
                        view: 'views/fields/user-with-avatar'
                    },
                    {
                        name: 'assignedUser',
                        view: 'views/fields/assigned-user'
                    },
                    {
                        name: 'teams',
                        view: 'views/fields/teams'
                    }
                ]
            }
        },

    });
});


