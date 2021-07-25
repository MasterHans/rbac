# RBAC в Yii2

## Примечание
Аутентификация — процедура проверки подлинности, например проверка подлинности пользователя путем сравнения введенного
им пароля с паролем, сохраненным в базе данных. 
Авторизация — предоставление определенному лицу или группе лиц прав
на выполнение определенных действий.

## Фильтры контроля доступа (ACF)
Подключаются через поведение. ACF проверяет набор правил доступа, чтобы убедиться, что пользователь имеет доступ 
к запрошенному действию.

    [
        'allow' => true,
        'actions' => ['login', 'signup'],
        'roles' => ['?'],
    ],
    [
        'allow' => true,
        'actions' => ['logout'],
        'roles' => ['@'],
    ],

? - гость
@ - авторизованный пользователь
Использование других имён ролей будет приводить к вызову метода yii\web\User::can(), который требует включения RBAC
(будет описано дальше). Если свойство пустое или не задано, то правило применяется ко всем ролям.
actions - список действий

 - Разрешить всем гостям (ещё не прошедшим авторизацию) доступ к действиям login и signup.
   Опция roles содержит знак вопроса ?, это специальный токен обозначающий "гостя".
 - Разрешить аутентифицированным пользователям доступ к действию logout. Символ @ — это другой специальный токен,
   обозначающий аутентифицированного пользователя.

ACF привязываем к пользователю

    public function behaviors()
    {
        $userName = !empty(Yii::$app->user->getId()) ? User::findIdentity(Yii::$app->user->getId())->username : "<Not logged in>";
        return [
            'access' => [
                'class' => AccessControl::className(),
                'denyCallback' => function ($rule, $action) {
                    throw new \yii\web\NotFoundHttpException("Only registered admin user can work with admin panel! Please log in as admin!");
                },
                'rules' => [
                    [
                        'allow' => true,
                        'matchCallback' => function ($rule, $action) use ($userName) {
                            return $userName === 'admin';
                        }
                    ]
                ]
            ]
        ];
    }


only - указывает, что фильтр ACF нужно применять только к действиям login, logout и signup

## Role Based Access Control

Управление доступом на основе ролей (RBAC) обеспечивает простой, но мощный централизованный контроль доступа.
Пожалуйста, обратитесь к Wikipedia для получения информации о сравнении RBAC с другими, более традиционными, 
системами контроля доступа.

Yii реализует общую иерархическую RBAC, следуя NIST RBAC model. Обеспечивается функциональность RBAC через компонент
приложения authManager.

Использование RBAC состоит из двух частей. Первая часть — это создание RBAC данных авторизации, и вторая часть — 
это использование данных авторизации для проверки доступа в том месте, где это нужно.

## У нас есть :
1) Разрешения
2) Роли
3) Правила

# Настройка RBAC Manager

    return [
        // ...
        'components' => [
            'authManager' => [
                'class' => 'yii\rbac\DbManager',
            ],
            // ...
        ],
    ];
    
**PhpManager** - использует файл с PHP скриптом для хранения данных авторизации
**DbManager** -  сохраняет данные в базе данных   
    
### Замечание: 
По умолчанию, yii\rbac\PhpManager сохраняет данные RBAC в файлах в директории @app/rbac/.
Убедитесь что данная директория и файлы в них доступны для записи Web серверу, если иерархия 
разрешений должна меняться онлайн.

### Примечание: 
Если вы используете шаблон проекта basic, компонент authManager необходимо настроить как в config/web.php, 
так и в конфигурации консольного приложения config/console.php. При использовании шаблона проекта advanced authManager
достаточно настроить единожды в common/config/main.php.


DbManager использует четыре таблицы для хранения данных:

**itemTable**: таблица для хранения **авторизационных элементов**. По умолчанию "auth_item".
**itemChildTable**: таблица для хранения **иерархии** элементов. По умолчанию "auth_item_child".
**assignmentTable**: таблица для хранения **назначений** элементов авторизации. По умолчанию "auth_assignment".
**ruleTable**: таблица для хранения **правил**. По умолчанию "auth_rule".

