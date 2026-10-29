<?php

declare(strict_types=1);

namespace app\models;

use app\services\GhasedakService;
use Yii;
use yii\base\Model;

/**
 * Form model for requesting an OTP code via SMS.
 */
class OtpRequestForm extends Model
{
    public string $mobile = '';

    public function __construct(
        private readonly GhasedakService $ghasedak,
        $config = [],
    ) {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['mobile'], 'required'],
            [['mobile'], 'string', 'max' => 15],
            [['mobile'], 'match', 'pattern' => '/^09\d{9}$/', 'message' => 'شماره موبایل نامعتبر است.'],
            [['mobile'], 'validateSendWindow'],
        ];
    }

    /**
     * Rate-limit: reject if last OTP was sent less than 2 minutes ago.
     */
    public function validateSendWindow(string $attribute): void
    {
        $user = User::findByMobile($this->mobile);
        if ($user !== null && $user->otp_expires_at !== null) {
            $ttl = (int) ($_ENV['OTP_TTL'] ?? getenv('OTP_TTL') ?: 120);
            $lastSentAt = $user->otp_expires_at - $ttl;
            $elapsed = time() - $lastSentAt;

            if ($elapsed < 120) {
                $wait = 120 - $elapsed;
                $this->addError($attribute, "لطفاً {$wait} ثانیه دیگر صبر کنید.");
            }
        }
    }

    /**
     * Generate and send OTP code to the given mobile number.
     */
    public function send(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $otpLength = (int) ($_ENV['OTP_LENGTH'] ?? getenv('OTP_LENGTH') ?: 4);
        $otpTtl = (int) ($_ENV['OTP_TTL'] ?? getenv('OTP_TTL') ?: 120);
        $otpCode = random_int(10 ** ($otpLength - 1), (10 ** $otpLength) - 1);
        $otpCode = (string) $otpCode;

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Find or create user
            $user = User::findByMobile($this->mobile);
            if ($user === null) {
                $user = new User();
                $user->mobile = $this->mobile;
                $user->is_active = true;
            }

            $user->otp_code = $otpCode;
            $user->otp_expires_at = time() + $otpTtl;
            $user->generateAuthKey();

            if (!$user->save()) {
                $transaction->rollBack();
                $this->addErrors($user->errors);
                return false;
            }

            // Send OTP via Ghasedak
            $sent = $this->ghasedak->sendOtp($this->mobile, $otpCode);

            if (!$sent) {
                $transaction->rollBack();
                $this->addError('mobile', 'خطا در ارسال کد تأیید. لطفاً دوباره تلاش کنید.');
                return false;
            }

            $transaction->commit();
            return true;

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error("OTP send failed: {$e->getMessage()}", 'auth');
            $this->addError('mobile', 'خطای سیستمی. لطفاً دوباره تلاش کنید.');
            return false;
        }
    }
}
