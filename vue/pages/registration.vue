<template lang="pug">
  .registration
    KeyTouch(:display='display')
    .container
      section.content
        .mailForm
          h1 新規登録
          .form
            label メールアドレスを入力してください
            input(v-model="email",type="text")
            button(@click="register()") WebAuthnで登録
          p.toLogin
            nuxt-link(to="/login") IDをお持ちの方はこちら
        .thirdPartyArea
          .thirdPartyBtn Yahoo! JAPAN IDで登録
          .thirdPartyBtn Facebookアカウントで登録
          .thirdPartyBtn Googleアカウントで登録
          .thirdPartyBtn(@click="post()") LINEアカウントで登録
</template>

<script>
import { mapGetters } from 'vuex'
import { mapMutations } from 'vuex'
import { mapActions } from 'vuex'
import { Base64 } from 'js-base64';
import { getRegisterChallenge, postRegisterCredential } from '../utils/auth'
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
    register: async function() {
      const user = {
        'email': this.email
      }
      const challenge = await getRegisterChallenge(user)
      const option = challenge.data
      option.challenge = new TextEncoder().encode(Base64.decode(option.challenge))
      option.user.id = new TextEncoder().encode(Base64.decode(option.user.id))
      console.log(option)
      this.display = true
      const credential = await navigator.credentials.create({publicKey: option})
      this.display = false
      console.log(credential)


      const request_body = {
        email: this.email,
        id:    credential.id,
        raw_id: new Uint8Array(credential.rawId),
        type:  credential.type,
        response: {
          attestationObject: new Uint8Array(credential.response.attestationObject),
          clientDataJSON:    new Uint8Array(credential.response.clientDataJSON),
        }
      }

      const request_body2 = {
        email: this.email,
        id:    credential.id,
        raw_id: new Uint8Array([0, 211, 55, 161, 18, 238, 21, 23, 117, 217, 202, 108, 47, 112, 14, 250, 193, 193, 212, 184, 219, 106, 232, 148, 104, 17, 202, 10, 12, 161, 150, 32, 212, 114, 171, 132, 161, 70, 120, 26, 143, 200, 40, 74]),
        type:  credential.type,
        response: {
          attestationObject: new Uint8Array([163,99,102,109,116,102,112,97,99,107,101,100,103,97,116,116,83,116,109,116,162,99,97,108,103,38,99,115,105,103,88,70,48,68,2,32,79,31,194,84,78,104,59,162,91,47,80,162,60,94,96,33,38,230,126,141,168,203,21,237,35,218,72,206,246,100,248,245,2,32,117,9,210,118,140,187,226,30,128,67,111,85,209,64,146,181,176,39,173,22,144,173,138,16,76,42,247,230,196,158,24,73,104,97,117,116,104,68,97,116,97,88,176,167,208,7,140,4,246,99,23,104,12,6,12,242,227,247,25,27,49,31,99,84,87,222,71,85,183,183,241,0,8,78,220,69,91,160,106,198,173,206,0,2,53,188,198,10,100,139,11,37,241,240,85,3,0,44,0,211,55,161,18,238,21,23,117,217,202,108,47,112,14,250,193,193,212,184,219,106,232,148,104,17,202,10,12,161,150,32,212,114,171,132,161,70,120,26,143,200,40,74,165,1,2,3,38,32,1,33,88,32,126,174,225,52,1,83,113,110,57,13,147,184,167,31,115,32,50,239,130,92,238,195,117,97,138,205,211,83,9,62,128,141,34,88,32,24,199,64,8,65,14,69,7,220,89,213,168,181,61,175,203,167,172,205,211,201,192,33,76,172,139,50,110,195,168,41,64]),
          clientDataJSON:    new Uint8Array(credential.response.clientDataJSON),
        }
      }

      await this.post(request_body)
      this.$router.push('main')
    },
    post: async function(data) {
      
      const result = await postRegisterCredential(
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
        }
      }
    }
  }
</style>