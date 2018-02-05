Espo.define('treo-crm:views/progress-manager/actions/close', 'treo-crm:views/progress-manager/actions/request',
    Dep => Dep.extend({

        url: 'ProgressManager/:id/close',

        requestType: 'PUT',

        buttonLabel: 'close',

    })
);

