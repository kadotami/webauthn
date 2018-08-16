<?php

namespace app\controllers\api;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use yii\web\Response;

class BaseApiController extends Controller
{

    /**
     * 初期設定
     */
    public function init()
    {
        parent::init();

        $this->enableCsrfValidation = false;
        Yii::$app->response->charset = 'utf-8';
        Yii::$app->response->setStatusCode(200);
        $headers = Yii::$app->response->headers;
        $headers->set('Pragma', 'no-cache');
        $headers->add('Cache-Control', 'no-store, no-cache, must-revalidate');
        $headers->add('Cache-Control', 'post-check=0, pre-check=0');
        $headers->add('Access-Control-Allow-Credentials', true);
        Yii::$app->request->parsers = [
            'application/json' => 'yii\web\JsonParser',
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return array_merge(parent::behaviors(), [
            'corsFilter'  => [
                'class' => \yii\filters\Cors::className(),
                'cors'  => [
                    'Origin'      => ['*'],
                    'Access-Control-Request-Method'    => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age'           => 3600,
                ],
            ],

        ]);
    }
    /**
     * afterAction
     * @param \yii\base\Action $action
     * @param mixed $result
     * @return mixed
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        $result = Json::encode($result);
        return $result;
    }
}