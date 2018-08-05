<?php

namespace app\controllers\api;

use Yii;
use app\controllers\api\BaseApiController;
use yii\helpers\Json;

class AuthController extends BaseApiController
{
    public $modelClass = 'app\models\User';

    /**
     * 
     *
     * @return array
     */
    public function actionIndex()
    {
        return [
            'member' => 'test',
        ];
    }

    public function actionRegisterChallenge()
    {
        $data = Yii::$app->request->post();
        $random_str = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 32);
        $array = unpack('C*', $random_str);
        $challenge = json_encode($array);
        Yii::$app->session->set('wa-challenge', $challenge);
        return [
            'challenge' => $challenge,
            'rp' => [
                // 'id' => 'https://webauthn.kdtm.jp',
                'name' => 'WebAuthnTest'
            ],
            'user' => [
                'id' => '',
                'name' => "mondamin",
                'displayName' => "mondamin"
            ],
            'pubKeyCredParams'=> [ 
                [
                    'type' => "public-key",
                    'alg'  => -7
                ]
            ]
        ];
    }

    public function actionLoginChallenge()
    {
        $data = Yii::$app->request->post();
        $random_str = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, 32);
        $array = unpack('C*', $random_str);
        $challenge = json_encode($array);
        Yii::$app->session->set('wa-challenge', $challenge);
        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => 'WebAuthnTest'
            ],
            'pubKeyCredParams'=> [ 
                [
                    'type' => "public-key",
                    'alg'  => -7
                ]
            ]
        ];
    }

    public function actionRegisterCredential()
    {
        $data = Yii::$app->request->post();
        return $data['test'];
    }
}