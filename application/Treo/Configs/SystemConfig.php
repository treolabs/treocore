<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types = 1);

namespace Treo\Configs;

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
