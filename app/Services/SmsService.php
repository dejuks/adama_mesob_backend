<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    private string $baseUrl;
    private string $token;
    private string $senderId;

    public function __construct()
    {
        $this->baseUrl = env('DAGU_SMS_BASE_URL', '');
        $this->token = env('DAGU_SMS_TOKEN', '');
        $this->senderId = env('DAGU_SMS_SENDER_ID', '9141');

        if (!$this->baseUrl || !$this->token) {
            throw new \Exception("SMS configuration missing in .env");
        }
    }

    public function sendByPhone(string $phone, string $message)
    {
        return $this->sendSms($phone, $message);
    }

    public function sendOtp(string $phone)
    {
        $otp = rand(100000, 999999);

        $message = "Your OTP code is: {$otp}";

        return [
            'phone' => $phone,
            'otp' => $otp,
            'response' => $this->sendSms($phone, $message),
        ];
    }

    public function sendBulk(array $phones, string $message)
    {
        $results = [];

        foreach ($phones as $phone) {
            $results[] = $this->sendSms($phone, $message);
        }

        return $results;
    }

    private function sendSms(string $phone, string $message)
{
    try {
        $response = Http::timeout(30)->post(
            'http://196.190.213.33/api/sms/send-phone',
            [
                'phone' => $phone,
                'message' => $message,
            ]
        );

        return [
            'status' => 'success',
            'http_status' => $response->status(),
            'body' => $response->body(), // ⭐ IMPORTANT FIX
            'json' => $response->json(), // may be null but safe
        ];

    } catch (\Exception $e) {
        return [
            'status' => 'failed',
            'error' => $e->getMessage(),
        ];
    }
}
}