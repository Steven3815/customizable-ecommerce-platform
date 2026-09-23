// Customer Home

export async function getCustomerHome(storeId) {
  try {
    const params = new URLSearchParams()

    params.append('store_id', storeId)

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/get_homepage.php?${params.toString()}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得首頁資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得首頁資料失敗:', error)

    throw error
  }
}