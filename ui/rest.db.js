import axios from 'axios';

// Base configuration
const API_BASE_URL = process.env.API_BASE_URL || null;
const API_KEY = process.env.API_BEARER_TOKEN || null;

// Configuring axios instance with default headers
const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${API_KEY}`
    }
});

class DBService {
    constructor() {
        // Add request interceptor for error handling
        api.interceptors.response.use(
            response => response,
            error => {
                console.error('API Error:', error?.response?.data || error.message);
                return Promise.reject(error);
            }
        );
    }

    // Get multiple items by a specific key-value pair
    async getItemsByKeyValue(key, value, table) {
        try {
            const response = await api.get(`/${table}`);

            if (response.data.status === 'success') {
                // Filter the results by key-value pair
                const allItems = response.data.message;

                // Convert array to object with IDs as keys (similar to Firebase)
                const filteredItems = {};

                allItems.forEach(item => {
                    if (item[key] === value) {
                        filteredItems[item.id] = item;
                    }
                });

                return Object.keys(filteredItems).length ? filteredItems : null;
            }

            return null;
        } catch (error) {
            console.error(`Error in getItemsByKeyValue: ${error}`);
            return null;
        }
    }

    // Get a single item by a specific key-value pair
    async getItemByKeyValue(key, value, table) {
        try {
            const response = await api.get(`/${table}`);

            if (response.data.status === 'success') {
                // Find the first item that matches the key-value pair
                const allItems = response.data.message;
                const matchedItem = allItems.find(item => item[key] === value);

                return matchedItem || null;
            }

            return null;
        } catch (error) {
            console.error(`Error in getItemByKeyValue: ${error}`);
            return null;
        }
    }

    // Get item ID by a specific key-value pair
    async getItemKey(key, value, table) {
        try {
            const response = await api.get(`/${table}`);

            if (response.data.status === 'success') {
                // Find the ID of the first item that matches the key-value pair
                const allItems = response.data.message;
                const matchedItem = allItems.find(item => item[key] === value);

                return matchedItem ? matchedItem.id : null;
            }

            return null;
        } catch (error) {
            console.error(`Error in getItemKey: ${error}`);
            return null;
        }
    }

    // Get an item by ID
    async getItem(key, table) {
        try {
            const response = await api.get(`/${table}/${key}`);

            if (response.data.status === 'success') {
                return response.data.message;
            }

            return null;
        } catch (error) {
            console.error(`Error in getItem: ${error}`);
            return null;
        }
    }

    // Get all items from a table
    async getAll(table) {
        try {
            const response = await api.get(`/${table}`);

            if (response.data.status === 'success') {
                // Convert array to object with IDs as keys (similar to Firebase)
                const result = {};
                response.data.message.forEach(item => {
                    result[item.id] = item;
                });

                return result;
            }

            return {};
        } catch (error) {
            console.error(`Error in getAll: ${error}`);
            return {};
        }
    }

    // Create a new item
    async create(data, table) {
        try {
            const response = await api.post(`/${table}`, data);

            if (response.data.status === 'success') {
                // Format the response to be similar to Firebase's push response
                const id = response.data.message.id;

                return {
                    key: id,
                    ref: {
                        key: id,
                        path: `/${table}/${id}`
                    }
                };
            }

            throw new Error(response.data.message || 'Failed to create item');
        } catch (error) {
            console.error(`Error in create: ${error}`);
            throw error;
        }
    }

    // Update an existing item
    async update(key, value, table) {
        try {
            const response = await api.put(`/${table}/${key}`, value);

            if (response.data.status === 'success') {
                return response.data.message;
            }

            throw new Error(response.data.message || 'Failed to update item');
        } catch (error) {
            console.error(`Error in update: ${error}`);
            throw error;
        }
    }

    // Delete an item
    async delete(key, table) {
        try {
            const response = await api.delete(`/${table}/${key}`);

            if (response.data.status === 'success') {
                return true;
            }

            throw new Error(response.data.message || 'Failed to delete item');
        } catch (error) {
            console.error(`Error in delete: ${error}`);
            throw error;
        }
    }

    // Delete all items in a table
    // Note: Your PHP API doesn't seem to have this endpoint
    async deleteAll(table) {
        console.warn('deleteAll method is not implemented in the PHP API');
        return false;
    }

    // Upload an image
    // Note: This would require a multipart/form-data request
    async upload(image, custom) {
        try {
            // Create a FormData object to send the file
            const formData = new FormData();
            formData.append('file', image);
            formData.append('filename', custom);

            // Special axios request for file upload
            const response = await axios.post(
                `${API_BASE_URL}/upload`,
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'Authorization': `Bearer ${API_KEY}`
                    }
                }
            );

            if (response.data.status === 'success') {
                return response.data.message;
            }

            throw new Error(response.data.message || 'Failed to upload image');
        } catch (error) {
            console.error(`Error in upload: ${error}`);
            throw error;
        }
    }
}

export default new DBService();