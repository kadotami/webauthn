<template lang="pug">
  .top
    .description
      p 最強のセキュリティを有するイケてるサイト
    .links
      router-link.btn(to="/registration") 登録
      router-link.btn.btn--last(to="/login") ログイン
</template>

<script>
import AppLogo from '~/components/AppLogo.vue'

export default {
  components: {
    AppLogo
  }
}
</script>

<style lang="scss" scoped>
  .description {
    font-size: 60px;
    min-height: 60vh;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
  }

  .links {
    width: 600px;
    margin: 0 auto;
    .btn {
      width: 200px;
      height: 48px;
      line-height: 48px;
      float: left;
      font-size: 32px;
      text-align: center;
      text-decoration: none;
      background: #009688;
      color: #fff;
      border-radius: 5px;
      box-shadow: 0px 2px 5px #a5a5a5;

      &--last {
        float: right;
        background: #3282c9;
      }
    }
  }

  .title {
    font-family: "Quicksand", "Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; /* 1 */
    display: block;
    font-weight: 300;
    font-size: 100px;
    color: #35495e;
    letter-spacing: 1px;
  }

  .subtitle {
    font-weight: 300;
    font-size: 42px;
    color: #526488;
    word-spacing: 5px;
    padding-bottom: 15px;
  }

  .links {
    padding-top: 15px;
  }
</style>
