<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use yii\base\BaseObject;

/**
 * Ghasedak SMS service for sending OTP messages.
 *
 * API docs: https://gateway.ghasedak.me/rest/api/v1/webservice/sendotpsms
 * Panel: https://panel.ghasedak.me
 */
class GhasedakService extends BaseObject
{
    private string $apiKey;
    private string $baseUrl;
    private string $templateName;

    public function __construct($config = [])
    {
        $this->apiKey = $_ENV['GHASEDAK_API_KEY'] ?? getenv('GHASEDAK_API_KEY') ?: '';
        $this->baseUrl = $_ENV['GHASEDAK_BASE_URL'] ?? getenv('GHASEDAK_BASE_URL') ?: 'https://gateway.ghasedak.me/rest/api/v1/webservice';
        $this->templateName = $_ENV['GHASEDAK_TEMPLATE_NAME'] ?? getenv('GHASEDAK_TEMPLATE_NAME') ?: 'Ghasedak';
        parent::__construct($config);
    }

    /**
     * Send OTP SMS to a single receptor.
     *
     * @param string $mobile 10-digit mobile number (09xxxxxxxxx)
     * @param string $otpCode The OTP code to send
     * @return bool Whether the SMS was sent successfully
     */
    public function sendOtp(string $mobile, string $otpCode): bool
    {
        if ($this->apiKey === '') {
            Yii::error('Ghasedak API key is empty. Check GHASEDAK_API_KEY in .env.', 'ghasedak');
            return false;
        }

        $url = $this->baseUrl . '/sendotpsms';

        $payload = [
            'receptors' => [
                [
                    'mobile' => $mobile,
                    'clientReferenceId' => (string) time(),
                ],
            ],
            'templateName' => $this->templateName,
            'inputs' => [
                ['param' => 'Code', 'value' => $otpCode],
            ],
            'isVoice' => false,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'ApiKey: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            Yii::error("Ghasedak cURL error: {$curlError}", 'ghasedak');
            return false;
        }

        if ($httpCode !== 200) {
            Yii::error("Ghasedak HTTP {$httpCode}: {$response}", 'ghasedak');
            return false;
        }

        $result = json_decode($response, true);

        if (empty($result['isSuccess'])) {
            $message = $result['message'] ?? 'Unknown error';
            Yii::error("Ghasedak API error: {$message}", 'ghasedak');
            return false;
        }

        Yii::info("OTP sent to {$mobile}, cost: {$result['data']['totalCost']}", 'ghasedak');
        return true;
    }
}
