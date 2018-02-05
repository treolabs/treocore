Espo.define('treo-crm:views/progress-manager/actions/cancel', 'treo-crm:views/progress-manager/actions/request',
    Dep => Dep.extend({

        url: 'ProgressManager/:id/cancel',

        requestType: 'PUT',

        buttonLabel: 'cancel',

    })
);

