<?php

namespace tests\codeception\unit;

use app\models\PostForBehavior;
use Codeception\Test\Unit;
use tests\unit\fixtures\PostFixture;

//use yii\codeception\DbTestCase;

class MarkdownBehaviorTest extends Unit
{
    public function testNewModelSave()
    {
        $post = new PostForBehavior();
        $post->title = 'Title';
        $post->content_markdown = 'New *markdown* text';
        $this->assertTrue($post->save());
        $this->assertEquals("<p>New <em>markdown</em> text</p>\n", $post->content_html);
    }

    public function testExistingModelSave()
    {
        $post = PostForBehavior::findOne(16);
        $post->content_markdown = 'Other *markdown* text';
        $this->assertTrue($post->save());
        $this->assertEquals("<p>Other <em>markdown</em> text</p>\n", $post->content_html);
    }

    public function fixtures()
    {
        return [
            'posts' => [
                'class' => PostFixture::className(),
            ]
        ];
    }
}