<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Services\Email as BaseEmail;

/**
 * Class Email
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Email extends BaseEmail
{
    /**
     * Send test email
     *
     * @param array $data
     *
     * @return bool
     */
    public function sendTestEmail($data): bool
    {
        $email = $this->getEntityManager()->getEntity('Email');
        // get subject
        $subject = $this
            ->getEntityManager()
            ->getContainer()
            ->get('language')
            ->translate('testEmailSubject', 'messages', 'Email');

        $email->set(array(
            'subject' => $subject,
            'isHtml' => false,
            'to' => $data['emailAddress']
        ));

        $emailSender = $this->getEntityManager()->getContainer()->get('mailSender');
        $emailSender->useSmtp($data)->send($email);

        return true;
    }
}
