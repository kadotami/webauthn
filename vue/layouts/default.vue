<template>
  <div>
    <header>
      <div class='header-content'>
        WebAuthn
      </div>
    </header>
    <nuxt/>
    <footer>
    </footer>
  </div>
</template>

<style lang="scss">
html {
  font-family: "Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  font-size: 16px;
  word-spacing: 1px;
  -ms-text-size-adjust: 100%;
  -webkit-text-size-adjust: 100%;
  -moz-osx-font-smoothing: grayscale;
  -webkit-font-smoothing: antialiased;
  box-sizing: border-box;
  background: #fcfcfa;
}

*, *:before, *:after {
  box-sizing: border-box;
  margin: 0;
}

header {
  border-bottom: 2px solid #eaeaea;
  padding: 8px 0 4px 0;
  @media screen and (max-width:700px) {
    width: 100%;
    height: 48px;
    padding: 0;
  } 
  .header-content {
    display: block;
    width: 770px;
    margin: 0 auto;
    text-align: left;
    @media screen and (max-width:700px) {
      width: 100%;
      line-height: 48px;
      padding: 0 24px;
    } 
  }
}
</style>
