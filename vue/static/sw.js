importScripts('/_nuxt/workbox.dev.c21f51f2.js')

const workboxSW = new self.WorkboxSW({
  "cacheId": "nuxt",
  "clientsClaim": true,
  "directoryIndex": "/"
})

workboxSW.precache([
  {
    "url": "/_nuxt/app.3d221a16431a23c01228.js",
    "revision": "502ed145fba890decd39810c3f85fd1b"
  },
  {
    "url": "/_nuxt/layouts/default.f29f95e6f8b648ada971.js",
    "revision": "0250eb27a970e2807443ee818e004d51"
  },
  {
    "url": "/_nuxt/manifest.efca79134d0d4890d77d.js",
    "revision": "aa583201c8b066c56e0f1690dc2204a7"
  },
  {
    "url": "/_nuxt/pages/index.13c66a035ebc05b6fdf3.js",
    "revision": "d45715569886409f0b4c7b4a71771a48"
  },
  {
    "url": "/_nuxt/pages/login.33c63304ae06df24a44e.js",
    "revision": "d0489cf98697018679d43af6f747080e"
  },
  {
    "url": "/_nuxt/pages/registration.0660eb8a31789cf2e932.js",
    "revision": "37c3da212259e82159bceb492f89ff2b"
  },
  {
    "url": "/_nuxt/vendor.5fcdbc011f1ac8475f8e.js",
    "revision": "febff86386b6acd9dbf659d1d06db2f8"
  }
])


workboxSW.router.registerRoute(new RegExp('/_nuxt/.*'), workboxSW.strategies.cacheFirst({}), 'GET')

workboxSW.router.registerRoute(new RegExp('/.*'), workboxSW.strategies.networkFirst({}), 'GET')

