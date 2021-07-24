# Поведения в Yii2
## Определение
Поведения (behaviors) — это экземпляры класса yii\base\Behavior или класса, унаследованного от него.
Поведения, также известные как примеси, позволяют расширять функциональность существующих компонентов без необходимости
изменения дерева наследования. После прикрепления поведения к компоненту, его методы и свойства "внедряются" 
в компонент, и становятся доступными так же, как если бы они были объявлены в самом классе компонента. 
Кроме того, поведение может реагировать на события, создаваемые компонентом, что позволяет тонко 
настраивать или модифицировать обычное выполнение кода компонента.


### Примечание
В время разработки в моделях скапливается очень много методов, которые не удобно листать.
Нужно пересадить методы в отдельные классы компонентов или хелперов.
Но может оказаться что в моделе вызывается очень много хелперов.

Здесь мы сталкиваемся с проблемой множественного наследования.
В PHP множественное наследование отсутствует.

Как вариант можно использовать трейты

trait FileTrait 
{
    public function beforeSave($insert) 
    {
        if (parent::beforeSave($insert)){
        }
    }
    
    public function getFileUrl()
    {
    }

    public function getFilePath()
    {
    }
}


Но проблема в том что трейты создаются статически на этапе компиляции,
поэтому parent::beforeSave() не сработает. Так же методы с одинаковыми именами в разных трейтах
вызовут ошибку.

### Поведения в Yii2

Все поведения хранятся в массиве $_behaviors класса Component фреймворка
С помощью магических методов __GET и __CALL Эти методы извлекаются и подмешиваются к экземпляру 
класса в котором подключено поведение.


    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canGetProperty($name)) {
                return $behavior->$name;
            }
        }

        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

 
    public function __call($name, $params)
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $object) {
            if ($object->hasMethod($name)) {
                return call_user_func_array([$object, $name], $params);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }
    
    
Метод $this->ensureBehaviors(); заполняет массив $_behaviors из массива прописанного
в служебном методе
 
    public function behaviors()
    {
        return [
         .........
        ]
    }
    
Прикручиваем поведение к модели Post:
    
    class Post extends ActiveRecord
    {
        public function behaviors()
        {
            return [
                'class' => 'app\components\FileBehavior',
                'attribute' => 'image'
                'path' => 'upload/post'
            ]
        }
    }
    
Теперь можно делать любые наборы методов для реализации какого либо поведения в различных 
ситуациях и оформлять их как отдельные классы.

Если подключить их как показано выше к любому компоненту, то их свойства 
и методы станут доступны через магические иетоды (__call, __set, __get)
     
    
Подключим конвертацию синтаксиса из html в MarkDown через поведение:


    class MarkdownBehavior extends Behavior
    {
        public $sourceAttribute;
        public $targetAttribute;
    
        public function init()
        {
            if (empty($this->sourceAttribute) || empty($this->targetAttribute)) {
                throw new InvalidConfigException('Source and target must be set.');
            }
            parent::init();
        }
    
        public function events()
        {
            return [
                ActiveRecord::EVENT_BEFORE_INSERT => 'onBeforeSave',
                ActiveRecord::EVENT_BEFORE_UPDATE => 'onBeforeSave',
            ];
        }
    
        public function onBeforeSave(Event $event)
        {
            if ($this->owner->isAttributeChanged($this->sourceAttribute)) {
                $this->processContent();
            }
        }
    
        private function processContent()
        {
            $model = $this->owner;
            $source = $model->{$this->sourceAttribute};
            $model->{$this->targetAttribute} = Markdown::process($source);
        }
    }

В методе events() описываем события которые подключаются в attach и отключаются в detach

Прикрутим это дело к модели PostForBehavior

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
        
Теперь большие кучи кода из модели можно вынести в класс поведений, подключаемых и настраеваемых.
Модели "чистые" теперь. События срабатывают всё хорошо.


#### Поведение можно подключить так же и в контроллере:

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
        
Здесь значок @ говорит о том что logout возможен только для авторозиванного пользователя.


так же можно ограничить действия на странице для пользователей

                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],                
                
экшены index, view - только для авторезованных пользователей -- @.


#### Подключаем динамически поведение:

    
    $model = new Post();
    
    $model->attachBehavior('fileBehavior', [
        'class' => 'app\components\FileBehavior',
        'attribute' => 'image',
        'path' => 'upload/post',
    });
    
    
    $behavior = new app\behaviors\MarkdownBehavior();
    $behavior->fromBehavior = 'text';
    $behavior->toBehavior = 'text_html';
    
    $model->attachBehavior('markdownBehavior', $behavior);
    $model->detachBehavior('markdownBehavior');
    
#### Подключаем поведение через конфигурацию.

    class LastLoginBehavior extends Behavior
    {
        $attribute = 'logged_at';
        
        public function events() 
        {
            return [
                \yii\web\User::EVENT_AFTER_LOGIN => 'onAfterLogin',
            ];
        }
        
        public function onAfterLogin(\yii\web\UserEvent $event) {
            /** @var app\models\User $user **/
            $user = $event->identity;
            $user->updateAttributes([$this->attribute => time()]);
        }
        
    }



    $config = [
       .........
        'components' => [

            'user' => [
                'identityClass' => 'app\models\User',
                'enableAutoLogin' => true,
                'as lastlogin' => [
                    'class' => 'app\components\LastLoginBehavior',
                    'attribute' => 'logged_at',
                ],
            ],
       .........    
                        
#### Через контейнер

    Yii::$container->set('yii\web\Controller', [
        'as myControllerBehavior' => [
            ....
        ],
    ]);                        

#### Встроенное поведение в Yii2
Поведение, которое позволяет автоматически обновлять атрибуты с метками времени при сохранении Active Record 
моделей через insert(), update() или save().

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // если вместо метки времени UNIX используется datetime:
                // 'value' => new Expression('NOW()'),
            ],
        ];
    }                            