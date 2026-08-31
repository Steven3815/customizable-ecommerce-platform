<template>
  <div v-if="store">
    <h1>客戶註冊</h1>

    <form v-if="!success" @submit.prevent="register">

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
        
        <p v-if="error">{{ error }}</p>
    </form>
    
    <div v-if="success">
        <p class="success-message">恭喜！{{ success }}</p>
        <router-link to="/">前往首頁</router-link>
        <router-link :to="`/store-${storeId}/login`">登入</router-link>
    </div>
  </div>
  
  <div v-else>
    <p>載入商店資料中...</p>
  </div>
</template>

<script setup>

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { customerRegister } from '../../api/auth.js'
import { getStore } from '../../api/store.js'

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const confirmSuccess = ref(null)
const success = ref('')
const error = ref('')
const store = ref(null)
const route = useRoute()
const router = useRouter()
const storeId = route.params.storeId


onMounted(async () => {
    try {
        const data = await getStore(storeId)
        store.value = data.store
    } catch (e) {
        console.error('取得商店資料失敗:', e)
        router.push('/404')
    }
})

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