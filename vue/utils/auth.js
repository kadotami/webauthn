import axios from 'axios'

const BASE_URL = `https://api.webauthn.kdtm.com/api/auth`

export function login (obj) {
  const url = `${BASE_URL}`
  return axios.post(url, obj, {withCredentials: true}).then(response => response)
}

export function logout (obj) {
  const url = `${BASE_URL}`
  return axios.post(url, obj).then(response => response)
}

export async function getRegisterChallenge (obj) {
  const url = `${BASE_URL}/register-challenge`
  const challenge = await axios.post(url, obj,{withCredentials: true})
  return challenge 
}

export async function getLoginChallenge (obj) {
  const url = `${BASE_URL}/login-challenge`
  const challenge = await axios.post(url, obj, {withCredentials: true})
  return challenge 
}

export async function postRegisterCredential (obj) {
  const url = `${BASE_URL}/register-credential`
  return axios.post(url, obj, {withCredentials: true}).then(response => response)
}
