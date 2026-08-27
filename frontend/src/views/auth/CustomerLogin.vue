<template>
  <div v-if="store">
    <h1>客戶登入</h1>

    <form @submit.prevent="login">
        <div>
            <label>Email</label>
            <input v-model="email" type="email" required>
        </div>

        <div>
            <label>密碼</label>
            <input v-model="password" type="password" required>
        </div>

        <div>
            <button type="submit">登入</button>
        </div>
        
        <p v-if="error">{{ error }}</p>
    </form>

    <router-link to="`/store-${storeId}/register`">還沒有帳號？點此註冊</router-link>
  </div>

  <div v-else-if="storeError">
    <p> {{ storeError }}</p>
  </div>
  
  <div v-else>
    <p>載入商店資料中...</p>
  </div>
</template>

<script setup>

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import {
    customerLogin,
    getStore
} from '../../api/auth.js'

const email = ref('')
const password = ref('')
const error = ref('')
const store = ref(null)
const storeError = ref('')

const route = useRoute()
const router = useRouter()
const storeId = route.params.storeId

onMounted(async () => {
    try {
        const data = await getStore(storeId)
        store.value = data.store
    } catch (e) {
        storeError.value = e.message
    }
})

async function login() {

    error.value = ''

    try {
        const data = await customerLogin(
        storeId,
        email.value,
        password.value
        )

        console.log(data)

        // 登入成功
        router.push(`/store-${storeId}`)
    } catch (e) {
        error.value = e.message
    }
}

</script>

<style>

</style>