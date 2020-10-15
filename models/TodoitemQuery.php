<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Todoitem]].
 *
 * @see Todoitem
 */
class TodoitemQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Todoitem[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Todoitem|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
