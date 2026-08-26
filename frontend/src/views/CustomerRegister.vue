<template>
  <div v-if="store">
    <h1>客戶註冊</h1>

    <form @submit.prevent="register">
        <div>
            <label>姓名</label>
            <input v-model="name" type="text" required>
        </div>
        <div>
            <label>Email</label>
            <input v-model="email" type="email" required>
        </div>
        <div>
            <label>密碼</label>
            <input v-model="password" type="password" required>
        </div>
        <div>
            <label>確認密碼</label>
            <input v-model="confirmPassword" type="password" required>
        </div>
        <div>
            <button type="submit">註冊</button>
        </div>
        <p v-if="confirmSuccess === false">密碼輸入不一致</p>
        
        <div v-if="success">
            <p class="success-message">
                恭喜！{{ success }}
            </p>

            <button type="button" @click="goHome">
                前往首頁
            </button>

            <button type="button" @click="goLogin">
                登入
            </button>
        </div>
        
        <p v-if="error">
            {{ error }}
        </p>
    </form>
  </div>
  <div v-else-if="storeError">
    <p>{{ storeError }}</p>

    <button type="button" @click="goHome">
        前往首頁
    </button>
  </div>
  
  <div v-else>
    <p>載入商店資料中...</p>
  </div>
</template>

<script setup>

import { ref, onMounted } from 'vue'
import { useRouter, useRoute} from 'vue-router'

import {
    customerRegister,
    getStore
} from '../api/auth.js'

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const confirmSuccess = ref(null)
const success = ref('')
const error = ref('')
const router = useRouter()
const route = useRoute()
const storeId = route.params.storeId
const store = ref(null)
const storeError = ref('')

onMounted(async () => {
    try {
        const data = await getStore(storeId)
        store.value = data.store
    } catch (e) {
        storeError.value = e.message
    }
})

function goHome() {
    router.push('/')
}

function goLogin() {
    router.push(`/store-${storeId}/login`)
}

async function register() {

    error.value = ''
    
    if(password.value !== confirmPassword.value) {
        confirmSuccess.value = false
        return
    }

    confirmSuccess.value = true

    try {
        const data = await customerRegister(
        storeId,
        name.value,
        email.value,
        password.value
        )

        console.log(data)

        success.value = data.message
    } catch (e) {
        error.value = e.message
    }
}

</script>

<style>

</style>