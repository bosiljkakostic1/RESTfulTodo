<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\SignupForm;
use app\models\LoginForm;
use app\models\ResetPasswordForm;
use app\models\ResendVerificationEmailForm;
use app\models\PasswordResetRequestForm;
use app\models\VerifyEmailForm;
use app\models\ContactForm;
use app\models\Products;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use app\models\EditUserForm;
use app\models\User;
use yii\base\Exception;

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
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        $chLangOnly = Yii::$app->request->post('chLangOnly', false);
        if (!$chLangOnly || $chLangOnly == 'false') {
            $postData = Yii::$app->request->post();
            if ($model->load($postData) && $model->signup()) {
                Yii::$app->session->setFlash(
                    'success',
                    'Thank you for registration. Please check your inbox for verification email.'
                );
                return $this->goHome();
            }
        } else {
            $model->load(Yii::$app->request->post());
        }
        // die();
        return $this->render('signup', [
            'model' => $model,
        ]);
    }
    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionChangeUserData()
    {
        $user = User::findByUsername(Yii::$app->user->identity->username);
        $model = new EditUserForm();
        $chLangOnly = Yii::$app->request->post('chLangOnly', false);
        if (!$chLangOnly || $chLangOnly == 'false') {
            $postData = Yii::$app->request->post();
            if ($model->load($postData) && $model->update(Yii::$app->user->identity->username)) {
                Yii::$app->session->setFlash(
                    'success',
                    'Your User (Login) data was changed successfully.'
                );
                return $this->goHome();
            }
        } else {
            $model->load(Yii::$app->request->post());
        }
        $model->username = $user->username;
        $model->email = $user->email;
        $model->language = $user->language;
        $model->telefon_number = $user->telefon_number;
        return $this->render('userUpdate', [
            'model' => $model,
        ]);
    }
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash(
                    'error',
                    'Sorry, we are unable to reset password for the provided email address.'
                );
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash(
                'error',
                'Sorry, we are unable to resend verification email for the provided email address.'
            );
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }
    /**
     * Change application language
     *
     * @return string
     */
    public function actionChangeLanguge($language = 'en-US')
    {
        $session = Yii::$app->session;
        if (!$session->isActive) {
            $session->open();
        }
        $session->set('language', $language);
        \Yii::$app->language = $language;
        echo $language;
    }
    public function actionImportp()
    {
        $data = '
        [
    {"id":1,"name":"Handcrafted Plastic Shirt","category":"Chess","description":"Chess: Nihil non nulla.","price":148},
    {"id":2,"name":"Rustic Wooden Mouse","category":"Watersports","description":"Watersports: Tempore non dolorem.","price":453},
    {"id":3,"name":"Refined Rubber Car","category":"Watersports","description":"Watersports: Necessitatibus fuga non.","price":523},
    {"id":4,"name":"Awesome Wooden Chair","category":"Watersports","description":"Watersports: Eius nemo reiciendis.","price":951},
    {"id":5,"name":"Rustic Wooden Car","category":"Running","description":"Running: Illum enim ad.","price":633},
    {"id":6,"name":"Handcrafted Frozen Tuna","category":"Soccer","description":"Soccer: Illum et suscipit.","price":299},
    {"id":7,"name":"Awesome Cotton Mouse","category":"Watersports","description":"Watersports: Consequatur provident magnam.","price":647},
    {"id":8,"name":"Rustic Steel Towels","category":"Running","description":"Running: Animi deleniti id.","price":386},
    {"id":9,"name":"Practical Cotton Shirt","category":"Watersports","description":"Watersports: Eaque dignissimos necessitatibus.","price":30},
    {"id":10,"name":"Licensed Steel Towels","category":"Running","description":"Running: Necessitatibus vel in.","price":529},
    {"id":11,"name":"Tasty Rubber Mouse","category":"Chess","description":"Chess: Culpa sed voluptatem.","price":863},
    {"id":12,"name":"Ergonomic Granite Bacon","category":"Watersports","description":"Watersports: Assumenda quo magnam.","price":75},
    {"id":13,"name":"Small Fresh Car","category":"Soccer","description":"Soccer: Aut ex rerum.","price":666},
    {"id":14,"name":"Small Rubber Computer","category":"Soccer","description":"Soccer: Ex ut laboriosam.","price":795},
    {"id":15,"name":"Licensed Concrete Tuna","category":"Watersports","description":"Watersports: Quae fuga quas.","price":145},
    {"id":16,"name":"Awesome Rubber Car","category":"Soccer","description":"Soccer: Et ipsam qui.","price":217},
    {"id":17,"name":"Sleek Frozen Table","category":"Running","description":"Running: Animi voluptatibus iure.","price":140},
    {"id":18,"name":"Handmade Concrete Cheese","category":"Soccer","description":"Soccer: Ad qui dolore.","price":846},
    {"id":19,"name":"Awesome Frozen Car","category":"Watersports","description":"Watersports: A rerum mollitia.","price":314},
    {"id":20,"name":"Awesome Plastic Ball","category":"Chess","description":"Chess: Sunt recusandae laudantium.","price":34},
    {"id":21,"name":"Handcrafted Steel Table","category":"Chess","description":"Chess: Non reiciendis asperiores.","price":477},
    {"id":22,"name":"Gorgeous Plastic Sausages","category":"Soccer","description":"Soccer: Aut eos facere.","price":172},
    {"id":23,"name":"Sleek Rubber Car","category":"Soccer","description":"Soccer: Ut ullam exercitationem.","price":928},
    {"id":24,"name":"Fantastic Plastic Shoes","category":"Watersports","description":"Watersports: Blanditiis culpa distinctio.","price":114},
    {"id":25,"name":"Fantastic Metal Pants","category":"Soccer","description":"Soccer: Velit aut expedita.","price":274},
    {"id":26,"name":"Intelligent Soft Bacon","category":"Running","description":"Running: Est inventore nemo.","price":730},
    {"id":27,"name":"Small Fresh Soap","category":"Chess","description":"Chess: Fuga doloremque voluptatibus.","price":860},
    {"id":28,"name":"Practical Wooden Towels","category":"Running","description":"Running: Voluptatem illum neque.","price":89},
    {"id":29,"name":"Handcrafted Frozen Shirt","category":"Running","description":"Running: Non sit ea.","price":51},
    {"id":30,"name":"Practical Concrete Chips","category":"Chess","description":"Chess: Praesentium explicabo optio.","price":552},
    {"id":31,"name":"Generic Metal Table","category":"Chess","description":"Chess: Cupiditate temporibus alias.","price":705},
    {"id":32,"name":"Handmade Granite Shoes","category":"Soccer","description":"Soccer: Ea minus ex.","price":875},
    {"id":33,"name":"Awesome Cotton Chicken","category":"Chess","description":"Chess: Sunt consequatur ad.","price":591},
    {"id":34,"name":"Sleek Steel Table","category":"Running","description":"Running: Eaque dolore veniam.","price":787},
    {"id":35,"name":"Practical Frozen Chicken","category":"Chess","description":"Chess: Asperiores qui asperiores.","price":372},
    {"id":36,"name":"Tasty Metal Chips","category":"Watersports","description":"Watersports: Cupiditate molestiae quia.","price":498},
    {"id":37,"name":"Handcrafted Concrete Bike","category":"Running","description":"Running: Repellat unde qui.","price":990},
    {"id":38,"name":"Small Cotton Soap","category":"Watersports","description":"Watersports: Consectetur aut velit.","price":462},
    {"id":39,"name":"Handmade Metal Pants","category":"Chess","description":"Chess: Corrupti perspiciatis enim.","price":953},
    {"id":40,"name":"Ergonomic Cotton Chips","category":"Chess","description":"Chess: Ea quod quia.","price":848},
    {"id":41,"name":"Handmade Cotton Ball","category":"Watersports","description":"Watersports: Quos hic eveniet.","price":270},
    {"id":42,"name":"Tasty Soft Chicken","category":"Chess","description":"Chess: Autem consequatur nisi.","price":443},
    {"id":43,"name":"Ergonomic Metal Chips","category":"Chess","description":"Chess: Fugit iure dolores.","price":446},
    {"id":44,"name":"Intelligent Wooden Salad","category":"Soccer","description":"Soccer: Sapiente voluptatum quo.","price":212},
    {"id":45,"name":"Generic Concrete Hat","category":"Chess","description":"Chess: Dolores ea reiciendis.","price":323},
    {"id":46,"name":"Generic Fresh Shirt","category":"Chess","description":"Chess: Iure qui magnam.","price":247},
    {"id":47,"name":"Awesome Fresh Sausages","category":"Soccer","description":"Soccer: Rem mollitia nam.","price":285},
    {"id":48,"name":"Ergonomic Concrete Towels","category":"Running","description":"Running: Beatae amet minus.","price":573},
    {"id":49,"name":"Rustic Frozen Shirt","category":"Running","description":"Running: Quos sit harum.","price":548},
    {"id":50,"name":"Intelligent Fresh Ball","category":"Chess","description":"Chess: Neque consequatur voluptate.","price":926},
    {"id":51,"name":"Unbranded Metal Table","category":"Soccer","description":"Soccer: Nihil voluptatem vero.","price":785},
    {"id":52,"name":"Intelligent Steel Towels","category":"Running","description":"Running: Corporis ex reiciendis.","price":763},
    {"id":53,"name":"Awesome Granite Pants","category":"Watersports","description":"Watersports: Tenetur velit consequatur.","price":246},
    {"id":54,"name":"Ergonomic Rubber Bike","category":"Soccer","description":"Soccer: Et cumque deserunt.","price":924},
    {"id":55,"name":"Unbranded Rubber Pants","category":"Running","description":"Running: Quos qui voluptatem.","price":381},
    {"id":56,"name":"Small Granite Salad","category":"Soccer","description":"Soccer: Vitae dolores quidem.","price":26},
    {"id":57,"name":"Unbranded Frozen Chips","category":"Running","description":"Running: Nobis minima corrupti.","price":446},
    {"id":58,"name":"Refined Frozen Shoes","category":"Soccer","description":"Soccer: Non minima velit.","price":842},
    {"id":59,"name":"Intelligent Steel Pizza","category":"Running","description":"Running: Cumque nemo accusantium.","price":114},
    {"id":60,"name":"Intelligent Steel Mouse","category":"Soccer","description":"Soccer: Veritatis eveniet aut.","price":713},
    {"id":61,"name":"Gorgeous Rubber Gloves","category":"Watersports","description":"Watersports: Odio dolores eum.","price":472},
    {"id":62,"name":"Refined Concrete Tuna","category":"Watersports","description":"Watersports: Quo quod vero.","price":832},
    {"id":63,"name":"Licensed Metal Cheese","category":"Chess","description":"Chess: Id deserunt natus.","price":218},
    {"id":64,"name":"Sleek Frozen Soap","category":"Running","description":"Running: Pariatur tenetur aliquam.","price":246},
    {"id":65,"name":"Ergonomic Cotton Shoes","category":"Running","description":"Running: Qui iusto velit.","price":299},
    {"id":66,"name":"Sleek Metal Salad","category":"Watersports","description":"Watersports: Labore molestias pariatur.","price":360},
    {"id":67,"name":"Unbranded Granite Fish","category":"Running","description":"Running: Iure facilis deserunt.","price":251},
    {"id":68,"name":"Rustic Soft Cheese","category":"Watersports","description":"Watersports: Delectus error dolor.","price":148},
    {"id":69,"name":"Tasty Fresh Bike","category":"Watersports","description":"Watersports: Consequatur asperiores veritatis.","price":810},
    {"id":70,"name":"Generic Rubber Keyboard","category":"Watersports","description":"Watersports: Eos temporibus cum.","price":924},
    {"id":71,"name":"Ergonomic Plastic Mouse","category":"Running","description":"Running: Omnis autem quae.","price":735},
    {"id":72,"name":"Refined Frozen Soap","category":"Soccer","description":"Soccer: Quidem aut animi.","price":515},
    {"id":73,"name":"Ergonomic Wooden Fish","category":"Running","description":"Running: Sit ut consectetur.","price":637},
    {"id":74,"name":"Small Granite Table","category":"Chess","description":"Chess: Et saepe vero.","price":82},
    {"id":75,"name":"Licensed Cotton Table","category":"Watersports","description":"Watersports: Quam voluptatem illo.","price":34},
    {"id":76,"name":"Licensed Soft Keyboard","category":"Watersports","description":"Watersports: Ad porro porro.","price":705},
    {"id":77,"name":"Sleek Wooden Computer","category":"Soccer","description":"Soccer: Consectetur sint ipsam.","price":628},
    {"id":78,"name":"Sleek Steel Ball","category":"Running","description":"Running: Ab dicta error.","price":720},
    {"id":79,"name":"Intelligent Metal Pants","category":"Chess","description":"Chess: Deleniti omnis consequatur.","price":881},
    {"id":80,"name":"Incredible Cotton Keyboard","category":"Running","description":"Running: Quia id explicabo.","price":913},
    {"id":81,"name":"Unbranded Wooden Chair","category":"Watersports","description":"Watersports: Soluta rem soluta.","price":46},
    {"id":82,"name":"Small Metal Tuna","category":"Watersports","description":"Watersports: Quasi architecto officiis.","price":992},
    {"id":83,"name":"Licensed Wooden Chicken","category":"Watersports","description":"Watersports: Totam magni omnis.","price":271},
    {"id":84,"name":"Generic Concrete Soap","category":"Running","description":"Running: Beatae error temporibus.","price":653},
    {"id":85,"name":"Intelligent Soft Gloves","category":"Chess","description":"Chess: Et maxime deleniti.","price":873},
    {"id":86,"name":"Sleek Rubber Pants","category":"Soccer","description":"Soccer: Animi aspernatur quas.","price":688},
    {"id":87,"name":"Small Granite Car","category":"Soccer","description":"Soccer: Molestiae voluptates ut.","price":557},
    {"id":88,"name":"Incredible Soft Fish","category":"Soccer","description":"Soccer: Quidem enim incidunt.","price":870},
    {"id":89,"name":"Handcrafted Steel Shirt","category":"Watersports","description":"Watersports: Quia voluptatem eius.","price":821},
    {"id":90,"name":"Practical Plastic Shoes","category":"Running","description":"Running: Dolor deserunt quas.","price":766},
    {"id":91,"name":"Tasty Soft Pants","category":"Watersports","description":"Watersports: Et veritatis earum.","price":253},
    {"id":92,"name":"Intelligent Metal Fish","category":"Chess","description":"Chess: Unde fuga consequatur.","price":714},
    {"id":93,"name":"Handmade Steel Mouse","category":"Running","description":"Running: Eveniet nisi voluptatem.","price":705},
    {"id":94,"name":"Incredible Granite Ball","category":"Chess","description":"Chess: Iste voluptate est.","price":914},
    {"id":95,"name":"Intelligent Concrete Tuna","category":"Watersports","description":"Watersports: Ratione reprehenderit doloremque.","price":927},
    {"id":96,"name":"Sleek Plastic Hat","category":"Running","description":"Running: Aut quaerat vel.","price":799},
    {"id":97,"name":"Fantastic Metal Towels","category":"Soccer","description":"Soccer: Et quae distinctio.","price":415},
    {"id":98,"name":"Ergonomic Rubber Ball","category":"Watersports","description":"Watersports: Odit labore unde.","price":357},
    {"id":99,"name":"Incredible Plastic Ball","category":"Chess","description":"Chess: Blanditiis consequatur eaque.","price":481},
    {"id":100,"name":"Ergonomic Steel Cheese","category":"Chess","description":"Chess: Et nihil et.","price":131},
    {"id":101,"name":"Handmade Frozen Sausages","category":"Watersports","description":"Watersports: Est dolorem tempore.","price":428},
    {"id":102,"name":"Unbranded Metal Fish","category":"Soccer","description":"Soccer: Sit corporis est.","price":535},
    {"id":103,"name":"Handcrafted Frozen Cheese","category":"Chess","description":"Chess: Quibusdam dolor qui.","price":84},
    {"id":104,"name":"Ergonomic Soft Tuna","category":"Soccer","description":"Soccer: Vel eum molestiae.","price":509},
    {"id":105,"name":"Fantastic Steel Shoes","category":"Running","description":"Running: Est recusandae occaecati.","price":293},
    {"id":106,"name":"Rustic Granite Computer","category":"Running","description":"Running: Ut est voluptates.","price":625},
    {"id":107,"name":"Handcrafted Concrete Salad","category":"Chess","description":"Chess: Soluta ut perferendis.","price":657},
    {"id":108,"name":"Handmade Soft Sausages","category":"Soccer","description":"Soccer: In eveniet voluptatem.","price":343},
    {"id":109,"name":"Sleek Frozen Bike","category":"Soccer","description":"Soccer: Doloremque corrupti omnis.","price":805},
    {"id":110,"name":"Awesome Concrete Table","category":"Watersports","description":"Watersports: Et nisi qui.","price":624},
    {"id":111,"name":"Intelligent Cotton Keyboard","category":"Soccer","description":"Soccer: Distinctio praesentium excepturi.","price":405},
    {"id":112,"name":"Intelligent Granite Mouse","category":"Soccer","description":"Soccer: Aut accusantium aut.","price":271},
    {"id":113,"name":"Tasty Frozen Chips","category":"Watersports","description":"Watersports: Et cupiditate asperiores.","price":117},
    {"id":114,"name":"Gorgeous Steel Bike","category":"Watersports","description":"Watersports: Nihil molestiae sapiente.","price":677},
    {"id":115,"name":"Intelligent Metal Towels","category":"Chess","description":"Chess: Dolores quisquam dolor.","price":870},
    {"id":116,"name":"Refined Steel Computer","category":"Watersports","description":"Watersports: Ut laboriosam molestias.","price":502},
    {"id":117,"name":"Sleek Plastic Ball","category":"Soccer","description":"Soccer: Quos accusantium qui.","price":957},
    {"id":118,"name":"Awesome Soft Chicken","category":"Watersports","description":"Watersports: Non velit dicta.","price":883},
    {"id":119,"name":"Handmade Fresh Table","category":"Soccer","description":"Soccer: Molestiae sunt voluptas.","price":472},
    {"id":120,"name":"Small Steel Ball","category":"Running","description":"Running: Odit nihil eligendi.","price":913},
    {"id":121,"name":"Gorgeous Granite Ball","category":"Running","description":"Running: Quasi voluptatem sunt.","price":113},
    {"id":122,"name":"Handmade Concrete Shirt","category":"Watersports","description":"Watersports: Debitis nihil earum.","price":244},
    {"id":123,"name":"Gorgeous Fresh Bike","category":"Watersports","description":"Watersports: Possimus ea nobis.","price":110},
    {"id":124,"name":"Ergonomic Concrete Table","category":"Running","description":"Running: Occaecati harum nisi.","price":958},
    {"id":125,"name":"Refined Plastic Pants","category":"Chess","description":"Chess: Quas dolores corporis.","price":628},
    {"id":126,"name":"Refined Wooden Salad","category":"Chess","description":"Chess: Similique quis atque.","price":965},
    {"id":127,"name":"Fantastic Granite Chicken","category":"Watersports","description":"Watersports: Velit doloribus libero.","price":23},
    {"id":128,"name":"Handmade Concrete Bacon","category":"Chess","description":"Chess: Sint quia ipsam.","price":10},
    {"id":129,"name":"Practical Cotton Salad","category":"Running","description":"Running: Aspernatur nihil ut.","price":284},
    {"id":130,"name":"Handcrafted Plastic Keyboard","category":"Running","description":"Running: Quaerat aut et.","price":964},
    {"id":131,"name":"Incredible Steel Chair","category":"Soccer","description":"Soccer: Rerum dolorem porro.","price":763},
    {"id":132,"name":"Unbranded Plastic Chicken","category":"Chess","description":"Chess: Nesciunt temporibus et.","price":605},
    {"id":133,"name":"Handcrafted Frozen Hat","category":"Soccer","description":"Soccer: Aspernatur repellat voluptate.","price":352},
    {"id":134,"name":"Awesome Rubber Salad","category":"Watersports","description":"Watersports: In illo eos.","price":163},
    {"id":135,"name":"Handcrafted Wooden Computer","category":"Chess","description":"Chess: Molestias officia consequatur.","price":441},
    {"id":136,"name":"Refined Wooden Cheese","category":"Watersports","description":"Watersports: Sed est illo.","price":307},
    {"id":137,"name":"Unbranded Soft Keyboard","category":"Soccer","description":"Soccer: Pariatur enim dolorum.","price":68},
    {"id":138,"name":"Practical Metal Towels","category":"Soccer","description":"Soccer: Ipsam odit est.","price":156},
    {"id":139,"name":"Intelligent Wooden Bacon","category":"Running","description":"Running: Nam est ex.","price":603},
    {"id":140,"name":"Fantastic Frozen Bacon","category":"Running","description":"Running: Modi quia inventore.","price":8},
    {"id":141,"name":"Unbranded Steel Bacon","category":"Chess","description":"Chess: Libero quas est.","price":706},
    {"id":142,"name":"Gorgeous Cotton Chips","category":"Watersports","description":"Watersports: Praesentium dolorem voluptatem.","price":451},
    {"id":143,"name":"Handmade Rubber Keyboard","category":"Soccer","description":"Soccer: Aut sunt neque.","price":408},
    {"id":144,"name":"Intelligent Plastic Shoes","category":"Running","description":"Running: Enim quae culpa.","price":697},
    {"id":145,"name":"Intelligent Fresh Table","category":"Chess","description":"Chess: Enim corporis ullam.","price":831},
    {"id":146,"name":"Rustic Wooden Pizza","category":"Soccer","description":"Soccer: Exercitationem explicabo qui.","price":116},
    {"id":147,"name":"Intelligent Plastic Salad","category":"Soccer","description":"Soccer: A accusamus repellat.","price":849},
    {"id":148,"name":"Practical Granite Salad","category":"Soccer","description":"Soccer: Eum in aspernatur.","price":886},
    {"id":149,"name":"Licensed Fresh Shoes","category":"Chess","description":"Chess: Assumenda blanditiis illum.","price":992},
    {"id":150,"name":"Handmade Metal Pizza","category":"Watersports","description":"Watersports: Sapiente autem cumque.","price":68},
    {"id":151,"name":"Gorgeous Concrete Table","category":"Soccer","description":"Soccer: A enim voluptate.","price":742},
    {"id":152,"name":"Practical Metal Computer","category":"Chess","description":"Chess: Voluptas similique earum.","price":92},
    {"id":153,"name":"Rustic Wooden Computer","category":"Soccer","description":"Soccer: Similique impedit reiciendis.","price":900},
    {"id":154,"name":"Fantastic Soft Shoes","category":"Soccer","description":"Soccer: Iusto odio consequatur.","price":114},
    {"id":155,"name":"Generic Cotton Salad","category":"Watersports","description":"Watersports: Minus ut quidem.","price":627},
    {"id":156,"name":"Rustic Frozen Shirt","category":"Running","description":"Running: Autem et eveniet.","price":800},
    {"id":157,"name":"Practical Steel Soap","category":"Watersports","description":"Watersports: Labore aut dolor.","price":565},
    {"id":158,"name":"Incredible Rubber Car","category":"Soccer","description":"Soccer: Et quas odio.","price":238},
    {"id":159,"name":"Ergonomic Cotton Shoes","category":"Watersports","description":"Watersports: Et pariatur vero.","price":282},
    {"id":160,"name":"Gorgeous Cotton Towels","category":"Running","description":"Running: Voluptatibus quae atque.","price":859},
    {"id":161,"name":"Generic Cotton Chips","category":"Running","description":"Running: Voluptatem et rerum.","price":557},
    {"id":162,"name":"Practical Soft Keyboard","category":"Chess","description":"Chess: Id quibusdam vero.","price":111},
    {"id":163,"name":"Gorgeous Concrete Hat","category":"Chess","description":"Chess: Vero aut sit.","price":371},
    {"id":164,"name":"Unbranded Cotton Table","category":"Chess","description":"Chess: Dolorum sunt unde.","price":600},
    {"id":165,"name":"Incredible Fresh Hat","category":"Soccer","description":"Soccer: In sit dolorum.","price":699},
    {"id":166,"name":"Gorgeous Granite Bike","category":"Chess","description":"Chess: Est dolorem asperiores.","price":176},
    {"id":167,"name":"Rustic Soft Chair","category":"Watersports","description":"Watersports: Non quidem qui.","price":15},
    {"id":168,"name":"Unbranded Metal Chicken","category":"Running","description":"Running: Aliquid eaque qui.","price":664},
    {"id":169,"name":"Refined Frozen Computer","category":"Watersports","description":"Watersports: Sint nisi et.","price":849},
    {"id":170,"name":"Tasty Plastic Sausages","category":"Chess","description":"Chess: Qui consequuntur porro.","price":407},
    {"id":171,"name":"Gorgeous Concrete Sausages","category":"Soccer","description":"Soccer: Ipsam veritatis sunt.","price":885},
    {"id":172,"name":"Fantastic Granite Fish","category":"Soccer","description":"Soccer: Corrupti doloribus dolor.","price":728},
    {"id":173,"name":"Licensed Soft Shoes","category":"Soccer","description":"Soccer: Eos aut sequi.","price":655},
    {"id":174,"name":"Licensed Granite Salad","category":"Chess","description":"Chess: Doloribus dignissimos vitae.","price":975},
    {"id":175,"name":"Small Plastic Ball","category":"Chess","description":"Chess: Asperiores maxime ex.","price":668},
    {"id":176,"name":"Handmade Frozen Tuna","category":"Watersports","description":"Watersports: Deleniti amet atque.","price":6},
    {"id":177,"name":"Practical Frozen Shoes","category":"Chess","description":"Chess: Quos aspernatur doloremque.","price":316},
    {"id":178,"name":"Small Soft Salad","category":"Running","description":"Running: Qui reiciendis odit.","price":352},
    {"id":179,"name":"Awesome Metal Car","category":"Running","description":"Running: Et facilis molestias.","price":658},
    {"id":180,"name":"Small Fresh Chicken","category":"Chess","description":"Chess: Et laboriosam corrupti.","price":173},
    {"id":181,"name":"Sleek Concrete Chair","category":"Watersports","description":"Watersports: Animi eum ullam.","price":85},
    {"id":182,"name":"Awesome Granite Fish","category":"Watersports","description":"Watersports: Hic omnis incidunt.","price":521},
    {"id":183,"name":"Licensed Soft Ball","category":"Chess","description":"Chess: Voluptas modi molestiae.","price":36},
    {"id":184,"name":"Licensed Metal Gloves","category":"Soccer","description":"Soccer: Ipsum fuga impedit.","price":584},
    {"id":185,"name":"Tasty Cotton Fish","category":"Soccer","description":"Soccer: Facilis natus deleniti.","price":344},
    {"id":186,"name":"Handmade Rubber Bike","category":"Running","description":"Running: Pariatur debitis sit.","price":850},
    {"id":187,"name":"Practical Plastic Towels","category":"Chess","description":"Chess: Distinctio sint ex.","price":882},
    {"id":188,"name":"Awesome Cotton Gloves","category":"Watersports","description":"Watersports: Impedit labore quaerat.","price":396},
    {"id":189,"name":"Handcrafted Wooden Towels","category":"Chess","description":"Chess: Quidem harum inventore.","price":833},
    {"id":190,"name":"Handcrafted Granite Soap","category":"Watersports","description":"Watersports: Ullam aperiam ut.","price":983},
    {"id":191,"name":"Small Frozen Fish","category":"Chess","description":"Chess: Sint quia quo.","price":448},
    {"id":192,"name":"Small Cotton Salad","category":"Chess","description":"Chess: Tempore expedita labore.","price":897},
    {"id":193,"name":"Sleek Plastic Chips","category":"Running","description":"Running: Quia hic et.","price":949},
    {"id":194,"name":"Generic Plastic Pizza","category":"Soccer","description":"Soccer: Rerum omnis qui.","price":530},
    {"id":195,"name":"Rustic Soft Mouse","category":"Running","description":"Running: Laboriosam delectus nihil.","price":192},
    {"id":196,"name":"Ergonomic Concrete Shoes","category":"Watersports","description":"Watersports: Nobis et esse.","price":954},
    {"id":197,"name":"Tasty Cotton Cheese","category":"Watersports","description":"Watersports: Aspernatur aut quia.","price":848},
    {"id":198,"name":"Sleek Frozen Soap","category":"Running","description":"Running: Expedita nostrum adipisci.","price":195},
    {"id":199,"name":"Unbranded Plastic Bacon","category":"Chess","description":"Chess: Iste id sed.","price":21},
    {"id":200,"name":"Awesome Soft Ball","category":"Soccer","description":"Soccer: Rerum qui eum.","price":102},
    {"id":201,"name":"Awesome Frozen Soap","category":"Running","description":"Running: Sed officia fugit.","price":743},
    {"id":202,"name":"Small Steel Soap","category":"Running","description":"Running: Eos eos laudantium.","price":999},
    {"id":203,"name":"Unbranded Soft Table","category":"Soccer","description":"Soccer: Rem assumenda consequatur.","price":595},
    {"id":204,"name":"Practical Fresh Salad","category":"Watersports","description":"Watersports: Corrupti non possimus.","price":301},
    {"id":205,"name":"Licensed Concrete Ball","category":"Soccer","description":"Soccer: Minus ut nulla.","price":325},
    {"id":206,"name":"Small Wooden Chips","category":"Running","description":"Running: Enim impedit illo.","price":806},
    {"id":207,"name":"Unbranded Wooden Shirt","category":"Running","description":"Running: Quis doloremque ea.","price":16},
    {"id":208,"name":"Generic Concrete Keyboard","category":"Watersports","description":"Watersports: Quisquam quia repellat.","price":825},
    {"id":209,"name":"Fantastic Concrete Soap","category":"Chess","description":"Chess: Quibusdam ipsum harum.","price":992},
    {"id":210,"name":"Sleek Cotton Computer","category":"Watersports","description":"Watersports: In voluptatem blanditiis.","price":187},
    {"id":211,"name":"Small Rubber Chair","category":"Watersports","description":"Watersports: Cum molestiae dolorem.","price":125},
    {"id":212,"name":"Tasty Steel Soap","category":"Running","description":"Running: Autem eos et.","price":413},
    {"id":213,"name":"Sleek Steel Sausages","category":"Chess","description":"Chess: Perferendis ipsum ratione.","price":656},
    {"id":214,"name":"Refined Steel Table","category":"Soccer","description":"Soccer: Quaerat aut incidunt.","price":691},
    {"id":215,"name":"Practical Concrete Towels","category":"Running","description":"Running: Aut aut numquam.","price":917},
    {"id":216,"name":"Intelligent Soft Keyboard","category":"Watersports","description":"Watersports: Quas facilis qui.","price":398},
    {"id":217,"name":"Rustic Rubber Table","category":"Soccer","description":"Soccer: Vel qui eos.","price":364},
    {"id":218,"name":"Rustic Soft Keyboard","category":"Soccer","description":"Soccer: Ad ut mollitia.","price":684},
    {"id":219,"name":"Incredible Steel Tuna","category":"Soccer","description":"Soccer: Et nulla quo.","price":251},
    {"id":220,"name":"Small Plastic Bike","category":"Running","description":"Running: Et labore unde.","price":933},
    {"id":221,"name":"Unbranded Wooden Towels","category":"Chess","description":"Chess: Quisquam sint quis.","price":503},
    {"id":222,"name":"Intelligent Concrete Shoes","category":"Chess","description":"Chess: Voluptas amet sequi.","price":839},
    {"id":223,"name":"Awesome Soft Cheese","category":"Soccer","description":"Soccer: Sunt nobis ut.","price":28},
    {"id":224,"name":"Incredible Granite Salad","category":"Chess","description":"Chess: Quam non tenetur.","price":869},
    {"id":225,"name":"Intelligent Granite Car","category":"Watersports","description":"Watersports: Necessitatibus mollitia est.","price":398},
    {"id":226,"name":"Gorgeous Rubber Soap","category":"Chess","description":"Chess: Amet vitae laborum.","price":482},
    {"id":227,"name":"Handmade Metal Keyboard","category":"Watersports","description":"Watersports: Incidunt aspernatur modi.","price":357},
    {"id":228,"name":"Practical Granite Hat","category":"Watersports","description":"Watersports: Nostrum quo sed.","price":70},
    {"id":229,"name":"Tasty Cotton Pants","category":"Soccer","description":"Soccer: Velit consequatur quaerat.","price":304},
    {"id":230,"name":"Handcrafted Soft Chips","category":"Watersports","description":"Watersports: Sequi eos repudiandae.","price":777},
    {"id":231,"name":"Rustic Cotton Hat","category":"Soccer","description":"Soccer: Vitae et optio.","price":888},
    {"id":232,"name":"Rustic Wooden Chicken","category":"Watersports","description":"Watersports: Quisquam id qui.","price":721},
    {"id":233,"name":"Small Granite Car","category":"Chess","description":"Chess: Accusantium vitae omnis.","price":9},
    {"id":234,"name":"Licensed Frozen Soap","category":"Soccer","description":"Soccer: Hic nihil est.","price":119},
    {"id":235,"name":"Sleek Steel Sausages","category":"Chess","description":"Chess: Deserunt ratione porro.","price":508},
    {"id":236,"name":"Incredible Granite Shoes","category":"Watersports","description":"Watersports: Maiores ut quae.","price":8},
    {"id":237,"name":"Licensed Steel Bacon","category":"Chess","description":"Chess: Minima iusto cumque.","price":948},
    {"id":238,"name":"Unbranded Granite Pizza","category":"Watersports","description":"Watersports: Tenetur rerum officia.","price":878},
    {"id":239,"name":"Awesome Fresh Salad","category":"Chess","description":"Chess: Itaque dolore qui.","price":399},
    {"id":240,"name":"Handmade Wooden Chicken","category":"Soccer","description":"Soccer: Rerum aut dolorem.","price":203},
    {"id":241,"name":"Awesome Soft Fish","category":"Running","description":"Running: Sed ut non.","price":632},
    {"id":242,"name":"Small Granite Pants","category":"Watersports","description":"Watersports: Dolor voluptatem necessitatibus.","price":456},
    {"id":243,"name":"Unbranded Metal Pizza","category":"Soccer","description":"Soccer: Consequuntur necessitatibus est.","price":91},
    {"id":244,"name":"Tasty Cotton Fish","category":"Watersports","description":"Watersports: Ut et et.","price":901},
    {"id":245,"name":"Practical Granite Fish","category":"Watersports","description":"Watersports: Eos sunt recusandae.","price":652},
    {"id":246,"name":"Licensed Granite Bike","category":"Chess","description":"Chess: Et consequatur optio.","price":964},
    {"id":247,"name":"Unbranded Plastic Soap","category":"Chess","description":"Chess: Dolor non perferendis.","price":75},
    {"id":248,"name":"Intelligent Plastic Sausages","category":"Watersports","description":"Watersports: Et sapiente rerum.","price":514},
    {"id":249,"name":"Refined Frozen Cheese","category":"Running","description":"Running: Laboriosam molestiae expedita.","price":79},
    {"id":250,"name":"Handmade Wooden Shirt","category":"Soccer","description":"Soccer: Eaque est mollitia.","price":947},
    {"id":251,"name":"Gorgeous Cotton Keyboard","category":"Watersports","description":"Watersports: Tenetur et modi.","price":489},
    {"id":252,"name":"Generic Fresh Gloves","category":"Soccer","description":"Soccer: Culpa voluptate accusantium.","price":758},
    {"id":253,"name":"Fantastic Plastic Pizza","category":"Running","description":"Running: Sit error sit.","price":873},
    {"id":254,"name":"Handmade Wooden Salad","category":"Chess","description":"Chess: Vel occaecati alias.","price":542},
    {"id":255,"name":"Licensed Cotton Ball","category":"Watersports","description":"Watersports: Consequatur sed facilis.","price":188},
    {"id":256,"name":"Practical Concrete Computer","category":"Chess","description":"Chess: Enim dicta inventore.","price":349},
    {"id":257,"name":"Tasty Wooden Gloves","category":"Running","description":"Running: Autem dolores esse.","price":180},
    {"id":258,"name":"Intelligent Steel Fish","category":"Watersports","description":"Watersports: Velit ratione quae.","price":359},
    {"id":259,"name":"Unbranded Fresh Hat","category":"Running","description":"Running: Deleniti deserunt consequuntur.","price":275},
    {"id":260,"name":"Licensed Granite Sausages","category":"Soccer","description":"Soccer: Dolor sit occaecati.","price":279},
    {"id":261,"name":"Practical Soft Soap","category":"Running","description":"Running: Et omnis voluptas.","price":197},
    {"id":262,"name":"Sleek Concrete Computer","category":"Running","description":"Running: Qui ut est.","price":145},
    {"id":263,"name":"Licensed Rubber Pants","category":"Soccer","description":"Soccer: Reprehenderit sint sint.","price":115},
    {"id":264,"name":"Rustic Soft Car","category":"Soccer","description":"Soccer: Odit quia placeat.","price":192},
    {"id":265,"name":"Tasty Wooden Pants","category":"Running","description":"Running: Officiis consequatur quo.","price":683},
    {"id":266,"name":"Gorgeous Concrete Bacon","category":"Soccer","description":"Soccer: Saepe quod nisi.","price":675},
    {"id":267,"name":"Sleek Wooden Soap","category":"Watersports","description":"Watersports: Est beatae aperiam.","price":957},
    {"id":268,"name":"Incredible Granite Gloves","category":"Soccer","description":"Soccer: Natus itaque a.","price":674},
    {"id":269,"name":"Ergonomic Metal Chair","category":"Running","description":"Running: Debitis qui delectus.","price":356},
    {"id":270,"name":"Practical Metal Bike","category":"Soccer","description":"Soccer: Fuga magnam consequatur.","price":320},
    {"id":271,"name":"Practical Frozen Towels","category":"Running","description":"Running: Vel facere veniam.","price":157},
    {"id":272,"name":"Awesome Concrete Pizza","category":"Chess","description":"Chess: Vel corrupti laudantium.","price":44},
    {"id":273,"name":"Incredible Wooden Fish","category":"Soccer","description":"Soccer: Ratione molestias adipisci.","price":181},
    {"id":274,"name":"Sleek Concrete Shirt","category":"Soccer","description":"Soccer: Assumenda adipisci aliquam.","price":754},
    {"id":275,"name":"Handcrafted Metal Chair","category":"Soccer","description":"Soccer: Asperiores sint perspiciatis.","price":980},
    {"id":276,"name":"Refined Cotton Table","category":"Chess","description":"Chess: Architecto quod adipisci.","price":514},
    {"id":277,"name":"Tasty Granite Chair","category":"Running","description":"Running: Maiores consequatur suscipit.","price":97},
    {"id":278,"name":"Intelligent Plastic Chicken","category":"Running","description":"Running: Corrupti omnis iste.","price":852},
    {"id":279,"name":"Fantastic Fresh Table","category":"Running","description":"Running: Quos blanditiis a.","price":566},
    {"id":280,"name":"Intelligent Steel Chips","category":"Watersports","description":"Watersports: Voluptate optio nisi.","price":910},
    {"id":281,"name":"Licensed Rubber Sausages","category":"Soccer","description":"Soccer: Libero ipsam et.","price":267},
    {"id":282,"name":"Ergonomic Soft Pants","category":"Running","description":"Running: Hic quia totam.","price":365},
    {"id":283,"name":"Practical Wooden Mouse","category":"Chess","description":"Chess: Aspernatur vel voluptatibus.","price":799},
    {"id":284,"name":"Handmade Frozen Pizza","category":"Running","description":"Running: Maiores aperiam consequatur.","price":461},
    {"id":285,"name":"Practical Granite Pizza","category":"Watersports","description":"Watersports: Soluta omnis atque.","price":831},
    {"id":286,"name":"Small Fresh Chips","category":"Watersports","description":"Watersports: Ut hic laborum.","price":956},
    {"id":287,"name":"Incredible Plastic Fish","category":"Chess","description":"Chess: Mollitia adipisci reprehenderit.","price":796},
    {"id":288,"name":"Unbranded Wooden Keyboard","category":"Chess","description":"Chess: Doloremque sint suscipit.","price":648},
    {"id":289,"name":"Small Granite Cheese","category":"Chess","description":"Chess: Officia aut facilis.","price":627},
    {"id":290,"name":"Handmade Plastic Bike","category":"Soccer","description":"Soccer: Iure eum aut.","price":981},
    {"id":291,"name":"Small Metal Chips","category":"Chess","description":"Chess: Dicta quasi rem.","price":592},
    {"id":292,"name":"Handmade Cotton Sausages","category":"Running","description":"Running: Qui incidunt veritatis.","price":648},
    {"id":293,"name":"Gorgeous Frozen Computer","category":"Watersports","description":"Watersports: Optio et doloremque.","price":996},
    {"id":294,"name":"Ergonomic Cotton Chips","category":"Watersports","description":"Watersports: Est fugit cum.","price":37},
    {"id":295,"name":"Generic Concrete Car","category":"Running","description":"Running: Magnam doloremque illo.","price":939},
    {"id":296,"name":"Rustic Concrete Car","category":"Running","description":"Running: Architecto quod sint.","price":504},
    {"id":297,"name":"Sleek Steel Pants","category":"Soccer","description":"Soccer: Commodi qui culpa.","price":920},
    {"id":298,"name":"Handmade Wooden Bike","category":"Watersports","description":"Watersports: Nisi recusandae quia.","price":469},
    {"id":299,"name":"Handcrafted Plastic Mouse","category":"Soccer","description":"Soccer: Rem et perspiciatis.","price":50},
    {"id":300,"name":"Intelligent Granite Chicken","category":"Running","description":"Running: Quae qui nisi.","price":158},
    {"id":301,"name":"Refined Frozen Pizza","category":"Watersports","description":"Watersports: Molestiae repudiandae harum.","price":206},
    {"id":302,"name":"Sleek Plastic Table","category":"Watersports","description":"Watersports: Ratione et commodi.","price":229},
    {"id":303,"name":"Licensed Plastic Cheese","category":"Soccer","description":"Soccer: Ea voluptate quia.","price":489},
    {"id":304,"name":"Ergonomic Rubber Mouse","category":"Chess","description":"Chess: Maiores eos consectetur.","price":195},
    {"id":305,"name":"Sleek Metal Shoes","category":"Soccer","description":"Soccer: Quas illo voluptatem.","price":552},
    {"id":306,"name":"Licensed Steel Chips","category":"Chess","description":"Chess: Aut consequatur impedit.","price":856},
    {"id":307,"name":"Incredible Metal Car","category":"Soccer","description":"Soccer: Delectus laborum odio.","price":115},
    {"id":308,"name":"Ergonomic Metal Pizza","category":"Watersports","description":"Watersports: Sed aut vero.","price":539},
    {"id":309,"name":"Small Wooden Pants","category":"Running","description":"Running: Architecto qui aspernatur.","price":677},
    {"id":310,"name":"Incredible Fresh Chicken","category":"Chess","description":"Chess: Earum asperiores alias.","price":916},
    {"id":311,"name":"Unbranded Wooden Salad","category":"Running","description":"Running: Blanditiis maxime commodi.","price":101},
    {"id":312,"name":"Incredible Plastic Ball","category":"Chess","description":"Chess: Enim consequatur incidunt.","price":143},
    {"id":313,"name":"Small Wooden Soap","category":"Soccer","description":"Soccer: Consequuntur labore nesciunt.","price":470},
    {"id":314,"name":"Sleek Fresh Shirt","category":"Soccer","description":"Soccer: Vitae nihil aut.","price":492},
    {"id":315,"name":"Small Rubber Sausages","category":"Running","description":"Running: Maiores totam ullam.","price":857},
    {"id":316,"name":"Generic Fresh Hat","category":"Running","description":"Running: Dolores ut et.","price":548},
    {"id":317,"name":"Fantastic Metal Ball","category":"Soccer","description":"Soccer: Ut a soluta.","price":613},
    {"id":318,"name":"Awesome Cotton Bike","category":"Running","description":"Running: Sed est quasi.","price":499},
    {"id":319,"name":"Refined Granite Bacon","category":"Chess","description":"Chess: Fugit est et.","price":7},
    {"id":320,"name":"Gorgeous Cotton Bacon","category":"Soccer","description":"Soccer: Non aut vel.","price":83},
    {"id":321,"name":"Intelligent Concrete Fish","category":"Chess","description":"Chess: Cupiditate et id.","price":50},
    {"id":322,"name":"Refined Wooden Salad","category":"Running","description":"Running: Ab est necessitatibus.","price":984},
    {"id":323,"name":"Gorgeous Soft Chips","category":"Watersports","description":"Watersports: Est qui quo.","price":14},
    {"id":324,"name":"Ergonomic Rubber Ball","category":"Running","description":"Running: Beatae quia consequatur.","price":38},
    {"id":325,"name":"Fantastic Concrete Table","category":"Watersports","description":"Watersports: Magnam reiciendis earum.","price":321},
    {"id":326,"name":"Unbranded Soft Tuna","category":"Watersports","description":"Watersports: Laboriosam reprehenderit et.","price":948},
    {"id":327,"name":"Unbranded Fresh Chips","category":"Running","description":"Running: Ullam aut omnis.","price":194},
    {"id":328,"name":"Handcrafted Rubber Table","category":"Chess","description":"Chess: Inventore voluptatem sint.","price":633},
    {"id":329,"name":"Practical Frozen Shoes","category":"Watersports","description":"Watersports: Et exercitationem laborum.","price":337},
    {"id":330,"name":"Handmade Granite Pants","category":"Chess","description":"Chess: Quidem minima explicabo.","price":464},
    {"id":331,"name":"Intelligent Concrete Soap","category":"Watersports","description":"Watersports: Vitae magnam quo.","price":199},
    {"id":332,"name":"Ergonomic Frozen Gloves","category":"Running","description":"Running: Unde sed itaque.","price":275},
    {"id":333,"name":"Incredible Cotton Mouse","category":"Watersports","description":"Watersports: Dignissimos velit facilis.","price":832},
    {"id":334,"name":"Licensed Fresh Car","category":"Chess","description":"Chess: Aspernatur omnis sunt.","price":208},
    {"id":335,"name":"Generic Soft Chair","category":"Watersports","description":"Watersports: Voluptatem minima vitae.","price":737},
    {"id":336,"name":"Small Steel Table","category":"Chess","description":"Chess: Ut incidunt quas.","price":218},
    {"id":337,"name":"Handcrafted Rubber Cheese","category":"Watersports","description":"Watersports: Sint voluptates molestiae.","price":406},
    {"id":338,"name":"Generic Cotton Soap","category":"Running","description":"Running: Consequatur exercitationem inventore.","price":829},
    {"id":339,"name":"Gorgeous Plastic Chair","category":"Watersports","description":"Watersports: Ratione sed eligendi.","price":649},
    {"id":340,"name":"Generic Cotton Towels","category":"Running","description":"Running: Quia ex sint.","price":470},
    {"id":341,"name":"Practical Rubber Cheese","category":"Running","description":"Running: Maxime ut harum.","price":991},
    {"id":342,"name":"Small Wooden Tuna","category":"Watersports","description":"Watersports: Officia optio perferendis.","price":702},
    {"id":343,"name":"Generic Cotton Table","category":"Chess","description":"Chess: Consequuntur eaque quam.","price":769},
    {"id":344,"name":"Refined Plastic Car","category":"Running","description":"Running: Eaque ex labore.","price":8},
    {"id":345,"name":"Intelligent Concrete Bike","category":"Soccer","description":"Soccer: Dolores fugiat mollitia.","price":195},
    {"id":346,"name":"Practical Fresh Hat","category":"Watersports","description":"Watersports: Excepturi magni possimus.","price":300},
    {"id":347,"name":"Licensed Steel Shoes","category":"Soccer","description":"Soccer: Aspernatur quas et.","price":100},
    {"id":348,"name":"Gorgeous Wooden Pants","category":"Running","description":"Running: Neque et a.","price":916},
    {"id":349,"name":"Generic Fresh Fish","category":"Running","description":"Running: Aut aut nobis.","price":667},
    {"id":350,"name":"Licensed Cotton Gloves","category":"Watersports","description":"Watersports: Delectus aliquam in.","price":145},
    {"id":351,"name":"Intelligent Cotton Chair","category":"Soccer","description":"Soccer: Voluptate omnis rerum.","price":875},
    {"id":352,"name":"Generic Plastic Cheese","category":"Watersports","description":"Watersports: Beatae voluptatem quidem.","price":68},
    {"id":353,"name":"Intelligent Metal Salad","category":"Watersports","description":"Watersports: Cumque dolorum voluptatum.","price":300},
    {"id":354,"name":"Small Steel Mouse","category":"Chess","description":"Chess: Magni et quasi.","price":564},
    {"id":355,"name":"Ergonomic Soft Car","category":"Running","description":"Running: Quos est labore.","price":22},
    {"id":356,"name":"Gorgeous Steel Chair","category":"Watersports","description":"Watersports: Deserunt consectetur quasi.","price":328},
    {"id":357,"name":"Ergonomic Concrete Bacon","category":"Running","description":"Running: Ea sapiente magni.","price":435},
    {"id":358,"name":"Awesome Soft Hat","category":"Chess","description":"Chess: Accusamus sint iusto.","price":132},
    {"id":359,"name":"Fantastic Plastic Towels","category":"Chess","description":"Chess: Nostrum et deleniti.","price":984},
    {"id":360,"name":"Rustic Steel Mouse","category":"Soccer","description":"Soccer: Velit nihil nam.","price":796},
    {"id":361,"name":"Handcrafted Frozen Sausages","category":"Chess","description":"Chess: Deserunt autem odit.","price":68},
    {"id":362,"name":"Rustic Granite Gloves","category":"Soccer","description":"Soccer: Qui nulla nihil.","price":54},
    {"id":363,"name":"Incredible Soft Bacon","category":"Soccer","description":"Soccer: Dicta illum ipsa.","price":716},
    {"id":364,"name":"Generic Soft Mouse","category":"Chess","description":"Chess: Velit excepturi omnis.","price":621},
    {"id":365,"name":"Small Frozen Ball","category":"Watersports","description":"Watersports: Amet et quia.","price":884},
    {"id":366,"name":"Generic Concrete Sausages","category":"Soccer","description":"Soccer: Omnis quos et.","price":84},
    {"id":367,"name":"Gorgeous Metal Chips","category":"Running","description":"Running: Assumenda ad molestias.","price":339},
    {"id":368,"name":"Licensed Frozen Car","category":"Watersports","description":"Watersports: Sed architecto incidunt.","price":383},
    {"id":369,"name":"Licensed Cotton Computer","category":"Chess","description":"Chess: Consequuntur dicta consequuntur.","price":786},
    {"id":370,"name":"Licensed Frozen Bike","category":"Soccer","description":"Soccer: Eveniet dolor vel.","price":911},
    {"id":371,"name":"Rustic Rubber Towels","category":"Chess","description":"Chess: Animi porro atque.","price":357},
    {"id":372,"name":"Gorgeous Fresh Cheese","category":"Watersports","description":"Watersports: Cum minima iste.","price":181},
    {"id":373,"name":"Incredible Wooden Mouse","category":"Watersports","description":"Watersports: Minima aut et.","price":306},
    {"id":374,"name":"Gorgeous Granite Gloves","category":"Soccer","description":"Soccer: Sed corrupti qui.","price":534},
    {"id":375,"name":"Ergonomic Soft Computer","category":"Watersports","description":"Watersports: Vero qui maxime.","price":138},
    {"id":376,"name":"Handmade Cotton Keyboard","category":"Watersports","description":"Watersports: Suscipit ipsum aspernatur.","price":413},
    {"id":377,"name":"Gorgeous Fresh Towels","category":"Running","description":"Running: Omnis recusandae reiciendis.","price":491},
    {"id":378,"name":"Handmade Granite Pants","category":"Running","description":"Running: Fugit neque quam.","price":401},
    {"id":379,"name":"Licensed Wooden Car","category":"Soccer","description":"Soccer: Repellendus sed omnis.","price":64},
    {"id":380,"name":"Practical Wooden Car","category":"Watersports","description":"Watersports: Et dolorem sit.","price":269},
    {"id":381,"name":"Awesome Rubber Salad","category":"Running","description":"Running: Est nemo aspernatur.","price":46},
    {"id":382,"name":"Small Rubber Chips","category":"Watersports","description":"Watersports: Officiis ut tempora.","price":510},
    {"id":383,"name":"Handcrafted Wooden Mouse","category":"Soccer","description":"Soccer: Numquam dicta laboriosam.","price":577},
    {"id":384,"name":"Unbranded Plastic Chicken","category":"Watersports","description":"Watersports: Omnis perspiciatis voluptatem.","price":515},
    {"id":385,"name":"Gorgeous Granite Table","category":"Running","description":"Running: Ut quam sit.","price":986},
    {"id":386,"name":"Licensed Wooden Cheese","category":"Soccer","description":"Soccer: Ut in quia.","price":353},
    {"id":387,"name":"Ergonomic Metal Keyboard","category":"Chess","description":"Chess: Quod officia laudantium.","price":435},
    {"id":388,"name":"Gorgeous Plastic Mouse","category":"Chess","description":"Chess: Sit nobis ab.","price":481},
    {"id":389,"name":"Unbranded Frozen Towels","category":"Soccer","description":"Soccer: Est in voluptatem.","price":184},
    {"id":390,"name":"Tasty Plastic Bacon","category":"Soccer","description":"Soccer: At natus aliquam.","price":91},
    {"id":391,"name":"Incredible Concrete Car","category":"Soccer","description":"Soccer: Unde ut occaecati.","price":565},
    {"id":392,"name":"Handcrafted Metal Shirt","category":"Watersports","description":"Watersports: Voluptates dolores deleniti.","price":869},
    {"id":393,"name":"Incredible Rubber Tuna","category":"Chess","description":"Chess: Officiis doloremque perferendis.","price":277},
    {"id":394,"name":"Ergonomic Fresh Shoes","category":"Watersports","description":"Watersports: Deserunt nisi in.","price":435},
    {"id":395,"name":"Small Frozen Soap","category":"Watersports","description":"Watersports: Similique est rerum.","price":13},
    {"id":396,"name":"Licensed Metal Pizza","category":"Running","description":"Running: Maiores rem consectetur.","price":999},
    {"id":397,"name":"Sleek Metal Chicken","category":"Watersports","description":"Watersports: Et non natus.","price":662},
    {"id":398,"name":"Unbranded Rubber Bike","category":"Chess","description":"Chess: Ex accusamus reiciendis.","price":813},
    {"id":399,"name":"Awesome Frozen Shirt","category":"Soccer","description":"Soccer: Perspiciatis rem aliquid.","price":235},
    {"id":400,"name":"Tasty Wooden Chips","category":"Running","description":"Running: Deleniti alias vel.","price":483},
    {"id":401,"name":"Generic Steel Ball","category":"Running","description":"Running: Consequatur soluta nulla.","price":845},
    {"id":402,"name":"Intelligent Steel Pants","category":"Soccer","description":"Soccer: Explicabo mollitia eligendi.","price":155},
    {"id":403,"name":"Small Rubber Shoes","category":"Chess","description":"Chess: Dignissimos mollitia qui.","price":120},
    {"id":404,"name":"Licensed Granite Shirt","category":"Soccer","description":"Soccer: Quia enim quo.","price":735},
    {"id":405,"name":"Unbranded Steel Fish","category":"Chess","description":"Chess: Quia ratione eum.","price":64},
    {"id":406,"name":"Unbranded Soft Gloves","category":"Watersports","description":"Watersports: Accusantium et ad.","price":410},
    {"id":407,"name":"Sleek Soft Hat","category":"Soccer","description":"Soccer: Ducimus sit qui.","price":646},
    {"id":408,"name":"Small Soft Bacon","category":"Soccer","description":"Soccer: Velit ut atque.","price":858},
    {"id":409,"name":"Gorgeous Metal Computer","category":"Running","description":"Running: Ipsa et libero.","price":303},
    {"id":410,"name":"Incredible Steel Tuna","category":"Soccer","description":"Soccer: Perspiciatis adipisci modi.","price":280},
    {"id":411,"name":"Unbranded Plastic Hat","category":"Soccer","description":"Soccer: Fugiat molestiae beatae.","price":410},
    {"id":412,"name":"Sleek Rubber Fish","category":"Chess","description":"Chess: Magnam facilis sequi.","price":655},
    {"id":413,"name":"Small Rubber Car","category":"Chess","description":"Chess: Qui sed nemo.","price":661},
    {"id":414,"name":"Incredible Frozen Gloves","category":"Running","description":"Running: Qui ipsa nulla.","price":384},
    {"id":415,"name":"Small Rubber Chicken","category":"Chess","description":"Chess: Autem voluptas corporis.","price":535},
    {"id":416,"name":"Fantastic Rubber Table","category":"Soccer","description":"Soccer: Veritatis repudiandae dolores.","price":334},
    {"id":417,"name":"Small Soft Computer","category":"Soccer","description":"Soccer: Ullam voluptate quae.","price":544},
    {"id":418,"name":"Refined Granite Cheese","category":"Soccer","description":"Soccer: Error voluptates ut.","price":586},
    {"id":419,"name":"Practical Soft Table","category":"Soccer","description":"Soccer: Nulla maxime maxime.","price":373},
    {"id":420,"name":"Ergonomic Concrete Car","category":"Soccer","description":"Soccer: Id dolorem qui.","price":230},
    {"id":421,"name":"Gorgeous Rubber Sausages","category":"Soccer","description":"Soccer: Totam est vel.","price":362},
    {"id":422,"name":"Tasty Rubber Chips","category":"Chess","description":"Chess: Soluta optio odio.","price":165},
    {"id":423,"name":"Licensed Granite Car","category":"Watersports","description":"Watersports: Corporis odio laudantium.","price":589},
    {"id":424,"name":"Handcrafted Rubber Fish","category":"Chess","description":"Chess: Et quibusdam sint.","price":774},
    {"id":425,"name":"Handcrafted Metal Shoes","category":"Watersports","description":"Watersports: Qui esse accusantium.","price":986},
    {"id":426,"name":"Refined Frozen Cheese","category":"Soccer","description":"Soccer: Voluptate unde eos.","price":791},
    {"id":427,"name":"Fantastic Steel Ball","category":"Watersports","description":"Watersports: Eius voluptatem eius.","price":497},
    {"id":428,"name":"Incredible Rubber Shoes","category":"Soccer","description":"Soccer: Beatae quasi molestiae.","price":342},
    {"id":429,"name":"Tasty Soft Soap","category":"Soccer","description":"Soccer: Neque autem corporis.","price":977},
    {"id":430,"name":"Incredible Wooden Soap","category":"Running","description":"Running: Consequatur ut porro.","price":97},
    {"id":431,"name":"Generic Steel Shirt","category":"Chess","description":"Chess: Ullam tempore dicta.","price":5},
    {"id":432,"name":"Tasty Steel Towels","category":"Watersports","description":"Watersports: Rem eos harum.","price":42},
    {"id":433,"name":"Small Cotton Tuna","category":"Soccer","description":"Soccer: Doloribus doloribus eius.","price":515},
    {"id":434,"name":"Incredible Soft Chicken","category":"Chess","description":"Chess: Eius nemo ipsam.","price":883},
    {"id":435,"name":"Incredible Soft Bacon","category":"Soccer","description":"Soccer: Iure atque repudiandae.","price":545},
    {"id":436,"name":"Intelligent Cotton Mouse","category":"Watersports","description":"Watersports: Rerum ea omnis.","price":872},
    {"id":437,"name":"Ergonomic Steel Bacon","category":"Running","description":"Running: Repellendus voluptatum nulla.","price":679},
    {"id":438,"name":"Incredible Frozen Chair","category":"Soccer","description":"Soccer: Saepe et enim.","price":985},
    {"id":439,"name":"Small Wooden Bike","category":"Soccer","description":"Soccer: Iusto soluta et.","price":313},
    {"id":440,"name":"Sleek Cotton Car","category":"Running","description":"Running: Ea delectus impedit.","price":3},
    {"id":441,"name":"Ergonomic Wooden Chicken","category":"Chess","description":"Chess: Atque ducimus illo.","price":665},
    {"id":442,"name":"Tasty Plastic Towels","category":"Soccer","description":"Soccer: Architecto culpa tempore.","price":908},
    {"id":443,"name":"Refined Concrete Pizza","category":"Running","description":"Running: Beatae maxime velit.","price":371},
    {"id":444,"name":"Tasty Granite Keyboard","category":"Soccer","description":"Soccer: Qui natus ab.","price":411},
    {"id":445,"name":"Incredible Metal Tuna","category":"Watersports","description":"Watersports: Animi nemo dolor.","price":543},
    {"id":446,"name":"Sleek Steel Chips","category":"Chess","description":"Chess: Aut omnis dignissimos.","price":4},
    {"id":447,"name":"Handcrafted Metal Computer","category":"Chess","description":"Chess: Sunt saepe quia.","price":752},
    {"id":448,"name":"Ergonomic Granite Shirt","category":"Watersports","description":"Watersports: Harum porro quia.","price":154},
    {"id":449,"name":"Sleek Rubber Hat","category":"Chess","description":"Chess: Ullam eligendi dolorem.","price":491},
    {"id":450,"name":"Licensed Concrete Bacon","category":"Running","description":"Running: Sequi sequi velit.","price":409},
    {"id":451,"name":"Licensed Rubber Cheese","category":"Soccer","description":"Soccer: Et ut distinctio.","price":500},
    {"id":452,"name":"Licensed Plastic Cheese","category":"Running","description":"Running: Est excepturi perferendis.","price":406},
    {"id":453,"name":"Sleek Frozen Keyboard","category":"Chess","description":"Chess: Hic magni et.","price":259},
    {"id":454,"name":"Tasty Rubber Salad","category":"Chess","description":"Chess: Amet quis molestiae.","price":298},
    {"id":455,"name":"Handcrafted Wooden Hat","category":"Soccer","description":"Soccer: Sit animi unde.","price":199},
    {"id":456,"name":"Gorgeous Plastic Bike","category":"Running","description":"Running: Asperiores et aspernatur.","price":60},
    {"id":457,"name":"Small Frozen Soap","category":"Running","description":"Running: Rerum ut omnis.","price":25},
    {"id":458,"name":"Handcrafted Plastic Chicken","category":"Watersports","description":"Watersports: Aliquid sunt et.","price":305},
    {"id":459,"name":"Refined Fresh Bacon","category":"Chess","description":"Chess: Eos est neque.","price":220},
    {"id":460,"name":"Intelligent Cotton Chair","category":"Chess","description":"Chess: Sit non quidem.","price":535},
    {"id":461,"name":"Incredible Wooden Chair","category":"Soccer","description":"Soccer: Consequatur qui eius.","price":794},
    {"id":462,"name":"Fantastic Cotton Keyboard","category":"Running","description":"Running: Nam dolor nesciunt.","price":677},
    {"id":463,"name":"Tasty Metal Shirt","category":"Soccer","description":"Soccer: Dicta doloribus error.","price":310},
    {"id":464,"name":"Small Wooden Mouse","category":"Running","description":"Running: Quo perspiciatis aliquam.","price":762},
    {"id":465,"name":"Tasty Metal Chips","category":"Soccer","description":"Soccer: Et voluptatem quia.","price":282},
    {"id":466,"name":"Rustic Wooden Chips","category":"Chess","description":"Chess: Illum aut optio.","price":311},
    {"id":467,"name":"Rustic Granite Table","category":"Soccer","description":"Soccer: Voluptatibus quis beatae.","price":68},
    {"id":468,"name":"Unbranded Plastic Ball","category":"Watersports","description":"Watersports: Vel et ea.","price":795},
    {"id":469,"name":"Awesome Fresh Pants","category":"Watersports","description":"Watersports: Quia quam aut.","price":864},
    {"id":470,"name":"Handmade Wooden Towels","category":"Soccer","description":"Soccer: Non enim sint.","price":795},
    {"id":471,"name":"Practical Soft Chicken","category":"Chess","description":"Chess: Tempore nulla enim.","price":212},
    {"id":472,"name":"Generic Rubber Shoes","category":"Watersports","description":"Watersports: Eius architecto et.","price":516},
    {"id":473,"name":"Rustic Wooden Cheese","category":"Chess","description":"Chess: Qui totam labore.","price":511},
    {"id":474,"name":"Generic Steel Computer","category":"Soccer","description":"Soccer: Delectus ratione doloribus.","price":802},
    {"id":475,"name":"Handcrafted Granite Gloves","category":"Chess","description":"Chess: Sed beatae qui.","price":635},
    {"id":476,"name":"Unbranded Frozen Pants","category":"Chess","description":"Chess: Aut adipisci modi.","price":956},
    {"id":477,"name":"Incredible Granite Salad","category":"Running","description":"Running: Et occaecati qui.","price":745},
    {"id":478,"name":"Tasty Soft Table","category":"Running","description":"Running: Sunt eius eum.","price":738},
    {"id":479,"name":"Fantastic Rubber Bacon","category":"Running","description":"Running: Consectetur sapiente aut.","price":51},
    {"id":480,"name":"Sleek Frozen Computer","category":"Chess","description":"Chess: Sint et dignissimos.","price":785},
    {"id":481,"name":"Unbranded Fresh Ball","category":"Soccer","description":"Soccer: Quis dolor eveniet.","price":564},
    {"id":482,"name":"Incredible Concrete Tuna","category":"Chess","description":"Chess: Exercitationem voluptate quibusdam.","price":765},
    {"id":483,"name":"Tasty Steel Computer","category":"Watersports","description":"Watersports: Perspiciatis et in.","price":79},
    {"id":484,"name":"Rustic Concrete Car","category":"Watersports","description":"Watersports: Eaque dolorem ab.","price":125},
    {"id":485,"name":"Sleek Cotton Hat","category":"Watersports","description":"Watersports: Aliquam ad neque.","price":552},
    {"id":486,"name":"Ergonomic Fresh Shoes","category":"Soccer","description":"Soccer: Eos eum provident.","price":161},
    {"id":487,"name":"Fantastic Rubber Towels","category":"Chess","description":"Chess: Sit veritatis rerum.","price":386},
    {"id":488,"name":"Generic Concrete Gloves","category":"Running","description":"Running: Soluta dicta illum.","price":685},
    {"id":489,"name":"Unbranded Soft Fish","category":"Running","description":"Running: Sunt ut quia.","price":280},
    {"id":490,"name":"Unbranded Plastic Mouse","category":"Watersports","description":"Watersports: Doloribus inventore autem.","price":832},
    {"id":491,"name":"Generic Fresh Soap","category":"Watersports","description":"Watersports: Neque corrupti animi.","price":721},
    {"id":492,"name":"Incredible Granite Table","category":"Soccer","description":"Soccer: Totam rerum eveniet.","price":820},
    {"id":493,"name":"Licensed Concrete Table","category":"Watersports","description":"Watersports: Maxime iusto nam.","price":944},
    {"id":494,"name":"Rustic Concrete Pizza","category":"Chess","description":"Chess: Inventore voluptates dolor.","price":624},
    {"id":495,"name":"Handcrafted Fresh Soap","category":"Chess","description":"Chess: Error eaque asperiores.","price":122},
    {"id":496,"name":"Unbranded Soft Shoes","category":"Chess","description":"Chess: Voluptatem dolor dolor.","price":736},
    {"id":497,"name":"Incredible Metal Computer","category":"Watersports","description":"Watersports: In magnam doloribus.","price":404},
    {"id":498,"name":"Small Rubber Bike","category":"Running","description":"Running: Harum cupiditate deleniti.","price":499},
    {"id":499,"name":"Awesome Plastic Gloves","category":"Watersports","description":"Watersports: Omnis nihil harum.","price":942},
    {"id":500,"name":"Generic Frozen Towels","category":"Watersports","description":"Watersports: Commodi aperiam omnis.","price":702},
    {"id":501,"name":"Sleek Steel Soap","category":"Soccer","description":"Soccer: Et dolores ut.","price":418},
    {"id":502,"name":"Tasty Steel Fish","category":"Chess","description":"Chess: Alias nam quia.","price":362},
    {"id":503,"name":"Awesome Soft Car","category":"Chess","description":"Chess: Amet vel asperiores.","price":970}
            ]
        ';
        $products = json_decode($data);
        foreach ($products as $p) {
            $rec = new Products();
            $rec->id = $p->id;
            $rec->name = $p->name;
            $rec->category = $p->category;
            $rec->description = $p->description;
            $rec->price = $p->price;
            try {
                $rec->save();
                echo "saved " , $rec->id ,  "\n";
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }
        }
    }
}
