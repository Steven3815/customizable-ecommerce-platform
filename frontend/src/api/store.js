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