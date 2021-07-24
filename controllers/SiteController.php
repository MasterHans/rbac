<?php

namespace app\controllers;

use app\models\Article;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\AccessControl;
use yii\helpers\VarDumper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionTest()
    {
        $article = new Article();

        $article->name = 'День Святого Валентина приближается ? Вот дерьмо!Я забыл снова завести девушку!';
        $article->description = 'Бендер злится на Фрая за то, что он встречается с роботом. Держись подальше от наших женщин. У тебя металлическая лихорадка, парень. Лихорадка металла';

        // $event - объект класса yii\base\Event или дочернего класса
        $article->on(ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
            $followers = ['john2@teleworm.us',
                'shivawhite@cuvox.de',
                'kate@dayrep.com'
            ];

            foreach ($followers as $follower) {
                Yii::$app->mailer->compose()
                    ->setFrom('techblog@teleworm.us')
                    ->setTo($follower)
                    ->setSubject($event->sender->name)
                    ->setTextBody($event->sender->description)
                    ->send();
            }
            \yii\helpers\VarDumper::dump('Email sent successfuly!', 10, true);
        });
        if (!$article->save()) {
            echo VarDumper::dumpAsString($article->getErrors());
        };
    }

    public function actionTestNew()
    {
        $article = new Article();
        $article->name = 'Valentine\'s Day\'s coming? Aw crap! I forgot to get a girlfriend again!';
        $article->description = 'Bender is angry at Fry for dating a robot. Stay away from our women. You\'ve got metal fever, boy . Metal fever';
        // $event is an object of yii\base\Event or a child class
        $article->on(Article::EVENT_OUR_CUSTOM_EVENT, function ($event) {
            $followers = ['john2@teleworm.us',
                'shivawhite@cuvox.de',
                'kate@dayrep.com'];
            foreach ($followers as $follower) {
                Yii::$app->mailer->compose()
                    ->setFrom('techblog@teleworm.us')
                    ->setTo($follower)
                    ->setSubject($event->sender->name)
                    ->setTextBody($event->sender->description)
                    ->send();
            }
            echo 'Emails have been sent';
        });
        if ($article->save()) {
            $article->trigger(Article::EVENT_OUR_CUSTOM_EVENT);
        }
    }


}
