<template lang="pug">
  .container
    section.content
      .login
        .login__column
          h1 ログイン
          form(@submit.prevent="logina()")
            label メールアドレス（ID）
            input(type=text)
            label パスワード
            input(v-model=email, type=password)
            button(type=submit) ログイン
        .login__column
          .thirdPartyBtn(@click="logina()") WebAuthnでログイン
          .thirdPartyBtn Yahoo! JAPAN IDでログイン
          .thirdPartyBtn Facebookアカウントでログイン
          .thirdPartyBtn Googleアカウントでログイン
          .thirdPartyBtn LINEアカウントでログイン
</template>

<script>
import { mapGetters } from 'vuex'
import { mapMutations } from 'vuex'
import { mapActions } from 'vuex'
import { getLoginChallenge, login } from '../utils/auth'

export default {
  computed: mapGetters([
    'getIsLogin'
  ]),
  data: () => {
    return { 
      email: ''
    }
  },
  methods: {
    ...mapActions({
      'login': 'auth/login'
    }),
    logina: async function() {
      const user = {
        'email': this.email
      }
      const challenge = await getLoginChallenge(user)
      challenge.data.challenge = new Uint8Array(Object.values(JSON.parse(challenge.data.challenge)))
      challenge.data.allowCredentials[0].id = new Uint8Array(Object.values(JSON.parse(challenge.data.allowCredentials[0].id)))
      const credential = await navigator.credentials.get({ "publicKey": challenge.data})
      console.log(credential)

      const body = {
        email: this.email,
        id: credential.id,
        raw_id: JSON.stringify(new Uint8Array(credential.rawId)),
        type: credential.type,
        response: {
          authenticatorData: JSON.stringify(new Uint8Array(credential.response.authenticatorData)),
          clientDataJSON: JSON.stringify(new Uint8Array(credential.response.clientDataJSON)),
          signature: JSON.stringify(new Uint8Array(credential.response.signature)),
        }
      }

      await this.post(body)
    },
    post: async function(body) {
      console.log(body)
      const result = await login(
        body
      )
      console.log(result)
    }
  }
}
</script>

<style lang="scss" scoped>
  $container_width: 770px;
  $bg_color: #fff;
  .container {
    position: relative;
    width: $container_width;
    margin: 0 auto;
    padding: 30px 0 40px;
    font-family: Verdana, Roboto, "Droid Sans", "メイリオ", Meiryo, "ヒラギノ角ゴ ProN W3", "Hiragino Kaku Gothic ProN", "ＭＳ Ｐゴシック", sans-serif;

    .content {
      position: relative;
      width: $container_width;
      padding: 36px;
      box-shadow: 0px 0px 6px rgba(0,0,0,0.2);
      background: $bg_color;
      box-sizing: border-box;
      &:before {
        position: absolute;
        top: 0;
        left: 50%;
        width: 1px;
        height: 100%;
        content: '';
        background: #eaeaea;
      }
      &:after {
        position: absolute;
        top: 50%;
        left: 50%;
        margin: -17px 0 0 -17px;
        width: 34px;
        height: 34px;
        line-height: 34px;
        text-align: center;
        border: solid 1px #eaeaea;
        border-radius: 50%;
        background: $bg_color;
        color: #999;
        content: 'or';
      }

      .login {
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: left;
        .login__column {
          width: 50%;
          h1 {
            font-size: 24px;
            font-weight: bold;
            color: #505050;
            margin: 0;
          }
          label {
            width: 100%;
            margin: 20px 0 4px 0;
            color: #505050;
            font-size: 14px;
            display: block;
          }
          input {
            height: 40px;
            border: 2px solid #eaeaea;
            padding: 0 12px;
            box-sizing: border-box;
            -webkit-appearance: none;
            border-radius: 0;
            font-size: 14px;
            line-height: 14px;
            width: 300px;
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
          }

          .thirdPartyBtn {
            width: 300px;
            height: 48px;
            padding: 0 20px 0 10px;
            margin: 0 auto 12px;
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
          }          
        }
      }
    }
  }
</style>