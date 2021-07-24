<?php

namespace app\models;

use app\behaviors\MarkdownBehavior;
use Yii;

/**
 * This is the model class for table "post_for_behavior".
 *
 * @property int $id
 * @property string $title
 * @property string|null $content_markdown
 * @property string|null $content_html
 */
class PostForBehavior extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'post_for_behavior';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['content_markdown', 'content_html'], 'string'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    public function behaviors()
    {
        return [
            'markdown' => [
                'class' => MarkdownBehavior::class,
                'sourceAttribute' => 'content_markdown',
                'targetAttribute' => 'content_html',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'content_markdown' => Yii::t('app', 'Content Markdown'),
            'content_html' => Yii::t('app', 'Content Html'),
        ];
    }

    /**
     * {@inheritdoc}
     * @return \app\models\query\PostForBehaviorQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \app\models\query\PostForBehaviorQuery(get_called_class());
    }
}
