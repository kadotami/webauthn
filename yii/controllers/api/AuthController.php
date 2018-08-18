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
                'id' => json_encode(unpack('C*', 'kdtm@test.com')),
                'name' => "mondamin",
                'displayName' => "mondamin"
            ],
            'pubKeyCredParams'=> [ 
                [
                    'type' => "public-key",
                    'alg'  => -7
                ]
            ],
            // 'attestation' => "direct",
        ];
    }

    public function actionRegisterCredential()
    {
        $data = Yii::$app->request->post();
        $rawid = $array = json_decode($data['raw_id'], true);
        $clientDataJSON = $this->bufferArrayToJsonArray(json_decode($data['response']['clientDataJSON'], true));
        $attestationObject = $this->bufferArrayToCBORObject(json_decode($data['response']['attestationObject'], true));
        
        if (!$this->isValidClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!!");
        }

        Yii::error($attestationObject);

        $authData = $attestationObject['authData'];
        $authData_byte = $authData->get_byte_string();
        $authData_byte_array = unpack('C*',$authData_byte);

        $rpid_hash = array_slice($authData_byte_array, 0, 32);
        // bufferArrayは0から始まらないことに注意
        $flag = str_pad(decbin($authData_byte_array[33]), 8, 0, STR_PAD_LEFT);
        $counter = $this->convertEndian(array_slice($authData_byte_array, 33, 4));

        if(!$this->isValidRPID($rpid_hash)) {
            throw new Exception("invalid!!!");
        }

        $user_present = substr($flag,7,1);
        $user_verified = substr($flag,5,1);
        $attested_credential_data = substr($flag,1,1);
        $extension_data_included = substr($flag,0,1);

        if($attested_credential_data) {
            $aaguid              = array_slice($authData_byte_array, 37, 16);
            $credentialIdLength  = array_slice($authData_byte_array, 53, 2);
            $credentialIdLength = $this->convertEndian($credentialIdLength);
            $credentialId = array_slice($authData_byte_array, 55, $credentialIdLength);
            $credentialPublicKey = array_slice($authData_byte_array, 55 + $credentialIdLength);

            Yii::$app->session->set('wa-credential-id', $credentialId);
            Yii::error($credentialId);
            if($this->convertHex($credentialId) !== $this->convertHex($rawid)){
                throw new Exception("invalid!!! not match credential id");
            }
            $this->publicKey($credentialPublicKey);

        }


        return true;
    }

    private function publicKey($credentialPublicKey)
    {
        $publickey_json = $this->bufferArrayToCBORObject($credentialPublicKey);
        // key is [1,3,-1,-2,-3]
        $x = unpack('C*',$publickey_json['-2']->get_byte_string());
        $y = unpack('C*',$publickey_json['-3']->get_byte_string());

        Yii::error($x);
        Yii::error($y);
        $z = array_merge([4],$x,$y);
        Yii::error($z);

    }

    public function actionLoginChallenge()
    {
        $data = Yii::$app->request->post();
        $random_str = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
        $array = unpack('C*', $random_str);
        $credentialId = Yii::$app->session->get('wa-credential-id');
        

        $challenge = json_encode($array);
        Yii::$app->session->set('wa-challenge', $challenge);
        return [
            'challenge' => $challenge,
            'allowCredentials' => [
                [
                    'type' => "public-key",
                    'id' => json_encode($credentialId),
                ],
            ],
        ];
    }

    private function bufferArrayToJsonArray($buffer_array)
    {
        $json = implode(array_map("chr", $buffer_array));
        $json = json_decode($json, true);
        return $json;
    }

    private function bufferArrayToCBORObject($buffer_array)
    {
        $CBORstring = implode(array_map("chr", $buffer_array));
        $data = CBOREncoder::decode($CBORstring);
        return $data;
    }

    /*
    * byte arrayをbig endianになおして数値化する
    */
    private function convertEndian($byte_array)
    {
        $value = '';
        foreach($byte_array as $num) {
            $value = $value . str_pad(decbin($num), 8, 0, STR_PAD_LEFT);
        }
        $value = bindec($value);
        return $value;
    }

    /*
    * byte arrayを16進数文字列にする
    */
    private function convertHex($byte_array)
    {
        $value = '';
        foreach($byte_array as $num) {
            $value = $value . str_pad(dechex($num), 2, 0, STR_PAD_LEFT);
        }
        return $value;
    }

    /*
    *
    * hashで送られてくるripdのsha256が正しいかどうかをチェックする
    */
    private function isValidRPID($rpid_hash)
    {
        $rpid = $this->convertHex($rpid_hash);
        if($rpid !== hash("sha256", self::RPID)){
            return false;
        }
        return true;
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
}