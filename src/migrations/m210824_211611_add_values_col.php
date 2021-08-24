<?php

namespace venveo\oauthclient\migrations;

use Craft;
use craft\db\Migration;

/**
 * m210824_211611_add_values_col migration.
 */
class m210824_211611_add_values_col extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%oauthclient_tokens}}', 'values', $this->text()->after('refreshToken'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210824_211611_add_values_col cannot be reverted.\n";
        return false;
    }
}
