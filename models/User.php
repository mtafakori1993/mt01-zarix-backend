<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User identity model backed by database.
 *
 * @property int $id
 * @property string $mobile
 * @property string|null $username
 * @property string|null $password_hash
 * @property string $auth_key
 * @property string|null $access_token
 * @property string|null $otp_code
 * @property int|null $otp_expires_at
 * @property bool $is_verified
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%users}}';
    }

    public function rules(): array
    {
        return [
            [['mobile', 'auth_key'], 'required'],
            [['mobile'], 'string', 'max' => 15],
            [['mobile'], 'match', 'pattern' => '/^09\d{9}$/', 'message' => 'شماره موبایل نامعتبر است.'],
            [['username'], 'string', 'max' => 64],
            [['password_hash', 'access_token'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 64],
            [['otp_code'], 'string', 'max' => 10],
            [['otp_expires_at'], 'integer'],
            [['is_verified', 'is_active'], 'boolean'],
            [['mobile'], 'unique'],
            [['username'], 'unique'],
            [['access_token'], 'unique'],
        ];
    }

    // ── IdentityInterface ───────────────────────────────────────────

    public static function findIdentity($id): static|null
    {
        return static::findOne(['id' => $id, 'is_active' => true]);
    }

    public static function findIdentityByAccessToken($token, $type = null): static|null
    {
        return static::findOne(['access_token' => $token, 'is_active' => true]);
    }

    public function getId(): int|string
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey(): string|null
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    // ── Finders ─────────────────────────────────────────────────────

    public static function findByMobile(string $mobile): static|null
    {
        return static::findOne(['mobile' => $mobile, 'is_active' => true]);
    }

    public static function findByUsername(string $username): static|null
    {
        return static::findOne(['username' => $username, 'is_active' => true]);
    }

    // ── OTP helpers ──────────────────────────────────────────────────

    public function isOtpValid(string $code): bool
    {
        if ($this->otp_code === null || $this->otp_expires_at === null) {
            return false;
        }

        return $this->otp_code === $code && $this->otp_expires_at > time();
    }

    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at === null || $this->otp_expires_at <= time();
    }

    public function clearOtp(): void
    {
        $this->otp_code = null;
        $this->otp_expires_at = null;
    }

    /**
     * Generates "remember me" authentication key.
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates access token for API authentication.
     */
    public function generateAccessToken(): void
    {
        $this->access_token = Yii::$app->security->generateRandomString(64);
    }

    // ── Lifecycle ────────────────────────────────────────────────────

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
            if (empty($this->auth_key)) {
                $this->generateAuthKey();
            }
        }
        $this->updated_at = $now;

        return true;
    }
}
