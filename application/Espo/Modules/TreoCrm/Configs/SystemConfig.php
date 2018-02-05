<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Configs;

return [
    'defaultPermissions'            => [
        'dir'   => '0775',
        'file'  => '0664',
        'user'  => '',
        'group' => '',
    ],
    'permissionMap'                 => [
        /** ['0664', '0775'] */
        'writable' => [
            'apidocs',
            'data',
            'custom',
        ],
        /** ['0644', '0755'] */
        'readable' => [
            'api',
            'application',
            'client',
            'vendor',
            'index.php',
            'cron.php',
            'rebuild.php',
            'main.html',
            'reset.html',
        ],
    ],
    'cron'                          => [
        /** Max number of jobs per one execution. */
        'maxJobNumber'     => 15,
        /** Max execution time (in seconds] allocated for a sinle job. If exceeded then set to Failed. */
        'jobPeriod'        => 7800,
        /** Min time (in seconds] between two cron runs. */
        'minExecutionTime' => 30,
        /** Attempts to re-run failed jobs. */
        'attempts'         => 2
    ],
    'crud'                          => [
        'get'    => 'read',
        'post'   => 'create',
        'put'    => 'update',
        'patch'  => 'patch',
        'delete' => 'delete',
    ],
    'systemUser'                    => [
        'id'        => 'system',
        'userName'  => 'system',
        'firstName' => '',
        'lastName'  => 'System',
    ],
    'systemItems'                   =>
    [
        'systemItems',
        'adminItems',
        'configPath',
        'cachePath',
        'database',
        'crud',
        'logger',
        'isInstalled',
        'defaultPermissions',
        'systemUser',
        'permissionMap',
        'permissionRules',
        'passwordSalt',
        'cryptKey',
        'restrictedMode',
        'userLimit',
        'portalUserLimit',
        'stylesheet',
        'userItems',
        'internalSmtpServer',
        'internalSmtpPort',
        'internalSmtpAuth',
        'internalSmtpUsername',
        'internalSmtpPassword',
        'internalSmtpSecurity',
        'internalOutboundEmailFromAddress'
    ],
    'adminItems'                    =>
    [
        'devMode',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'cron',
        'authenticationMethod',
        'adminPanelIframeUrl',
        'ldapHost',
        'ldapPort',
        'ldapSecurity',
        'ldapAuth',
        'ldapUsername',
        'ldapPassword',
        'ldapBindRequiresDn',
        'ldapBaseDn',
        'ldapUserLoginFilter',
        'ldapAccountCanonicalForm',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapAccountFilterFormat',
        'ldapTryUsernameSplit',
        'ldapOptReferrals',
        'ldapCreateEspoUser',
        'ldapAccountDomainName',
        'ldapAccountDomainNameShort',
        'ldapUserNameAttribute',
        'ldapUserFirstNameAttribute',
        'ldapUserLastNameAttribute',
        'ldapUserTitleAttribute',
        'ldapUserEmailAddressAttribute',
        'ldapUserPhoneNumberAttribute',
        'ldapUserObjectClass',
        'maxEmailAccountCount',
        'massEmailMaxPerHourCount',
        'personalEmailMaxPortionSize',
        'inboundEmailMaxPortionSize',
        'authTokenLifetime',
        'authTokenMaxIdleTime',
        'ldapUserDefaultTeamId',
        'ldapUserDefaultTeamName',
        'ldapUserTeamsIds',
        'ldapUserTeamsNames',
        'cleanupJobPeriod',
        'cleanupActionHistoryPeriod'
    ],
    'userItems'                     =>
    [
        'outboundEmailFromAddress',
        'outboundEmailFromName',
        'integrations',
        'googleMapsApiKey'
    ],
    'isInstalled'                   => false,
    'ldapUserNameAttribute'         => 'sAMAccountName',
    'ldapUserFirstNameAttribute'    => 'givenName',
    'ldapUserLastNameAttribute'     => 'sn',
    'ldapUserTitleAttribute'        => 'title',
    'ldapUserEmailAddressAttribute' => 'mail',
    'ldapUserPhoneNumberAttribute'  => 'telephoneNumber',
    'ldapUserObjectClass'           => 'person',
];
