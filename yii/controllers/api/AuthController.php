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
    const RPID = 'webauthn.kdtm.com';

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
        Yii::$app->session->set('wa-username', $data['email']);

        $challenge = json_encode($array);
        return [
            'challenge' => $challenge,
            'rp' => [
                // 'id' => self::RPID,
                'name' => 'WebAuthnTest',
            ],
            'user' => [
                'id' => json_encode(unpack('C*', $data['email'])),
                'name' => "mondamin",
                'displayName' => "mondamin"
            ],
            'pubKeyCredParams'=> [
                [
                    'type' => "public-key",
                    'alg'  => -7,
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
        
        if($data['email'] !==  Yii::$app->session->get('wa-username')) {
            throw new Exception("invalid!!! email is not correct");
        }

        if (!$this->isValidRegistrationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!!");
        }

        $authData = $attestationObject['authData'];
        $authData_byte = $authData->get_byte_string();
        $authData_byte_array = unpack('C*',$authData_byte);

        $rpid_hash = array_slice($authData_byte_array, 0, 32);
        // bufferArrayは0から始まらないことに注意
        $flag = str_pad(decbin($authData_byte_array[33]), 8, 0, STR_PAD_LEFT);
        $counter = $this->convertEndian(array_slice($authData_byte_array, 33, 4));

        if(!$this->isValidRPID($rpid_hash)) {
            throw new Exception("invalid!!! not match rpid");
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

            if($this->convertHex($credentialId) !== $this->convertHex($rawid)){
                throw new Exception("invalid!!! not match credential id");
            }

            $pubkey_string = $this->convertHex($this->publicKey($credentialPublicKey));

            $this->createUser('test', $this->convertHex($credentialId), $pubkey_string);
        }


        return [
            "result" => 'ok'
        ];
    }

    private function publicKey($credentialPublicKey)
    {
        $publickey_json = $this->bufferArrayToCBORObject($credentialPublicKey);
        // key is [1,3,-1,-2,-3]
        $x = unpack('C*',$publickey_json['-2']->get_byte_string());
        $y = unpack('C*',$publickey_json['-3']->get_byte_string());
        $z = array_merge([4],$x,$y);
    
        return $z;
    }

    public function actionLoginChallenge()
    {
        $data = Yii::$app->request->post();
        $email = $data['email'];
        $random_str = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
        $array = unpack('C*', $random_str);
        $user = $this->getUser();
        $credentialId = $this->convertByteArray($user['credential_id']);

        $challenge = json_encode($array);
        Yii::$app->session->set('wa-challenge', $challenge);
        return [
            'challenge' => $challenge,
            'allowCredentials' => [
                [
                    'id' => json_encode($credentialId),
                    'type' => "public-key",
                ],
            ],
        ];
    }

    public function actionAuthentication()
    {
        $data = Yii::$app->request->post();
        $clientDataJSON = $this->bufferArrayToJsonArray(json_decode($data['response']['clientDataJSON'], true));

        if (!$this->isValidAuthenticationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!!");
        }

        $email = $data['email'];

        $user = $this->getUser($email);

    
        return true;
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
    * byte array(10進数の配列)を16進数文字列にする
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
    * byte array(10進数の配列)を16進数文字列にする
    */
    private function convertByteArray($hex)
    {
        Yii::error($hex);
        $array = str_split($hex, 2);
        Yii::error($array);
        $value = [];
        foreach($array as $num) {
            $value []= hexdec($num);
        }
        Yii::error($value);
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
    private function isValidRegistrationClientDataJSON($json)
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
    * ClientDataJSONが正しいかどうかチェック
    * 
    */
    private function isValidAuthenticationClientDataJSON($json)
    {
        $challenge = Yii::$app->session->get('wa-challenge');
        if($json['type'] !== "webauthn.create"
         || $json['origin'] !== "https://webauthn.kdtm.com"
         || base64_decode($json['challenge']) !== $challenge ) {
            return false;
        }
        return true;
    } 

    private function createUser($user_name, $credential_id, $publickey)
    {
        $sql = <<<SQL
            INSERT INTO user
                (user_name, credential_id, publickey)
            VALUES (:user_name, :credential_id, :publickey)
SQL;
        Yii::$app->db->createCommand($sql, [
            ":user_name" => $user_name,
            ":credential_id" => $credential_id,
            ":publickey" => $publickey,
        ])->execute();
    }

    private function getUser()
    {
        $sql = <<<SQL
            SELECT  * FROM user
                WHERE user_name = 'test'
SQL;
        $user = Yii::$app->db->createCommand($sql)->queryOne();
        Yii::error($user['credential_id']);
        return $user;
    }
}