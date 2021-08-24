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

use craft\db\Migration;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    /**
     * @return bool
     */
    protected function createTables()
    {
        $this->createTable(
            '{{%oauthclient_tokens}}',
            [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(),
                'appId' => $this->integer()->notNull(),
                'accessToken' => $this->text()->notNull(),
                'refreshToken' => $this->text(),
                'values' => $this->text(),
                'expiryDate' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]
        );
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

    // Protected Methods
    // =========================================================================

    /**
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(
            $this->db->getIndexName('{{%oauthclient_apps}}', 'handle', true),
            '{{%oauthclient_apps}}',
            'handle',
            true
        );

        $this->createIndex(
            $this->db->getIndexName('{{%oauthclient_apps}}', 'provider', false),
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
            $this->db->getForeignKeyName('{{%oauthclient_tokens}}', 'appId'), '{{%oauthclient_tokens}}', 'appId',
            '{{%oauthclient_apps}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oauthclient_tokens}}', 'userId'), '{{%oauthclient_tokens}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%oauthclient_apps}}', 'userId'), '{{%oauthclient_apps}}',
            'userId',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->removeTables();

        return true;
    }

    protected function removeTables()
    {
        $this->dropTableIfExists('{{%oauthclient_tokens}}');
        $this->dropTableIfExists('{{%oauthclient_apps}}');
    }
}
