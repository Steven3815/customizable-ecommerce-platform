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

        router.push(`/store`)

    } catch (error) {
        console.error(error)
    }
}
</script>

<style>

</style>