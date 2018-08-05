import axios from 'axios'

export const state = () => ({
      id_login: false
})

export const mutations = {
  login(state) {
    state.is_login = true
  },
  logout(state) {
    state.is_login = false
  }
}

async function yubikey_login() {
  console.log('aaa')
  const credential = await navigator.credentials.create({publicKey: {}})
  console.log('bbb')
  const {id, rawID, response, type} = credential
  const {attestationObject, clientDataJSON} = response
}

export const actions = {
  login({ commit }) {
    yubikey_login().then(
      commit('login')
    )
  },
  logout({ commit }) {
    commit('logout')
  }
}

export const getters = {
  getIsLogin (state) {
    return state.is_login
  }
}