<?php

namespace app\controllers;

use app\models\User;
use stdClass;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Html;
use yii\web\Controller;

/**
 * Class RbacController.
 */
class RbacController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['deletePost'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['test'],
                    ],
                ],
            ],
        ];
    }

    public function actionDelete()
    {
        return $this->renderContent(Html::tag('h1', 'Post deleted.'));
    }

    /**
     * @param $description
     * @param $rule
     * @param array $params
     *
     * @return string
     */
    protected function renderAccess($description, $rule, $params = [])
    {
        $access = Yii::$app->user->can($rule, $params);
        return $description . ': ' . ($access ? 'yes' : 'no');
    }

    public function actionTest()
    {
        $post = new stdClass();
        $post->createdBy = User::findByUsername('demo')->id;
        return $this->renderContent(
            Html::tag('h1', 'Current permissions') .
            Html::ul([
                $this->renderAccess('Use can create post', 'createPost'),
                $this->renderAccess('Use can read post', 'readPost'),
                $this->renderAccess('Use can update post', 'updatePost'),
                $this->renderAccess('Use can own update post', 'updateOwnPost', [
                    'post' => $post,
                ]),
                $this->renderAccess('Use can delete post', 'deletePost'),
            ])
        );
    }
}