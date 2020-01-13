<?php

namespace venveo\oauthclient\migrations;

use Craft;
use craft\db\Migration;

/**
 * m200113_014806_add_url_authorize migration.
 */
class m200113_014806_add_url_authorize extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%oauthclient_apps}}', 'urlAuthorize', $this->text()->after('clientSecret'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%oauthclient_apps}}', 'urlAuthorize');
    }
}
