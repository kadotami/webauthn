<template lang="pug">
  .key-touch
    .popup(v-show="display")
      p デバイスで認証してください。
      .key-image
</template>

<script>
export default {
  props: [
    'display'
  ]
}
</script>

<style lang="scss" scoped>
  $bg_color: #fff;
  .popup {
    position: absolute;
    width: 30%;
    height: 300px;
    margin: 0 35%;
    box-shadow: 0px 0px 6px rgba(0,0,0,0.2);
    background: $bg_color;
    box-sizing: border-box;
    z-index: 100;
  }
</style>