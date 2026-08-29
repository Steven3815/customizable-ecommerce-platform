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

export async function customerLogin(storeId, email, password) {
    try {
        const response = await fetch(
            'http://localhost/ecommerce-platform/backend/api/auth/customer_login.php',
            {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    store_id: storeId,
                    email: email,
                    password: password
                })
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '登入失敗')
        }

        return data

    } catch (error) {
        console.error('Customer login error:', error)
        throw error
    }
}

export async function customerLogout() {
    try {
        const response = await fetch(
            'http://localhost/ecommerce-platform/backend/api/auth/customer_logout.php',
            {
                method: 'POST',
                credentials: 'include'
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '登出失敗')
        }

        return data

    } catch (error) {
        console.error('Customer logout error:', error)
        throw error
    }
}

export async function storeRegister(storeName, ownerName, email, password, phone, mode) {
    try {
        const response = await fetch(
        'http://localhost/ecommerce-platform/backend/api/auth/store_register.php',
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                store_name: storeName,
                owner_name: ownerName,
                email: email,
                password: password,
                phone: phone,
                store_mode: mode
            })
        }
        )

        const data = await response.json()

        if(!response.ok) {
            throw new Error(data.error || '註冊失敗')
        }

        return data

    } catch (error) {
        console.error('Store register error:', error)
        throw error
    }
}

export async function storeLogin(email, password) {
    try {
        const response = await fetch(
            'http://localhost/ecommerce-platform/backend/api/auth/store_login.php',
            {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '登入失敗')
        }

        return data

    } catch (error) {
        console.error('Store login error:', error)
        throw error
    }
}

export async function storeLogout() {
    try {
        const response = await fetch(
            'http://localhost/ecommerce-platform/backend/api/auth/store_logout.php',
            {
                method: 'POST',
                credentials: 'include'
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(data.error || '登出失敗')
        }

        return data

    } catch (error) {
        console.error('Store logout error:', error)
        throw error
    }
}