<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$session = Yii::$app->session;
$language = $session->get('language') ? $session->get('language') : 'en';
\Yii::$app->language = $language;
$model->language = $model->language ? $model->language : $language ;
$this->title = 'Signup';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="site-signup">
    <h3><?= Html::encode(\Yii::t('app', $this->title)) ?></h3>
    <p><?= \Yii::t('app', 'Please fill out the following fields to signup:')?></p>

    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>
                <?= $form->field($model, 'language')->DropDownList(
                    ['en' => 'en', 'sr-LT' => 'sr-LT', 'cz' => 'cz'],
                    ['onchange' => 'changeLanguage(this.value)', 'autofocus' => true],
                )->label(\Yii::t('app', 'Prefered Language'));?>
                <?= Html::hiddenInput('chLangOnly', 'false', ['id' => 'chLangOnly']) ?>
                    <?= $form->field($model, 'username', ['labelOptions' => ['class' => 'red-star']])
                    ->textInput(['placeholder' =>\Yii::t('app', 'Enter Your User Name')])
                    ->label(\Yii::t('app', 'User Name')) ?>

                    <?= $form->field($model, 'email', ['labelOptions' => ['class' => 'red-star']])
                    ->input(['placeholder' => \Yii::t('app', 'Enter Your Email')]) ?>

                    <?= $form->field($model, 'password', ['labelOptions' => ['class' => 'red-star']])
                    ->passwordInput(['placeholder' => \Yii::t('app', 'Enter Your Password')])
                    ->label(\Yii::t('app', 'Password')) ?>
                    <?= $form->field($model, 'passwordRepeat', ['labelOptions' => ['class' => 'red-star']])
                    ->passwordInput(['placeholder' => \Yii::t('app', 'Repeat Your Password')])
                    ->label(\Yii::t('app', 'Repeat Your Password')) ?>
                    <?= $form->field($model, 'telefon_number')
                    ->passwordInput(['placeholder' => \Yii::t('app', 'Enter Your telefon number (optionaly)')])
                    ->label(\Yii::t('app', 'Telefon Number')) ?>
                <div class="form-group">
                    <?= Html::submitButton(\Yii::t('app', 'Signup'), ['class' => 'btn btn-primary',
                    'name' => 'signup-button']) ?>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
<script>
    window.onload = function() {
        console.log('<?= $language ?>');
    };
</script>
