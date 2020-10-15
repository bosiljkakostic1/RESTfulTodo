<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "todoitem".
 *
 * @property int $id
 * @property string|null $action
 * @property string|null $start_date
 * @property string|null $end_date
 * @property bool|null $done
 */
class Todoitem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'todoitem';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['start_date', 'end_date'], 'safe'],
            [['done'], 'boolean'],
            [['action'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'action' => 'Action',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'done' => 'Done',
        ];
    }

    /**
     * {@inheritdoc}
     * @return TodoitemQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TodoitemQuery(get_called_class());
    }
    public static function primaryKey()
    {
        return ["id"];
    }
}
