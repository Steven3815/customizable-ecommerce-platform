<template>
  <div>
    <h1>商家登入</h1>

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

    <router-link to="/store/register">還沒有帳號？點此註冊</router-link>
  </div>

</template>

<script setup>

import { ref } from 'vue'
import { useRouter } from 'vue-router'

import {
    storeLogin,
} from '../../api/auth.js'

const email = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()

async function login() {

    error.value = ''

    try {
        const data = await storeLogin(
        email.value,
        password.value
        )

        console.log(data)

        const storeId = data.store.store_id

        // 登入成功
        router.push(`/store-${storeId}/admin`)
    } catch (e) {
        error.value = e.message
    }
}

</script>

<style>

</style>