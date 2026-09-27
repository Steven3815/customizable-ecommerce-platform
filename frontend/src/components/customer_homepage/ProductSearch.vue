<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const searchModalVisible = ref(false)
const searchKeyword = ref('')
const recentSearches = ref([])

const loadRecentSearches = () => {
  const searches = localStorage.getItem('recentProductSearches')

  if (searches) {
    recentSearches.value = JSON.parse(searches)
  }
}

const saveRecentSearch = () => {
  const keyword = searchKeyword.value.trim()

  if (!keyword) {
    return
  }

  recentSearches.value = recentSearches.value.filter(
    item => item !== keyword
  )

  recentSearches.value.unshift(keyword)

  recentSearches.value = recentSearches.value.slice(0, 5)

  localStorage.setItem(
    'recentProductSearches',
    JSON.stringify(recentSearches.value)
  )
}

const performSearch = () => {
  const keyword = searchKeyword.value.trim()

  if (!keyword) {
    return
  }

  saveRecentSearch()

  searchModalVisible.value = false

  router.push({
    name: 'CustomerProductSearch',
    params: {
      storeId: route.params.storeId
    },
    query: {
      keyword
    }
  })
}

const selectRecentSearch = (keyword) => {
  searchKeyword.value = keyword
  performSearch()
}

const clearRecentSearches = () => {
  recentSearches.value = []

  localStorage.removeItem('recentProductSearches')
}

onMounted(() => {
  loadRecentSearches()
})
</script>

<template>
  <div>

    <CSearchButton
      class="ms-2"
      @trigger="searchModalVisible = true"
      aria-label="搜尋商品"
      aria-controls="headerSearchModal"
    />

    <CModal
      id="headerSearchModal"
      :visible="searchModalVisible"
      @close="
        () => {
          searchModalVisible = false
        }
      "
      aria-labelledby="headerSearchModalTitle"
    >
      <CModalHeader
        dismiss
        @close="
          () => {
            searchModalVisible = false
          }
        "
      >
        <CModalTitle
          id="headerSearchModalTitle"
          class="w-100"
        >
          <CFormInput
            v-model="searchKeyword"
            type="search"
            placeholder="搜尋商品"
            aria-label="搜尋商品"
            @keyup.enter="performSearch"
          />
        </CModalTitle>
      </CModalHeader>

      <CModalBody>

        <div
          v-if="recentSearches.length > 0"
          class="d-flex justify-content-between align-items-center mb-2"
        >
          <p class="text-body-secondary small mb-0">
            最近搜尋
          </p>

          <CButton
            color="link"
            size="sm"
            class="text-decoration-none p-0"
            @click="clearRecentSearches"
          >
            清除
          </CButton>
        </div>

        <p
          v-else
          class="text-body-secondary small mb-2"
        >
          最近搜尋
        </p>

        <CListGroup
          v-if="recentSearches.length > 0"
          flush
        >
          <CListGroupItem
            v-for="keyword in recentSearches"
            :key="keyword"
            as="button"
            type="button"
            class="text-start"
            @click="selectRecentSearch(keyword)"
          >
            {{ keyword }}
          </CListGroupItem>
        </CListGroup>

        <p
          v-else
          class="text-body-secondary small mb-0"
        >
          尚無搜尋紀錄
        </p>

      </CModalBody>
    </CModal>

  </div>
</template>

<style scoped>
:deep(.search-button-placeholder) {
  font-size: 0;
}

:deep(.search-button-placeholder::after) {
  content: '搜尋商品';
  font-size: 1rem;
}

:deep(.search-button-key) {
  display: none;
}

:deep(.search-button) {
  width: 270px;
  justify-content: flex-start;
}
</style>