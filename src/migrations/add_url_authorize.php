<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * add_url_authorize migration.
 */
class add_url_authorize extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%oauthclient_apps}}', 'urlAuthorize', $this->text());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn('{{%oauthclient_apps}}', 'urlAuthorize');
    }
}
