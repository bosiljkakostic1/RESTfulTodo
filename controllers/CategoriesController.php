<?php
namespace app\controllers;

use yii\rest\ActiveController;

class CategoriesController extends ActiveController
{
    //public $enableCsrfValidation = false;
    public $modelClass = 'app\models\Categories';
/**
 * List of allowed domains.
 * Note: Restriction works only for AJAX (using CORS, is not secure).
 *
 * @return array List of domains, that can access to this API
 */
    public static function allowedDomains()
    {
        return [
        // '*',                        // star allows all domains
        'http://localhost:3000',
        ];
    }

/**
 * @inheritdoc
 */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

        // For cross-domain AJAX request
        'corsFilter'  => [
            'class' => \yii\filters\Cors::className(),
            'cors'  => [
                // restrict access to domains:
                'Origin'                           => static::allowedDomains(),
                'Access-Control-Request-Method'    => ['GET','POST'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'           => 3600,                 // Cache (seconds)
            ],
        ],

        ]);
    }
}
