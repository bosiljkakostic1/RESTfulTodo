<?php
namespace app\models;

use yii\base\Model;

class ProductsSearch extends Model
{
    public $category;
    public function rules()
    {
        return [
            ['category', 'string', 'min' => 2, 'max' => 200],
        ];
    }
}
