<?php

namespace app\controllers\api;

use Yii;
use app\controllers\api\BaseApiController;
use \yii\helpers\Json;
use \yii\web\Cookie;
use \CBOR\CBOREncoder;
use \yii\base\Exception;

class AuthController extends BaseApiController
{
    public $modelClass = 'app\models\User';
    const RPID = 'kdtm.com';

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
                'id' => self::RPID,
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
        $attestationObject = $this->bufferArrayToObject($data['response']['attestationObject']);
        
        if (!$this->isValidClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!!");
        }

        $authData = $attestationObject['authData'];
        $authData_byte = $authData->get_byte_string();
        $authData_byte_array = unpack('C*',$authData_byte);

        $rpid_hash = array_slice($authData_byte_array, 0, 32);
        $flag = str_pad(dechex($authData_byte_array[32]), 8, 0, STR_PAD_LEFT);
        $counter = array_slice($authData_byte_array, 33, 4);

        if(!$this->isValidRPID($rpid_hash)) {
            throw new Exception("invalid!!!");
        }



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

    private function bufferArrayToObject($buffer_array)
    {
        $array = json_decode($buffer_array, true);
        $CBORstring = implode(array_map("chr", $array));
        $data = CBOREncoder::decode($CBORstring);
        return $data;
    }

    /*
    * ClientDataJSONが正しいかどうかチェック
    * 
    */
    private function isValidClientDataJSON($json)
    {
        $challenge = Yii::$app->session->get('wa-challenge');
        if($json['type'] !== "webauthn.create"
         || $json['origin'] !== "https://webauthn.kdtm.com"
         || base64_decode($json['challenge']) !== $challenge ) {
            return false;
        }
        return true;
    } 

    /*
    *
    * hashで送られてくるripdのsha256が正しいかどうかをチェックする
    */
    private function isValidRPID($rpid_hash)
    {
        $rpid = '';
        foreach($rpid_hash as $num) {
            $rpid = $rpid . str_pad(dechex($num), 2, 0, STR_PAD_LEFT);
        }
        if($rpid !== hash("sha256", self::RPID)){
            return false;
        }
        return true;
    }
}