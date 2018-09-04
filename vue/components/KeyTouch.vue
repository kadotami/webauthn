<template lang="pug">
  .key-touch
    .popup(v-show="display")
      h1 デバイスで認証してください
      .key-image
        span.ring
        .key
          .key-ring
            .hale
          .key-block
            .bar
            .bar.bar-bottom
      h1 待機中…
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
  $ring_color: #04DDFD;
  $key_color: #ef0;
  .popup {
    position: absolute;
    width: 30%;
    height: 300px;
    margin: 24px 35%;
    box-shadow: 0px 0px 6px rgba(0,0,0,0.2);
    background: $bg_color;
    box-sizing: border-box;
    z-index: 100;

    h1 {
      margin: 16px 0;
      text-align: center;
      font-size: 16px;
    }

    .key-image {
      position: relative;
      display: block;
      width: 80px;
      height: 80px;
      margin: 60px auto;
      border-radius: 50%;
      background: $ring_color;

      .ring {
        background: rgba(181, 244, 253, 0.8);
        position: absolute;
        z-index:-1;
        border-radius: 50%;
        height: 100px;
        width: 100px;
        top: -10px;
        left: -10px;
        animation: pulsate 1s ease-out;  /* スピードなど */
        animation-iteration-count: infinite;
      }

      .key-ring {
        position: relative;
        height: 30px;
        width: 30px;
        left: 25px;
        top: 10px; 
        background: $key_color;
        border-radius: 50%;
        .hale { 
          position: relative;
          height: 20px;
          width: 20px;
          left: 5px;
          top: 5px;
          background: #ef0;
          border-radius: 50%;
          background: $ring_color;
        }
      }

      .key-block {
        position: relative;
        height: 32px;
        width: 0;
        left: 37px;
        top: 5px;
        border: 3px solid $key_color;
        .bar {
          position: relative;
          border: 3px solid $key_color;
          height: 0;
          width: 10px;
          top: 7px;
        }
        .bar-bottom {
          width: 13px;
          top: 13px;
        }
      }
    }
    @keyframes pulsate {
      0%   { transform: scale(1, 1); }
      50%  { opacity: 1; }
      100% { transform: scale(2, 2); opacity: 0.0; }
    }
  }
</style>