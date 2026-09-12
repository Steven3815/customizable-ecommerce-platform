<template>
  <button  type="button" @click="logout">
    登出
  </button>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { storeLogout } from '../../api/auth.js'

const router = useRouter()

async function logout() {
    try {
        const data = await storeLogout()

        console.log(data)

        router.push('/store/login')

    } catch (error) {
        console.error('登出失敗', error)
    }
}
</script>

<style>

</style>