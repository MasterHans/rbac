<?php

namespace app\models\query;

/**
 * This is the ActiveQuery class for [[\app\models\PostForBehavior]].
 *
 * @see \app\models\PostForBehavior
 */
class PostForBehaviorQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return \app\models\PostForBehavior[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return \app\models\PostForBehavior|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
