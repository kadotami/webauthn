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
    // const RPID = 'kbtm.com';

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
        if (empty($data['email'])){
            throw new Exception("invalid!!! email is not allowed empty");
        }
        $challenge = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
        $array = unpack('C*', $challenge);
        Yii::$app->session->set('wa-challenge', $challenge);
        Yii::$app->session->set('wa-username', $data['email']);

        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);

        $challenge = json_encode($array);
        return [
            'challenge' => $challenge,
            'rp' => [
                'id' => $rpid,
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
        if (empty($data['email'])) {
            throw new Exception("invalid!!! email is not allowed empty");
        }

        $rawid = json_decode($data['raw_id'], true);
        $clientDataJSON = $this->bufferArrayToJsonArray(json_decode($data['response']['clientDataJSON'], true));
        $attestationObject = $this->bufferArrayToCBORObject(json_decode($data['response']['attestationObject'], true));
        
        if($data['email'] !==  Yii::$app->session->get('wa-username')) {
            throw new Exception("invalid!!! email is not correct");
        }

        if (!$this->isValidRegistrationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!! client data is not correct");
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

        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);

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

            $this->createUser($data['email'], $rpid, $this->convertHex($credentialId), $pubkey_string);
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
        $challenge = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 32)), 0, 32);
        Yii::$app->session->set('wa-challenge', $challenge);
        $array = unpack('C*', $challenge);
        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);
        $user = $this->getUser($email, $rpid);
        $credentialId = $this->convertByteArray($user['credential_id']);

        $challenge = json_encode($array);
        return [
            'challenge' => $challenge,
            'rpId' => $rpid,
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
        $email = $data['email'];
        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);
        $clientDataJSON = $this->bufferArrayToJsonArray(json_decode($data['response']['clientDataJSON'], true));
        $authenticatorData = json_decode($data['response']['authenticatorData'], true);
        $signature = json_decode($data['response']['signature'], true);
        
        // clientDataのチェック
        if (!$this->isValidAuthenticationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!! client data is not correct");
        }
        
        // clientdataJsonのハッシュ値を取得する
        $clientDataStr = json_decode($data['response']['clientDataJSON'], true);
        $clientDataStr = implode(array_map("chr", $clientDataStr));
        $clientDataHash = hash('sha256', $clientDataStr); // ok

        Yii::error($this->convertByteArray($clientDataHash));

        // rpidとフラグのチェック
        $rpid_hash = array_slice($authenticatorData, 0, 32);
        $flag = str_pad(decbin($authenticatorData[32]), 8, 0, STR_PAD_LEFT);
        $sig_count = array_slice($authenticatorData, 33);
        if(!$this->isValidRPID($rpid_hash)) {
            throw new Exception("invalid!!! not match rpid");
        }
        $user_present = substr($flag,7,1);
        $user_verified = substr($flag,5,1);
        $attested_credential_data = substr($flag,1,1);
        $extension_data_included = substr($flag,0,1);

        Yii::error($authenticatorData);
        
        $confirm_sig = array_merge($authenticatorData, $this->convertByteArray($clientDataHash));
        Yii::error($confirm_sig);

        
        $user = $this->getUser($email, $rpid);
        $pubkey = $user['publickey'];
        Yii::error($pubkey);

        // メタデータの付与
        $pubkey = "3059301306072a8648ce3d020106082a8648ce3d030107034200" . $pubkey;
        // 10進数のbyte arrayへ変換
        $pubkey = $this->convertByteArray($pubkey);
        Yii::error($pubkey);

        // byte arrayからbase64へ
        $pubkey = implode(array_map("chr", $pubkey));
        $pubkey = base64_encode($pubkey);
        
        // PEMに整形
        // $pubkey = 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE05Bflr4e+XoT+lEgubRweQ68IQXOaLEmawNze0s2WK6JKu6mNckSDiNsJin/MkEDhrkT8DmQIzOnLF+/KJ0A/g==';
        $pubkey = chunk_split($pubkey,64, "\n");
        $pubkey_pem = "-----BEGIN PUBLIC KEY-----\n$pubkey-----END PUBLIC KEY-----\n";
        Yii::error($pubkey_pem);

        Yii::error($signature);
        $signature = implode(array_map("chr", $signature));
        
        $confirm_sig = implode(array_map("chr", $confirm_sig));
        Yii::error($confirm_sig);

        $ok = openssl_verify($confirm_sig, $signature, $pubkey_pem, 'sha256');
        Yii::error($ok);

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
    * 16進数文字列をbyte array(10進数の配列)にする
    */
    private function convertByteArray($hex)
    {
        $array = str_split($hex, 2);
        $value = [];
        foreach($array as $num) {
            $value []= hexdec($num);
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
    private function isValidRegistrationClientDataJSON($json)
    {
        $origin = Yii::$app->request->origin;
        $challenge = Yii::$app->session->get('wa-challenge');
        if($json['type'] !== "webauthn.create"
         || $json['origin'] !== $origin //"https://webauthn.kdtm.com"
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
        $origin = Yii::$app->request->origin;
        $challenge = Yii::$app->session->get('wa-challenge');
        Yii::error($json['origin']);
        if($json['type'] !== "webauthn.get"
         || $json['origin'] !== $origin
         || base64_decode($json['challenge']) !== $challenge ) {
            return false;
        }
        return true;
    } 

    private function createUser($email, $rpid, $credential_id, $publickey)
    {
        $sql = <<<SQL
            INSERT INTO user
                (email, rpid, credential_id, publickey)
            VALUES (:email, :rpid, :credential_id, :publickey)
SQL;
        Yii::$app->db->createCommand($sql, [
            ":email" => $email,
            ":rpid" => $rpid,
            ":credential_id" => $credential_id,
            ":publickey" => $publickey,
        ])->execute();
    }

    private function getUser($email, $rpid)
    {
        $sql = <<<SQL
            SELECT  * FROM user
                WHERE email = :email
                AND rpid = :rpid
SQL;
        $user = Yii::$app->db->createCommand($sql,[
            ":rpid" => $rpid,
            ":email" => $email,
        ])->queryOne();
        return $user;
    }

    private function testRpid($origin)
    {
        if($origin === 'https://webauthn.kbtm.com') {
            return 'webauthn.kbtm.com';
        }
        return self::RPID;
    }
}