<?php

namespace Puz\MailgunWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;

class MailgunSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signatureData = $request->get('signature', []);
        $timestamp = $signatureData['timestamp'];
        $token = $signatureData['token'];
        $signature = $signatureData['signature'];

        if (empty($timestamp) || empty($token) || empty($signature)) {
            return false;
        }

        $hmac = hash_hmac('sha256', $timestamp.$token, $config->signingSecret);

        return hash_equals($hmac, $signature);
    }
}
