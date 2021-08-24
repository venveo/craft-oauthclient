<?php

namespace venveo\oauthclient\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210824_211045_user_not_required migration.
 */
class m210824_211045_user_not_required extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Tokens can exist without user ids
        $this->alterColumn('{{%oauthclient_tokens}}', 'userId', $this->integer()->null());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210824_211045_user_not_required cannot be reverted.\n";
        return false;
    }
}
