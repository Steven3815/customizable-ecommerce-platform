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

// Homepage Settings Product

export async function getHomepageProducts(categoryId = null) {
  try {
    const url = categoryId
      ? `http://localhost/ecommerce-platform/backend/api/store/homepage/product/get_products.php?category_id=${categoryId}`
      : 'http://localhost/ecommerce-platform/backend/api/store/homepage/product/get_products.php'

    const response = await fetch(
      url,
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得商品資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得商品資料失敗:',
      error
    )

    throw error
  }
}

export async function createHomepageProduct(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/product/add_product.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '新增商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '新增商品失敗:',
      error
    )

    throw error
  }
}

export async function updateHomepageProduct(productId, productName) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/product/update_product.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          product_id: productId,
          product_name: productName
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '更新商品失敗:',
      error
    )

    throw error
  }
}

export async function deleteHomepageProduct(productId) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/product/delete_product.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          product_id: productId
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '刪除商品失敗:',
      error
    )

    throw error
  }
}

export async function reorderHomepageProducts(categoryId, productIds) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/product/reorder_product.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          category_id: categoryId,
          product_ids: productIds
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '調整商品順序失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '調整商品順序失敗:',
      error
    )

    throw error
  }
}

// Homepage Settings Footer

export async function getFooterSettings() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/footer/get_footer.php',
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得 Footer 設定失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得 Footer 設定失敗:',
      error
    )

    throw error
  }
}

export async function updateFooterSettings(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/homepage/footer/update_footer.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新 Footer 設定失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '更新 Footer 設定失敗:',
      error
    )

    throw error
  }
}

// Store Order List

export async function getOrders({
  search = '',
  status = 'all',
  refundStatus = 'all',
  paymentConfirmStatus = 'all',
  sort = 'newest',
  page = 1
} = {}) {
  try {
    const params = new URLSearchParams()

    if (search) {
      params.append('search', search)
    }

    params.append('status', status)
    params.append('refund_status', refundStatus)
    params.append(
      'payment_confirm_status',
      paymentConfirmStatus
    )
    params.append('sort', sort)
    params.append('page', page)

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/store/order/get_orders.php?${params.toString()}`,
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得訂單資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得訂單資料失敗:', error)

    throw error
  }
}

// Store Order Detail

export async function getOrder(orderId) {
  try {
    const params = new URLSearchParams()

    params.append('order_id', orderId)

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/store/order/get_order.php?${params.toString()}`,
      {
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得訂單詳細資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得訂單詳細資料失敗:', error)

    throw error
  }
}

export async function updateOrderDelivery({
  order_id,
  delivery_status
} = {}) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/order/update_order.php',
      {
        method: 'PUT',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          order_id,
          delivery_status
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新訂單配送狀態失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('更新訂單配送狀態失敗:', error)

    throw error
  }
}
export async function confirmOrderPayment({
  order_id,
  payment_confirm_status,
  payment_note
} = {}) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/payment/confirm_payment.php',
      {
        method: 'PUT',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          order_id,
          payment_confirm_status,
          payment_note
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '確認訂單付款失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('確認訂單付款失敗:', error)

    throw error
  }
}

// Store Product List

export async function getProducts({
  page = 1,
  keyword = null,
  category_id = null,
  status = null,
  stock_status = null
} = {}) {
  try {
    const params = new URLSearchParams()

    params.append('page', page)

    if (keyword !== null && keyword !== '') {
      params.append('keyword', keyword)
    }

    if (category_id !== null) {
      params.append('category_id', category_id)
    }

    if (status !== null) {
      params.append('status', status)
    }

    if (stock_status !== null) {
      params.append('stock_status', stock_status)
    }

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/store/product/get_products.php?${params.toString()}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得商品管理資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得商品管理資料失敗:', error)

    throw error
  }
}

// Store Product Detail

export async function getProduct(productId) {
  try {
    const params = new URLSearchParams()

    params.append('product_id', productId)

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/store/product/get_product.php?${params.toString()}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得商品資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得商品資料失敗:', error)

    throw error
  }
}

export async function createProduct(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/product/create_product.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '建立商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('建立商品失敗:', error)

    throw error
  }
}

export async function updateProduct(formData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/product/update_product.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('更新商品失敗:', error)

    throw error
  }
}

export async function deleteProductImage(imageId) {
  try {
    const formData = new FormData()

    formData.append('image_id', imageId)

    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/product/delete_product_image.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除商品圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('刪除商品圖片失敗:', error)

    throw error
  }
}

export async function deleteProduct(productId) {
  try {
    const formData = new FormData()

    formData.append('product_id', productId)

    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/product/delete_product.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('刪除商品失敗:', error)

    throw error
  }
}

export async function reorderProductImages(productId, imageIds) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/store/product/reorder_product_image.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          product_id: productId,
          image_ids: imageIds
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '重新排列商品圖片失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('重新排列商品圖片失敗:', error)

    throw error
  }
}

// Store Product List

export async function getCustomers({
  page = 1,
  search = null,
  sort = 'created_at_desc'
} = {}) {
  try {
    const params = new URLSearchParams()

    params.append('page', page)

    if (search !== null && search !== '') {
      params.append('search', search)
    }

    if (sort !== null && sort !== '') {
      params.append('sort', sort)
    }

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/store/customer/get_customers.php?${params.toString()}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得客戶管理資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得客戶管理資料失敗:', error)

    throw error
  }
}