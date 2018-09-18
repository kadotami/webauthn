<?php

use yii\db\Migration;

/**
 * Class m180713_031439_user_table
 */
class m180713_031439_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('user', [
            'id' => $this->primaryKey(),
            'rpid' => $this->string(),
            'email' => $this->string(),
            'credential_id' => $this->string(),
            'publickey' => $this->text(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180713_031439_user_table cannot be reverted.\n";
        $this->dropTable('user');
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180713_031439_user_table cannot be reverted.\n";

        return false;
    }
    */
}
