document.addEventListener("DOMContentLoaded", () => {
    const alertBox = document.getElementById("forgotAlert");
    const show = (message, success = false) => {
        alertBox.textContent = message;
        alertBox.className = "alert " + (success ? "alert-success" : "alert-danger");
    };
    document.getElementById("forgotForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const button = document.getElementById("sendCode");
        button.disabled = true;
        try {
            const response = await fetch("/api/v1/auth/forgot-password", {
                method: "POST",
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({ email: document.getElementById("email").value.trim() })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Không gửi được mã xác nhận.");
            show(result.message, true);
        } catch (error) { show(error.message); }
        finally { button.disabled = false; }
    });
    document.getElementById("resetForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const password = document.getElementById("newPassword").value;
        const confirmation = document.getElementById("confirmPassword").value;
        if (password !== confirmation) return show("Mật khẩu nhập lại không khớp.");
        try {
            const response = await fetch("/api/v1/auth/reset-password", {
                method: "POST",
                headers: { Accept: "application/json", "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: document.getElementById("email").value.trim(),
                    code: document.getElementById("code").value.trim(),
                    mat_khau_moi: password,
                    mat_khau_moi_confirmation: confirmation
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || "Không thể đặt lại mật khẩu.");
            show(result.message + " Đang chuyển về trang đăng nhập...", true);
            setTimeout(() => window.location.href = "/login", 1500);
        } catch (error) { show(error.message); }
    });
});