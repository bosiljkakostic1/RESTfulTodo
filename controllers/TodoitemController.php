<?php
namespace app\controllers;

use yii\rest\ActiveController;
use app\models\Todoitem;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ActiveDataFilter;

class TodoitemController extends ActiveController
{
    //public $enableCsrfValidation = false;
    public $modelClass = 'app\models\Todoitem';
    /**
     * List of allowed domains.
     * Note: Restriction works only for AJAX (using CORS, is not secure).
     *
     * @return array List of domains, that can access to this API
     */
    public static function allowedDomains()
    {
        return ["http://localhost:3000",];
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
                'Access-Control-Request-Method'    => ['GET','HEAD', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'           => 3600,
                'Access-Control-Allow-Headers'     => ['*'],
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Total-Count',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page', 'X-Pagination-Current-Page', 'Link',
                ],                // Cache (seconds)
            ],
        ],

        ]);
    }
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        return $actions;
    }

    public function actionIndex()
    {
        $filter = new ActiveDataFilter([
            'searchModel' => 'app\models\TodoitemSearch'
            ]);
            $filterCondition = null;
            // You may load filters from any source. For example,
            // if you prefer JSON in request body,
            // use Yii::$app->request->getBodyParams() below:
        if ($filter->load(\Yii::$app->request->get())) {
            $filterCondition = $filter->build();
            
            if ($filterCondition === false) {
            // Serializer would get errors out of it
                return $filter;
            }
        }
        $query = Todoitem::find();
        if ($filterCondition !== null) {
            $query->andWhere($filterCondition);
        }
        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }
}
