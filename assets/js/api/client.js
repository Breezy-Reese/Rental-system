const API_BASE_URL = "http://localhost:5000/api";

const api = {
    getToken() {
        return localStorage.getItem("propertypro_token");
    },

    getUser() {
        const user = localStorage.getItem("propertypro_user");

        if (!user) {
            return null;
        }

        try {
            return JSON.parse(user);
        } catch {
            return null;
        }
    },

    setAuth(data) {
        if (data.token) {
            localStorage.setItem(
                "propertypro_token",
                data.token
            );
        }

        if (data.user) {
            localStorage.setItem(
                "propertypro_user",
                JSON.stringify(data.user)
            );
        }
    },

    logout() {
        localStorage.removeItem("propertypro_token");
        localStorage.removeItem("propertypro_user");
    },

    isAuthenticated() {
        return !!this.getToken();
    },

    async request(endpoint, options = {}) {
        const token = this.getToken();

        const headers = {
            "Content-Type": "application/json",
            ...(options.headers || {}),
        };

        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(
            `${API_BASE_URL}${endpoint}`,
            {
                ...options,
                headers,
            }
        );

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error(
                `Server returned HTTP ${response.status}`
            );
        }

        if (!response.ok) {
            throw new Error(
                result.message ||
                `Request failed with HTTP ${response.status}`
            );
        }

        return result;
    },

    async login(email, password) {
        const result = await this.request(
            "/auth/login",
            {
                method: "POST",
                body: JSON.stringify({
                    email,
                    password,
                }),
            }
        );

        if (result.success) {
            this.setAuth(result.data);
        }

        return result;
    },

    async register(name, email, password, phone = "") {
        const result = await this.request(
            "/auth/register",
            {
                method: "POST",
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    phone,
                }),
            }
        );

        if (result.success) {
            this.setAuth(result.data);
        }

        return result;
    },

    async me() {
        return this.request("/auth/me");
    },

    async adminDashboard() {
        return this.request("/admin/dashboard");
    },

    async customerDashboard() {
        return this.request("/customer/dashboard");
    },

    async properties() {
        return this.request("/admin/properties");
    },

    async units() {
        return this.request("/admin/units");
    },

    async tenants() {
        return this.request("/admin/tenants");
    },

    async leases() {
        return this.request("/admin/leases");
    },

    async payments() {
        return this.request("/admin/payments");
    },

    async maintenance() {
        return this.request("/admin/maintenance");
    },

    async expenses() {
        return this.request("/admin/expenses");
    },

    async notifications() {
        return this.request("/notifications");
    },

    async myLease() {
        return this.request("/customer/lease");
    },

    async myPayments() {
        return this.request("/customer/payments");
    },

    async myMaintenance() {
        return this.request("/customer/maintenance");
    },

    async customerProfile() {
        return this.request("/customer/profile");
    },
};

window.PropertyProAPI = api;
