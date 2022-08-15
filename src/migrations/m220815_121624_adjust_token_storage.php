<?php

namespace venveo\oauthclient\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m220815_121624_adjust_token_storage migration.
 */
class m220815_121624_adjust_token_storage extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->dropOldestTokens();
        $this->dropForeignKeyIfExists('{{%oauthclient_apps}}', 'userId');
        $this->dropColumn('{{%oauthclient_apps}}', 'userId');
        $this->createIndex(null, '{{%oauthclient_tokens}}', ['userId', 'appId'], true);
        return true;
    }

    protected function dropOldestTokens(): void
    {
        $query = (new Query())->select('id')
            ->from(['t1' => '{{%oauthclient_tokens}}'])
            ->innerJoin(
                [
                    't2' => (new Query())->select(['MAX(id) as lastId', 'userId', 'appId'])
                        ->from('{{%oauthclient_tokens}}')
                        ->groupBy(['userId', 'appId'])
                        ->having('COUNT(*) > 1')
                ],
                '[[t2.userId]] = [[t1.userId]] AND [[t1.appId]] = [[t2.appId]]'
            )
            ->where('[[t1.id]] < [[t2.lastId]]');
        $ids = $query->column($this->db);

        $this->delete('{{%oauthclient_tokens}}', ['IN', 'id', $ids]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220815_121624_adjust_token_storage cannot be reverted.\n";
        return false;
    }
}
