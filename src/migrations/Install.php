<?php
/**
 * OAuth 2.0 Client plugin for Craft CMS 3.x
 *
 * Simple OAuth 2.0 client
 *
 * @link      https://venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\oauthclient\migrations;

use Craft;
use craft\db\Migration;

/**
 * @author    Venveo
 * @package   Oauth20Client
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%oauthclient_tokens}}');
        if ($tableSchema === null) {
            $this->createTable(
                '{{%oauthclient_tokens}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'userId' => $this->integer()->notNull(),
                    'appId' => $this->integer()->notNull(),
                    'accessToken' => $this->text()->notNull(),
                    'refreshToken' => $this->text(),
                    'expiryDate' => $this->dateTime(),
                ]
            );
        }

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%oauthclient_apps}}');
        if ($tableSchema === null) {
            $this->createTable(
                '{{%oauthclient_apps}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                    'userId' => $this->integer(),
                    'name' => $this->string(255)->notNull(),
                    'handle' => $this->string(255)->notNull(),
                    'provider' => $this->string(255)->notNull(),
                    'clientId' => $this->text(),
                    'clientSecret' => $this->text(),
                    'urlAuthorize' => $this->text(),
                    'scopes' => $this->text(),
                ]
            );
        }

        return true;
    }

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName(
                '{{%oauthclient_apps}}',
                'handle',
                true
            ),
            '{{%oauthclient_apps}}',
            'handle',
            true
        );

        $this->createIndex(
            $this->db->getIndexName(
                '{{%oauthclient_apps}}',
                'provider',
                false
            ),
            '{{%oauthclient_apps}}',
            'provider',
            false
        );
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oauthclient_tokens}}', 'appId'),
            '{{%oauthclient_tokens}}',
            'appId',
            '{{%oauthclient_apps}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oauthclient_tokens}}', 'userId'),
            '{{%oauthclient_tokens}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oauthclient_apps}}', 'userId'),
            '{{%oauthclient_apps}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%oauthclient_tokens}}');
        $this->dropTableIfExists('{{%oauthclient_apps}}');
    }
}
