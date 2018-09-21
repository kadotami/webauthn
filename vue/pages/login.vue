<template lang="pug">
  .login
    KeyTouch(:display='display')
    .container
      section.content
        .mailForm
          h1 ログイン
          .form
            label メールアドレス（ID）
            input(v-model="email", type=text)
            button(@click="wa_login()") WebAuthnでログイン
        .thirdPartyArea
          .thirdPartyBtn Yahoo! JAPAN IDでログイン
          .thirdPartyBtn Facebookアカウントでログイン
          .thirdPartyBtn Googleアカウントでログイン
          .thirdPartyBtn LINEアカウントでログイン
</template>

<script>
import { mapGetters } from 'vuex'
import { mapMutations } from 'vuex'
import { mapActions } from 'vuex'
import { Base64 } from 'js-base64';
import { getLoginChallenge, login } from '../utils/auth'
import KeyTouch from '~/components/KeyTouch.vue'

export default {
  components: {
    KeyTouch
  },
  computed: mapGetters([
    'getIsLogin'
  ]),
  data: () => {
    return { 
      email: '',
      display: false
    }
  },
  methods: {
    ...mapActions({
      'login': 'auth/login'
    }),
    wa_login: async function() {
      const user = {
        'email': this.email
      }
      let challenge = await getLoginChallenge(user)
      let option = challenge.data
      option.challenge = new TextEncoder().encode(Base64.decode(option.challenge))
      option.allowCredentials[0].id = new Uint8Array(option.allowCredentials[0].id)
      console.log(option)

      //demo用
      //option.rpId = 'webauthn-second.kdtm.com'
      
      this.display = true
      let credential = await navigator.credentials.get({ "publicKey": option}).catch(err => {
        this.display = false
        throw(err)
      })
      
      console.log(credential)
      this.display = false
        
      const request_body = {
        email: this.email,
        id: credential.id,
        raw_id: new Uint8Array(credential.rawId),
        type: credential.type,
        response: {
          authenticatorData: new Uint8Array(credential.response.authenticatorData),
          clientDataJSON: new Uint8Array(credential.response.clientDataJSON),
          signature: new Uint8Array(credential.response.signature),
        }
      }

      await this.post(request_body)
      this.$router.push('main')
    },
    post: async function(data) {
      console.log(data)
      const result = await login(
        data
      )
      console.log(result)
    }
  }
}
</script>

<style lang="scss" scoped>
  $container_width: 384px;
  $bg_color: #fff;
  .container {
    font-family: Verdana, Roboto, "Droid Sans", "メイリオ", Meiryo, "ヒラギノ角ゴ ProN W3", "Hiragino Kaku Gothic ProN", "ＭＳ Ｐゴシック", sans-serif;
    position: relative;
    width: $container_width;
    margin: 30px auto 40px;
    @media screen and (max-width:700px) {
      width: 96%;
      margin: 30px 2% 40px;
    } 
    .content {
      background: $bg_color;
      box-shadow: 0px 0px 6px rgba(0,0,0,0.2);
      .mailForm {
        padding: 42px;
        border-bottom: 1px solid #eaeaea;
        position: relative;

        label {
          display: block;
          margin: 16px 0 4px 0;
          color: #464646;
          font-size: 14px;
          line-height: 1.4em;
        }

        input {
          height: 40px;
          border: 2px solid #eaeaea;
          padding: 0 12px;
          box-sizing: border-box;
          border-radius: 0;
          font-size: 14px;
          line-height: 14px;
          width: 300px;
          @media screen and (max-width:700px) {
            width: 100%;
          } 
        }

        button {
          border-radius: 3px;
          background: #3282c9;
          color: #fff;
          font-size: 16px;
          font-weight: bold;
          text-decoration: none;
          box-shadow: 0px 2px 5px #a5a5a5;
          border: none;
          cursor: pointer;
          text-align: center;
          display: block;
          width: 300px;
          height: 60px;
          margin: 16px 0 0 0;
          @media screen and (max-width:700px) {
            width: 100%;
          } 
        }
        .toLogin {
          margin-top: 16px;
          font-size: 14px;
          line-height: 1.6em;
        }

        &:after {
          position: absolute;
          bottom: -17px;
          left: 50%;
          width: 34px;
          height: 34px;
          line-height: 34px;
          margin: -17px 0 0 -17px;
          text-align: center;
          border: solid 1px #eaeaea;
          border-radius: 50%;
          background: $bg_color;
          color: #999;
          content: "or";
        }
      }

      .thirdPartyArea {
        padding: 42px;

        .thirdPartyBtn {
          width: 300px;
          height: 48px;
          padding: 0 20px 0 10px;
          margin-bottom: 12px;
          box-sizing: border-box;
          border-radius: 3px;
          background: #fff;
          color: #505050;
          font-size: 14px;
          text-decoration: none;
          box-shadow: 0px 1px 4px #dadada;
          border: none;
          cursor: pointer;
          position: relative;
          display: flex;
          align-items: center;
          &:hover {
            box-shadow: 0px 0px 2px #cccccc;
            transform: translateY(1px);
          }
          @media screen and (max-width:700px) {
            width: 100%;
          } 
        }
      }
    }
  }
</style>