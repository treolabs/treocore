<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Configs;

return [
    'scheduledJobs'         => [
        [
            'scheduling' => '0 0 * * *',
            'service'    => 'RestApiDocs',
            'method'     => 'generateDocumentation',
            'name'       => 'Generate REST API documentation',
            'data'       => []
        ],
        [
            'scheduling' => '* * * * *',
            'service'    => 'ProgressManager',
            'method'     => 'executeProgressJobs',
            'name'       => 'Execute progress manager jobs',
            'data'       => []
        ]
    ],
    'scheduledJobsServices' => [
    // array of services
    ]
];
