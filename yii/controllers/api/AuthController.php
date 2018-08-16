<?php

namespace app\controllers\api;

use Yii;
use app\controllers\api\BaseApiController;
use yii\helpers\Json;
use \yii\web\Cookie;

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
        $challenge = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
        $array = unpack('C*', $challenge);
        Yii::$app->session->set('wa-challenge', $challenge);

        $challenge = json_encode($array);
        return [
            'challenge' => $challenge,
            'rp' => [
                'id' => 'kdtm.com',
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

    public function actionRegisterCredential()
    {
        $data = Yii::$app->request->post();
        $clientDataJSON = $this->bufferArrayToJsonArray($data['response']['clientDataJSON']);
        $attestationObject = $this->bufferArrayToString($data['response']['attestationObject']);
        if (!$this->isValidClientDataJSON($clientDataJSON)) {
        }
        Yii::error($attestationObject);
        return $clientDataJSON;
    }

    public function actionLoginChallenge()
    {
        $data = Yii::$app->request->post();
        $random_str = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
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

    private function bufferArrayToJsonArray($buffer_array)
    {
        $array = json_decode($buffer_array, true);
        $json = implode(array_map("chr", $array));
        $json = json_decode($json, true);
        return $json;
    }

    private function bufferArrayToString($buffer_array)
    {
        $array = json_decode($buffer_array, true);
        $json = implode(array_map("chr", $array));
        return $json;
    }

    private function isValidClientDataJSON($json)
    {
        $challenge = Yii::$app->session->get('wa-challenge');
        Yii::error($challenge);
        if($json['type'] !== "webauthn.create"
         || $json['origin'] !== "https://webauthn.kdtm.com"
         || base64_decode($json['challenge']) !== $challenge ) {
            return false;
        }
        return true;
    } 
}