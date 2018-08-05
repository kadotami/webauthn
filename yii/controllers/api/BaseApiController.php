<?php

namespace app\controllers\api;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;

class BaseApiController extends Controller
{

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
        $result = Json::encode($result);
        return $result;
    }
}