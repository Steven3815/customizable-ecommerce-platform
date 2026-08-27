<template>
  <div>
    <h1>商家註冊</h1>

    <form v-if="!success" @submit.prevent="register">

        <div>
            <label>商家名稱</label>
            <input v-model="storeName" type="text" required>
        </div>
        
        <div>
            <label>負責人姓名</label>
            <input v-model="ownerName" type="text" required>
        </div>

        <div>
            <label>連絡電話</label>
            <input v-model="phone" type="text" required>
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
            <label>選擇商店模式</label>
            <select v-model="mode" required>
                <option value="" disabled>請選擇商店模式</option>
                <option value="shopping">購物模式</option>
                <option value="showcase">展示模式</option>
            </select>
            <p>商店模式說明：</p>
            <p>購物模式：商店可以正常販售商品，顧客可以瀏覽商品、加入購物車並進行結帳。</p>
            <p>展示模式：商店僅供展示，顧客可以瀏覽商店與商品，但無法登入、註冊、加入購物車或進行購買。</p>
            <p>注意：一旦選擇「購物模式」，之後將無法切換為「展示模式」。</p>
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
        <router-link :to="`/store-${newStoreId}/login`">登入</router-link>
    </div>
  </div>

</template>

<script setup>

import { ref } from 'vue'

import {
    storeRegister,
} from '../../api/auth.js'

const storeName = ref('')
const ownerName = ref('')
const phone = ref('')
const email = ref('')
const password = ref('')
const mode = ref('')
const confirmPassword = ref('')
const confirmSuccess = ref(null)
const success = ref('')
const error = ref('')
const newStoreId = ref(null)

async function register() {

    error.value = ''
    
    if(password.value !== confirmPassword.value) {
        confirmSuccess.value = false
        return
    }

    confirmSuccess.value = true

    try {
        const data = await storeRegister(
        storeName.value,
        ownerName.value,
        email.value,
        password.value,
        phone.value,
        mode.value
        )

        console.log(data)

        success.value = data.message
        newStoreId.value = data.store.store_id
        
    } catch (e) {
        error.value = e.message
    }
}

</script>

<style>

</style>