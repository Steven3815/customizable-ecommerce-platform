// Login Status

export async function getCustomerLoginStatus() {
    try {
        const response = await fetch(
            'http://localhost/ecommerce-platform/backend/api/auth/customer_status.php',
            {
                method: 'GET',
                credentials: 'include'
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '取得登入狀態失敗')
        }

        return data

    } catch (error) {
        console.error('Customer login status error:', error)
        throw error
    }
}

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

// Cart
export async function getCustomerCart(storeId, sortBy = 'created_at', sortOrder = 'desc') {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/cart/get_cart.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          sort_by: sortBy,
          sort_order: sortOrder
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得購物車資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得購物車資料失敗:', error)

    throw error
  }
}

export async function updateCustomerCart(cartItemId, quantity, storeId) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/cart/update_cart.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          cart_item_id: cartItemId,
          quantity: quantity,
          store_id: storeId
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新購物車數量失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('更新購物車數量失敗:', error)

    throw error
  }
}

export async function deleteCustomerCart(cartItemId, storeId) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/cart/delete_cart.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          cart_item_id: cartItemId,
          store_id: storeId
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除購物車商品失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('刪除購物車商品失敗:', error)

    throw error
  }
}

export async function addCustomerCart(productId, quantity, storeId, specId = null) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/cart/add_cart.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          product_id: productId,
          quantity: quantity,
          store_id: storeId,
          spec_id: specId
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '加入購物車失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('加入購物車失敗:', error)

    throw error
  }
}

// Product

export async function getCustomerProduct(productId, storeId) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/product/get_product.php?id=${productId}&store_id=${storeId}`,
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

export async function getCustomerProducts(
  storeId,
  categoryId = null,
  sort = 'asc',
  keyword = ''
) {
  try {
    const params = new URLSearchParams({
      store_id: storeId,
      sort,
      keyword
    })

    if (categoryId !== null && categoryId !== '') {
      params.append('category_id', categoryId)
    }

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/product/get_products.php?${params.toString()}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得商品列表失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得商品列表失敗:',
      error
    )

    throw error
  }
}

// order
export async function createCustomerOrder(
  cartItemIds,
  receiverName,
  receiverPhone,
  receiverAddress,
  deliveryMethod
) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/order/create_order.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          cart_item_ids: cartItemIds,
          receiver_name: receiverName,
          receiver_phone: receiverPhone,
          receiver_address: receiverAddress,
          delivery_method: deliveryMethod
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '建立訂單失敗'
      )

      error.status = response.status
      error.errorType = data.error_type
      error.stock = data.stock

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '建立訂單失敗:',
      error
    )

    throw error
  }
}