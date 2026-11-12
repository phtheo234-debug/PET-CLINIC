const API_BASE_URL = "/api/v1";

function getToken() {
    return localStorage.getItem("access_token");
}

function getCurrentUser() {
    const raw = localStorage.getItem("current_user");
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        localStorage.removeItem("current_user");
        return null;
    }
}

function checkAuth() {
    if (!getToken()) {
        window.location.href = "/login";
    }
}

async function apiRequest(endpoint, options = {}) {
    const token = getToken();
    const headers = {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...(token ? { Authorization: "Bearer " + token } : {}),
        ...(options.headers || {}),
    };

    const response = await fetch(API_BASE_URL + endpoint, {
        ...options,
        headers,
    });

    if (response.status === 401) {
        localStorage.removeItem("access_token");
        localStorage.removeItem("current_user");
        window.location.href = "/login";
        throw new Error("Phiên đăng nhập hết hạn hoặc không hợp lệ");
    }

    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
        if (result.code === "PASSWORD_CHANGE_REQUIRED") {
            if (window.location.pathname !== "/change-password") {
                window.location.href = "/change-password";
            }
            throw new Error(result.message);
        }
        if (result.errors) {
            const firstError = Object.values(result.errors).flat()[0];
            throw new Error(firstError || result.message);
        }
        throw new Error(result.message || `Lỗi máy chủ (${response.status})`);
    }
    return result;
}

async function logout() {
    try {
        await apiRequest("/auth/logout", { method: "POST" });
    } finally {
        localStorage.removeItem("access_token");
        localStorage.removeItem("current_user");
        window.location.href = "/login";
    }
}

function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isHidden = input.type === "password";
    input.type = isHidden ? "text" : "password";

    const icon = button.querySelector("i");
    if (icon) {
        icon.classList.toggle("fa-eye", !isHidden);
        icon.classList.toggle("fa-eye-slash", isHidden);
    }

    const label = isHidden ? "Ẩn mật khẩu" : "Hiện mật khẩu";
    button.setAttribute("aria-label", label);
    button.setAttribute("title", label);
}

function formatCurrency(amount) {
    return Number(amount || 0).toLocaleString("vi-VN") + " đ";
}

async function initPage() {
    checkAuth();
    const roleMap = {
        QUAN_TRI: "Quản trị viên",
        BAC_SI: "Bác sĩ thú y",
        NHAN_VIEN: "Nhân viên / Lễ tân",
    };

    let user = getCurrentUser();
    if (!user) {
        try {
            const res = await apiRequest("/users/me");
            user = res.data || res;
            if (user && (user.ho_ten || user.username)) {
                localStorage.setItem("current_user", JSON.stringify(user));
            }
        } catch (e) {
            return;
        }
    }

    if (user && user.phai_doimatkhau) {
        window.location.href = "/change-password";
        return;
    }

    const nameEl = document.getElementById("navUserName");
    const roleEl = document.getElementById("navUserRole");
    if (nameEl) {
        nameEl.innerText = (user && (user.ho_ten || user.username)) || "Tài khoản";
    }
    if (roleEl && user && user.vaitro) {
        roleEl.innerText = roleMap[user.vaitro] || user.vaitro;
    }
    const adminNav = document.getElementById('adminUsersNav');
    if (adminNav && user && user.vaitro === 'QUAN_TRI') {
        adminNav.classList.remove('d-none');
    }
}
