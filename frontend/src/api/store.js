export async function checkStoreAuth() {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/check_auth.php',
    {
      credentials: 'include'
    }
  )

  return response.ok
}

export async function getStore(storeId) {
    const response = await fetch(
        `http://localhost/ecommerce-platform/backend/api/customer/get_store.php?store_id=${storeId}`
    )

    const data = await response.json()

    if (!response.ok) {
        throw new Error(data.error || '取得商店資料失敗')
    }

    return data

}

export async function getStoreDashboard() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/get_homepage.php',
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得 Dashboard 資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得 Dashboard 資料失敗:', error)
    throw error
  }
}