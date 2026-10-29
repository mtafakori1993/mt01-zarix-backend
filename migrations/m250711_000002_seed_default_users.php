<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Seeds the default admin and demo users from the legacy static User model.
 */
class m250711_000002_seed_default_users extends Migration
{
    public function safeUp(): void
    {
        $now = time();

        $this->batchInsert('{{%users}}',
            ['mobile', 'username', 'password_hash', 'auth_key', 'access_token', 'is_verified', 'is_active', 'created_at', 'updated_at'],
            [
                [
                    '09000000100',
                    'admin',
                    '$2y$13$gYAywKSkhfZDq9FLNdm7buKnvlRxDexf5xipSMAxQPDUxpaptmZJu', // password: admin
                    'test100key',
                    '100-token',
                    true,
                    true,
                    $now,
                    $now,
                ],
                [
                    '09000000101',
                    'demo',
                    '$2y$13$alRLq1PGVMlGYwS/Y3iy3ewQns1Z8ol8Iq6Zb5k7ZwEhblA1aL29y', // password: demo
                    'test101key',
                    '101-token',
                    true,
                    true,
                    $now,
                    $now,
                ],
            ]
        );
    }

    public function safeDown(): void
    {
        $this->delete('{{%users}}', ['username' => ['admin', 'demo']]);
    }
}
