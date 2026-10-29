<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * Form model for verifying an OTP code and logging in the user.
 */
class OtpVerifyForm extends Model
{
    public string $mobile = '';
    public string $otpCode = '';

    public function rules(): array
    {
        return [
            [['mobile', 'otpCode'], 'required'],
            [['mobile'], 'string', 'max' => 15],
            [['mobile'], 'match', 'pattern' => '/^09\d{9}$/', 'message' => 'شماره موبایل نامعتبر است.'],
            [['otpCode'], 'string', 'max' => 10],
            [['otpCode'], 'validateOtpCode'],
        ];
    }

    /**
     * Validates the OTP code against the stored code for this user.
     */
    public function validateOtpCode(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = User::findByMobile($this->mobile);

        if ($user === null) {
            $this->addError('mobile', 'کاربری با این شماره موبایل یافت نشد.');
            return;
        }

        if ($user->isOtpExpired()) {
            $this->addError($attribute, 'کد تأیید منقضی شده است. لطفاً کد جدید درخواست کنید.');
            return;
        }

        if (!$user->isOtpValid($this->otpCode)) {
            $this->addError($attribute, 'کد تأیید نامعتبر است.');
        }
    }

    /**
     * Verify OTP and authenticate the user. Returns the access token on success.
     */
    public function verify(): array|false
    {
        if (!$this->validate()) {
            return false;
        }

        $user = User::findByMobile($this->mobile);

        // Generate access token for API auth
        $user->generateAccessToken();
        $user->is_verified = true;
        $user->clearOtp();

        if (!$user->save()) {
            $this->addErrors($user->errors);
            return false;
        }

        // Login via session for web compatibility
        Yii::$app->user->login($user, 3600 * 24 * 30);

        return [
            'access_token' => $user->access_token,
            'mobile' => $user->mobile,
            'is_verified' => $user->is_verified,
        ];
    }
}