Создаём консольную команду:

    use yii\console\Controller;
    
    class RbacController extends Controller
    {
        public function actionInit()
            $auth = Yii::$app->authManager;
    
            // добавляем разрешение "createPost"
            $createPost = $auth->createPermission('createPost');
            $createPost->description = 'Create a post';
            $auth->add($createPost); // добавили запись в таблицу auth_item. (createPost) 
             	
            // добавляем разрешение "updatePost"
            $updatePost = $auth->createPermission('updatePost');
            $updatePost->description = 'Update post';
            $auth->add($updatePost); // добавили запись в таблицу auth_item.(updatePost) 
    
            // добавляем роль "author" и даём роли разрешение "createPost"
            $author = $auth->createRole('author');
            $auth->add($author); // добавили запись в таблицу auth_item. (author) и в таблицу auth_roles
            $auth->addChild($author, $createPost); // добавили запись в таблицу auth_item_child.(author-createPost)
    
            // добавляем роль "admin" и даём роли разрешение "updatePost"
            // а также все разрешения роли "author"
            $admin = $auth->createRole('admin');
            $auth->add($admin);
            $auth->addChild($admin, $updatePost);
            $auth->addChild($admin, $author); // админ копирует права автора
    
            // Назначение ролей пользователям. 1 и 2 это IDs возвращаемые IdentityInterface::getId()
            // обычно реализуемый в модели User.  В basic 100 и 101.
            $auth->assign($author, 2); // вставка в таблицу auth_assignment
            $auth->assign($admin, 1);
        }
    }
    
Если ваше приложение позволяет регистрировать пользователей, то вам необходимо сразу назначать роли этим новым 
пользователям. Например, для того, чтобы все вошедшие пользователи могли стать авторами в расширенном шаблоне 
проекта, вы должны изменить frontend\models\SignupForm::signup() как показано ниже:

    public function signup()
    {
        if ($this->validate()) {
            $user = new User();
            $user->username = $this->username;
            $user->email = $this->email;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->save(false);
    
            // нужно добавить следующие три строки:
            $auth = Yii::$app->authManager;
            $authorRole = $auth->getRole('author');
            $auth->assign($authorRole, $user->getId());
    
            return $user;
        }
    
        return null;
    }    
    
## Правила
Правила добавляют дополнительные ограничения на роли и разрешения. Правила — это классы, расширяющие yii\rbac\Rule.
Они должны реализовывать метод execute(). В иерархии, созданной нами ранее, автор не может редактировать свой пост. 
Давайте исправим это. Сначала мы должны создать правило, проверяющее что пользователь является автором поста:    

    class AuthorRule extends Rule
    {
        public $name = 'isAuthor';
    
        /**
         * @param string|int $user the user ID.
         * @param Item $item the role or permission that this rule is associated width.
         * @param array $params parameters passed to ManagerInterface::checkAccess().
         * @return bool a value indicating whether the rule permits the role or permission it is associated with.
         */
        public function execute($user, $item, $params)
        {
            return isset($params['post']) ? $params['post']->createdBy == $user : false;
        }
    }
    

Правило выше проверяет, что post был создан $user. Мы создадим специальное разрешение updateOwnPost в команде, 
которую мы использовали ранее:

    $auth = Yii::$app->authManager;
    
    // add the rule
    $rule = new \app\rbac\AuthorRule;
    $auth->add($rule); //вставка
    
    // добавляем разрешение "updateOwnPost" и привязываем к нему правило.
    $updateOwnPost = $auth->createPermission('updateOwnPost');
    $updateOwnPost->description = 'Update own post';
    $updateOwnPost->ruleName = $rule->name; //вставляем в поле rule_name - 'isAuthor' в запись 'updateOwnPost' 
    $auth->add($updateOwnPost);
    
    // "updateOwnPost" будет использоваться из "updatePost"
    $auth->addChild($updateOwnPost, $updatePost);
    
    // разрешаем "автору" обновлять его посты
    $auth->addChild($author, $updateOwnPost);

## Проверка доступа

    if (\Yii::$app->user->can('createPost')) {
        // create post
    }        
    
    
## Выводы:
    