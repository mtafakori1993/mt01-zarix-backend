<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the users table with OTP authentication support.
 */
class m250711_000001_create_users_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'mobile' => $this->string(15)->notNull()->unique(),
            'username' => $this->string(64)->null()->unique(),
            'password_hash' => $this->string(255)->null(),
            'auth_key' => $this->string(64)->notNull(),
            'access_token' => $this->string(64)->null()->unique(),
            'otp_code' => $this->string(10)->null(),
            'otp_expires_at' => $this->integer()->null()->comment('Unix timestamp when OTP expires'),
            'is_verified' => $this->boolean()->notNull()->defaultValue(false),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_users_mobile', '{{%users}}', 'mobile');
        $this->createIndex('idx_users_access_token', '{{%users}}', 'access_token');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%users}}');
    }
}
