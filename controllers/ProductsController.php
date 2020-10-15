<?php
namespace app\controllers;

use yii\rest\Controller;
use app\models\Products;
use yii\data\ActiveDataProvider;
use yii\data\ActiveDataFilter;

class ProductsController extends Controller
{
    //public $enableCsrfValidation = false;
    //public $modelClass = 'app\models\Products';

    public function actionIndex()
    {
        $filter = new ActiveDataFilter([
            'searchModel' => 'app\models\ProductsSearch'
        ]);

        $filterCondition = null;
        if ($filter->load(\Yii::$app->request->get())) {
            $filterCondition = $filter->build();
            if ($filterCondition === false) {
                return $filter;
            }
        }

        $query = Products::find();
        if ($filterCondition !== null) {
            $query->andWhere($filterCondition);
        }

        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

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
                'Access-Control-Request-Method'    => ['GET','POST'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age'           => 3600,
                'Access-Control-Expose-Headers' => ['X-Pagination-Total-Count', 'X-Pagination-Page-Count',
                'X-Pagination-Per-Page', 'X-Pagination-Current-Page', 'Link'],                // Cache (seconds)
            ],
        ],

        ]);
    }
}
