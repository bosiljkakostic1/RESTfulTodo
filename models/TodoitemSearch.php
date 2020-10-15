<?php
namespace app\models;

use yii\base\Model;

class TodoitemSearch extends Model
{
    public $done;
    public $id;
    public $action;
    public function rules()
    {
        return [
            ['id', 'integer'],
            ['done', 'boolean'],
            ['action', 'string'],  
            ['done, id, action', 'safe'],
        ];
    }
}
