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
export async function getCustomerOrders(
  storeId,
  orderStatus = 'all',
  paymentStatus = 'all',
  paymentConfirmStatus = 'all',
  deliveryStatus = 'all',
  refundStatus = 'all'
) {
  try {
    const params = new URLSearchParams({
      store_id: storeId,
      order_status: orderStatus,
      payment_status: paymentStatus,
      payment_confirm_status: paymentConfirmStatus,
      delivery_status: deliveryStatus,
      refund_status: refundStatus
    })

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/order/get_orders.php?${params}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得訂單失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得訂單失敗:',
      error
    )

    throw error
  }
}

export async function getCustomerOrder(storeId, orderId) {
  try {
    const params = new URLSearchParams({
      store_id: storeId,
      order_id: orderId
    })

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/order/get_order.php?${params}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得訂單失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得訂單失敗:',
      error
    )

    throw error
  }
}

export async function updateCustomerOrder(
  storeId,
  orderId,
  receiverName,
  receiverPhone,
  receiverAddress,
  deliveryMethod
) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/order/update_order.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          order_id: orderId,
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
        data.error || '修改訂單失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '修改訂單失敗:',
      error
    )

    throw error
  }
}

export async function deleteCustomerOrder(
  storeId,
  orderNumber
) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/order/delete_order.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          order_number: orderNumber
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '刪除訂單失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '刪除訂單失敗:',
      error
    )

    throw error
  }
}

// Payment

export async function getCustomerPaymentMethods(
  storeId
) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/payment/get_payment_methods.php?store_id=${storeId}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得付款與配送方式失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得付款與配送方式失敗:',
      error
    )

    throw error
  }
}

export async function getCustomerPayment(
  storeId,
  orderId
) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/payment/get_payment.php?store_id=${storeId}&order_id=${orderId}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得付款資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得付款資料失敗:',
      error
    )

    throw error
  }
}

export async function createCustomerPayment(
  storeId,
  orderId,
  paymentMethod
) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/payment/create_payment.php',
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          order_id: orderId,
          payment_method: paymentMethod
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '建立付款失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '建立付款失敗:',
      error
    )

    throw error
  }
}
export async function payCustomerCreditCard(
  storeId,
  orderId,
  transactionAmount,
  cardNumber,
  expiryDate,
  phone
) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/payment/credit_card.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          order_id: orderId,
          transaction_amount: transactionAmount,
          card_number: cardNumber,
          expiry_date: expiryDate,
          phone
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '信用卡付款失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '信用卡付款失敗:',
      error
    )

    throw error
  }
}

export async function getCustomerTransferPayment(orderId) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/payment/get_transfer.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          order_id: orderId
        })
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得轉帳資訊失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得轉帳資訊失敗:',
      error
    )

    throw error
  }
}

export async function submitCustomerTransferPayment(
  orderId,
  paymentProofImage
) {
  try {
    const formData = new FormData()

    formData.append(
      'order_id',
      orderId
    )

    if (paymentProofImage) {
      formData.append(
        'payment_proof_image',
        paymentProofImage
      )
    }

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/payment/transfer_confirm.php`,
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '轉帳確認失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '轉帳確認失敗:',
      error
    )

    throw error
  }
}

// Profile

export async function getCustomerProfile() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/profile/get_profile.php',
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得會員資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('取得會員資料失敗:', error)

    throw error
  }
}

export async function updateCustomerProfile(profile) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/profile/update_profile.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(profile)
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '更新會員資料失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('更新會員資料失敗:', error)

    throw error
  }
}

export async function changeCustomerPassword(passwordData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/profile/update_password.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        credentials: 'include',
        body: JSON.stringify(passwordData)
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '修改密碼失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error('修改密碼失敗:', error)

    throw error
  }
}

// Service

export async function getCustomerServices(
  storeId,
  status = 'all'
) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/service/get_services.php?store_id=${storeId}&status=${status}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得客服案件失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '取得客服案件失敗:',
      error
    )

    throw error
  }
}

export async function getCustomerService(storeId, serviceId) {
  const response = await fetch(
    `http://localhost/ecommerce-platform/backend/api/customer/service/get_service.php?store_id=${storeId}&service_id=${serviceId}`,
    {
      method: 'GET',
      credentials: 'include'
    }
  )

  const data = await response.json()

  if (!response.ok) {
    const error = new Error(data.error || '取得客服案件失敗')
    error.status = response.status
    error.message = data.error || '取得客服案件失敗'
    throw error
  }

  return data
}

export async function createCustomerService(serviceData) {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/service/create_service.php',
      {
        method: 'POST',
        credentials: 'include',
        body: serviceData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '建立客服案件失敗'
      )

      error.status = response.status

      throw error
    }

    return data

  } catch (error) {
    console.error(
      '建立客服案件失敗:',
      error
    )

    throw error
  }
}

export async function deleteCustomerService(
  storeId,
  serviceId
) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/service/delete_service.php?store_id=${storeId}&service_id=${serviceId}`,
      {
        method: 'PUT',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取消客服案件失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '取消客服案件失敗:',
      error
    )
    throw error
  }
}

export async function createCustomerRefund(
  orderId,
  refundReason,
  refundDescription,
  refundImage
) {
  try {
    const formData = new FormData()

    formData.append('order_id', orderId)
    formData.append('refund_reason', refundReason)
    formData.append('refund_description', refundDescription)

    if (refundImage) {
      formData.append('refund_image', refundImage)
    }

    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/refund/create_refund.php',
      {
        method: 'POST',
        credentials: 'include',
        body: formData
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '建立退款申請失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '建立退款申請失敗:',
      error
    )
    throw error
  }
}

export async function getCustomerRefunds(storeId) {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/refund/get_refunds.php?store_id=${storeId}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得退款列表失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '取得退款列表失敗:',
      error
    )
    throw error
  }
}

// Member Center

export async function getMemberOrders(params = {}) {
  try {
    const query = new URLSearchParams(params).toString()

    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/member/get_orders.php${query ? `?${query}` : ''}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得會員中心訂單列表失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '取得會員中心訂單列表失敗:',
      error
    )
    throw error
  }
}

export async function getMemberRefunds() {
  try {
    const response = await fetch(
      'http://localhost/ecommerce-platform/backend/api/customer/member/get_refunds.php',
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得會員中心退款列表失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '取得會員中心退款列表失敗:',
      error
    )
    throw error
  }
}

export async function getMemberServices(status = 'all') {
  try {
    const response = await fetch(
      `http://localhost/ecommerce-platform/backend/api/customer/member/get_services.php?status=${status}`,
      {
        method: 'GET',
        credentials: 'include'
      }
    )

    const data = await response.json()

    if (!response.ok) {
      const error = new Error(
        data.error || '取得會員中心客服列表失敗'
      )

      error.status = response.status
      throw error
    }

    return data
  } catch (error) {
    console.error(
      '取得會員中心客服列表失敗:',
      error
    )
    throw error
  }
}