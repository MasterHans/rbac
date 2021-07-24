<?php

namespace tests\unit\fixtures;

use yii\test\ActiveFixture;

class PostFixture extends ActiveFixture
{
    public $modelClass = 'app\models\PostForBehavior';
    public $dataFile = '@tests/unit/fixtures/data/postforbehavior.php';
}