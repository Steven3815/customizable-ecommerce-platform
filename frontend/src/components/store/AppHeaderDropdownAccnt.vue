<script setup>
import { useRouter } from 'vue-router'
import { storeLogout } from '../../api/auth.js'

const router = useRouter()

const itemsCount = 42

async function logout() {
  try {
    await storeLogout()
    router.push('/store/login')
  } catch (error) {
    console.error('登出失敗', error)
  }
}
</script>

<template>
  <CDropdown placement="bottom-end" variant="nav-item">
    <CDropdownToggle class="auto" :caret="false">
      <CIcon icon="cil-user" size="lg" />
    </CDropdownToggle>

    <CDropdownMenu class="pt-0">
      <CDropdownHeader
        component="h6"
        class="bg-body-secondary text-body-secondary fw-semibold mb-2 rounded-top"
      >
        帳號管理
      </CDropdownHeader>

      <CDropdownItem>
        <CIcon icon="cil-bell" />
        通知
        <CBadge color="info" class="ms-auto">{{ itemsCount }}</CBadge>
      </CDropdownItem>

      <CDropdownHeader
        component="h6"
        class="bg-body-secondary text-body-secondary fw-semibold my-2"
      >
        設定
      </CDropdownHeader>

      <CDropdownItem @click="router.push('/store/admin/profile')">
        <CIcon icon="cil-user" />
        基本資料
      </CDropdownItem>

      <CDropdownItem @click="router.push('/store/admin/settings')">
        <CIcon icon="cil-settings" />
        商店設定
      </CDropdownItem>

      <CDropdownDivider />

      <CDropdownItem @click="logout">
        <CIcon icon="cil-lock-locked" />
        登出
      </CDropdownItem>
    </CDropdownMenu>
  </CDropdown>
</template>

<style scoped>
:deep(.dropdown-item){
  cursor: default;
}
</style>