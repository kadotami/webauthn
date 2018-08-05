import axios from 'axios'

const BASE_URL = `https://api-webauthn.kdtm.com/api/auth`
// const BASE_URL = `http://localhost:8080/api/auth`

export function login (obj) {
  const url = `${BASE_URL}`
  return axios.get(url).then(response => response)
}

export function logout (obj) {
  const url = `${BASE_URL}`
  return axios.post(url, obj).then(response => response)
}

export async function getChallenge () {
  const url = `${BASE_URL}/challenge`
  const obj = await axios.get(url)
  return obj 
}
