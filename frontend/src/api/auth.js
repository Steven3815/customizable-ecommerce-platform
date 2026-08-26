export async function customerRegister(storeId, name, email, password) {
    try {
        const response = await fetch(
        'http://localhost/ecommerce-platform/backend/api/auth/customer_register.php',
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                store_id: storeId,
                name: name,
                email: email,
                password: password
            })
        }
        )

        const data = await response.json()

        if(!response.ok) {
            throw new Error(data.error || '註冊失敗')
        }

        return data

    } catch (error) {
        console.error('Customer register error:', error)
        throw error
    }
}

export async function getStore(storeId) {
    try {
        const response = await fetch(
            `http://localhost/ecommerce-platform/backend/api/customer/get_store.php?store_id=${storeId}`
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '取得商店資料失敗')
        }

        return data

    } catch (error) {
        console.error('Get store error:', error)
        throw error
    }
}