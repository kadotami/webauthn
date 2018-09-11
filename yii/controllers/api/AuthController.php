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
    // const RPID = 'kdtm.com';

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
        $challenge = $this->createRandomString(32);
        Yii::$app->session->set('wa-challenge', $challenge);
        Yii::$app->session->set('wa-username', $data['email']);
        
        return [
            'challenge' => base64_encode($challenge),
            'rp' => [
                'id' => self::RPID,
                'name' => 'WebAuthnTest',
            ],
            'user' => [
                'id' => base64_encode($data['email']),
                'name' => "test",
                'displayName' => "test"
            ],
            'pubKeyCredParams'=> [
                [
                    'type' => "public-key",
                    'alg'  => -7, // "ES256"
                ]
            ],
            'attestation' => "direct",
        ];
    }

    public function actionRegisterCredential()
    {
        $data = Yii::$app->request->post();
        if (empty($data['email'])) {
            throw new Exception("invalid!!! email is not allowed empty");
        }
        if($data['email'] !==  Yii::$app->session->get('wa-username')) {
            throw new Exception("invalid!!! email is not correct");
        }

        $rawid = $data['raw_id'];
        $clientDataJSON = $this->byteArrayToJsonArray($data['response']['clientDataJSON']);
        $attestationObject = $this->byteArrayToCBORObject($data['response']['attestationObject']);

        $clientDataHash = $this->byteArrayToString($data['response']['clientDataJSON']);
        $clientDataHash = hash('sha256', $clientDataHash);

        if (!$this->isValidRegistrationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!! client data is not correct");
        }

        $authData = $attestationObject['authData'];
        $authData_byte = $authData->get_byte_string();
        $authData_byte_array = array_values(unpack('C*',$authData_byte));

        $rpid_hash = array_slice($authData_byte_array, 0, 32);
        $flag = str_pad(decbin($authData_byte_array[32]), 8, 0, STR_PAD_LEFT);
        $counter = $this->byteArrayToEndian(array_slice($authData_byte_array, 33, 4));

        if(!$this->isValidRPID($rpid_hash)) {
            throw new Exception("invalid!!! not match rpid");
        }

        $user_present = substr($flag,7,1);
        $user_verified = substr($flag,5,1);
        $attested_credential_data = substr($flag,1,1);
        $extension_data_included = substr($flag,0,1);

        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);

        
        // attestation check

        if($attested_credential_data) {
            $aaguid              = array_slice($authData_byte_array, 37, 16);
            $credentialIdLength  = array_slice($authData_byte_array, 53, 2);
            $credentialIdLength = $this->byteArrayToEndian($credentialIdLength);
            $credentialId = array_slice($authData_byte_array, 55, $credentialIdLength);
            $credentialId = $this->byteArrayToHex($credentialId);
            $credentialPublicKey = array_slice($authData_byte_array, 55 + $credentialIdLength);

            if($credentialId !== $this->byteArrayToHex($rawid)){
                throw new Exception("invalid!!! not match credential id");
            }

            $pubkey_hex = $this->byteArrayToHex($this->publicKey($credentialPublicKey));

            Yii::error($attestationObject['fmt']);
            if($attestationObject['fmt'] === 'fido-u2f') {
                if(!$this->u2fAttestationCheck($attestationObject['attStmt'], $rpid_hash, $clientDataHash, $credentialId, $pubkey_hex)) {
                    return [
                        "result" => 'ng'
                    ];
                }
            }

            $pubkey_pem = $this->createPubkeyPem($pubkey_hex);

            $this->createUser($data['email'], $rpid, $credentialId, $pubkey_pem);
        }

        return [
            "result" => 'ok'
        ];
    }

    private function u2fAttestationCheck($attStmt, $rpid_hash, $clientDataHash, $credentialId, $pubkey_string)
    {
        $certification = $attStmt['x5c'][0]->get_byte_string();
        $signature = $attStmt['sig']->get_byte_string();
        
        // PEMに整形
        $certification = base64_encode($certification);
        $certification = chunk_split($certification, 64, "\n");
        $certification_pem = "-----BEGIN CERTIFICATE-----\n$certification-----END CERTIFICATE-----";
        
        $verificationData = '00' . $this->byteArrayToHex($rpid_hash) . $clientDataHash .  $credentialId . $pubkey_string;
        Yii::error($this->hexToByteArray($verificationData));
        
        $verificationData = $this->byteArrayToString($this->hexToByteArray($verificationData));
        Yii::error($certification_pem);

        Yii::error(unpack('C*',$signature));

        $ok = openssl_verify($verificationData, $signature, $certification_pem, 'sha256');
        Yii::error($ok);

        if($ok === 1 ) {
            return true;
        }
        return false;
    }

    private function publicKey($credentialPublicKey)
    {
        $publickey_json = $this->byteArrayToCBORObject($credentialPublicKey);
        // key is [1,3,-1,-2,-3]
        $x = unpack('C*',$publickey_json['-2']->get_byte_string());
        $y = unpack('C*',$publickey_json['-3']->get_byte_string());
        $z = array_merge([4],$x,$y);

        return $z;
    }

    private function createPubkeyPem($pubkey_hex)
    {
        // メタデータの付与
        $pubkey_hex = "3059301306072a8648ce3d020106082a8648ce3d030107034200" . $pubkey_hex;
        // 10進数のbyte arrayへ変換
        $pubkey = $this->hexToByteArray($pubkey_hex);
        // byte arrayからbase64へ
        $pubkey = $this->byteArrayToString($pubkey);
        $pubkey = base64_encode($pubkey);
        
        // PEMに整形
        $pubkey = chunk_split($pubkey,64, "\n");
        $pubkey_pem = "-----BEGIN PUBLIC KEY-----\n$pubkey-----END PUBLIC KEY-----\n";
        return $pubkey_pem;
    }

    public function actionLoginChallenge()
    {
        //test
        $origin = Yii::$app->request->origin;
        $rpid = $this->testRpid($origin);

        $data = Yii::$app->request->post();
        $email = $data['email'];
        $challenge = $this->createRandomString(32);
        Yii::$app->session->set('wa-challenge', $challenge);
        $user = $this->getUser($email, $rpid);
        $credentialId = $this->hexToByteArray($user['credential_id']);

        return [
            'challenge' => base64_encode($challenge),
            'rpId' => $rpid,
            'allowCredentials' => [
                [
                    'id' => $credentialId,
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
        $clientDataJSON = $this->byteArrayToJsonArray($data['response']['clientDataJSON']);
        $authenticatorData = $data['response']['authenticatorData'];
        $signature = $data['response']['signature'];
        
        // clientDataのチェック
        if (!$this->isValidAuthenticationClientDataJSON($clientDataJSON)) {
            throw new Exception("invalid!!! client data is not correct");
        }
        
        // clientdataJsonのハッシュ値を取得する
        $clientDataStr = $data['response']['clientDataJSON'];
        $clientDataStr = $this->byteArrayToString($clientDataStr);
        $clientDataHash = hash('sha256', $clientDataStr);

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
        
        $confirm_sig = array_merge($authenticatorData, $this->hexToByteArray($clientDataHash));
        Yii::error($confirm_sig);

        
        $user = $this->getUser($email, $rpid);
        $pubkey = $user['publickey'];
        Yii::error($pubkey);

        $signature = $this->byteArrayToString($signature);
        Yii::error($signature);
        
        $confirm_sig =  $this->byteArrayToString($confirm_sig);
        Yii::error($confirm_sig);

        $ok = openssl_verify($confirm_sig, $signature, $pubkey, 'sha256');
        if($ok === 1 ) {
            return true;
        }

        return false;
    }

    private function byteArrayToString($byte_array)
    {
        return implode(array_map("chr", $byte_array));
    }

    private function byteArrayToJsonArray($byte_array)
    {
        $json = $this->byteArrayToString($byte_array);
        $array = json_decode($json, true);
        return $array;
    }

    private function byteArrayToCBORObject($byte_array)
    {
        $CBORstring = $this->byteArrayToString($byte_array);
        $data = CBOREncoder::decode($CBORstring);
        return $data;
    }

    /*
    * byte arrayをbig endianになおして数値化する
    */
    private function byteArrayToEndian($byte_array)
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
    private function byteArrayToHex($byte_array)
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
    private function hexToByteArray($hex)
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
        $rpid = $this->byteArrayToHex($rpid_hash);
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

    /*
    * ランダムな文字列を生成する
    * 
    */
    private function createRandomString($length)
    {
        $challenge = str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', $length);
        $challenge = str_shuffle($challenge);
        $challenge = substr($challenge, 0, $length);
        return $challenge;
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