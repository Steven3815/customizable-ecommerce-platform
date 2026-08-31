<template>
  <button  type="button" @click="logout">
    登出
  </button>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { customerLogout } from '../../api/auth.js'

const router = useRouter()
const route = useRoute()

const storeId = route.params.storeId

async function logout() {
    try {
        const data = await customerLogout()

        console.log(data)

        router.push(`/store-${storeId}`)

    } catch (error) {
        console.error('登出失敗:', error)
    }
}
</script>

<style>

</style>