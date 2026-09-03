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

// Dashboard

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

// HomepageSettings

export async function getHomepageSettings() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/settings/get_settings.php',
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得首頁設定失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得首頁設定失敗:', error)
    throw error
  }
}


export async function updateHomepageSettings(payload) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/settings/update_settings.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新首頁設定失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('更新首頁設定失敗:', error)
    throw error
  }
}


export async function reorderCategories(categoryIds) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/settings/reorder_category.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          category_ids: categoryIds
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '重新排列分類失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('重新排列分類失敗:', error)
    throw error
  }
}


export async function deleteCategories(categoryIds) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/settings/delete_category.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          category_ids: categoryIds
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除分類失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('刪除分類失敗:', error)
    throw error
  }
}

export async function createCategory(categoryName) {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/settings/add_category.php',
    {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        category_name: categoryName
      })
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '新增分類失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

// Homepage Settings Banner

export async function getHomepageBanner() {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/banner/get_banner.php',
    {
      credentials: 'include'
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '取得 Banner 資料失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

export async function createHomepageBanner(formData) {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/banner/add_banner.php',
    {
      method: 'POST',
      credentials: 'include',
      body: formData
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '新增 Banner 失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

export async function updateHomepageBanner(formData) {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/banner/update_banner.php',
    {
      method: 'POST',
      credentials: 'include',
      body: formData
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '更新 Banner 失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

export async function deleteHomepageBanner() {
  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/banner/delete_banner.php',
    {
      method: 'POST',
      credentials: 'include'
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '刪除 Banner 失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

export async function deleteHomepageBannerImage(bannerId) {
  const formData = new FormData()

  formData.append('banner_id', bannerId)

  const response = await fetch(
    'http://localhost/ecommerce-platform/backend/api/store/homepage/banner/delete_banner_image.php',
    {
      method: 'POST',
      credentials: 'include',
      body: formData
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(
      data.error || '刪除橫幅圖片失敗'
    )

    error.status = response.status

    throw error
  }

  return data
}

// Homepage Settings Slider

export async function getHomepageSlider() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/slider/get_sliders.php',
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得首頁輪播圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得首頁輪播圖片失敗:',
      error
    )

    throw error
  }
}

export async function createHomepageSlider(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/slider/add_slider.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '新增首頁輪播圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '新增首頁輪播圖片失敗:',
      error
    )

    throw error
  }
}

export async function updateHomepageSlider(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/slider/update_slider.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新首頁輪播圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '更新首頁輪播圖片失敗:',
      error
    )

    throw error
  }
}

export async function deleteHomepageSlider(imageId) {
  try {
    const formData = new FormData()

    formData.append(
      'image_id',
      imageId
    )

    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/slider/delete_slider.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除首頁輪播圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '刪除首頁輪播圖片失敗:',
      error
    )

    throw error
  }
}

export async function reorderHomepageSliders(imageIds) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/slider/reorder_slider.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          image_ids: imageIds
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '調整首頁輪播圖片順序失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '調整首頁輪播圖片順序失敗:',
      error
    )

    throw error
  }
}