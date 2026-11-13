document.addEventListener("DOMContentLoaded", () => {
    if (!getToken()) {
        window.location.href = "/login";
        return;
    }

    const form = document.getElementById("changePasswordForm");
    const alertBox = document.getElementById("changePasswordAlert");
    const button = document.getElementById("btnChangePassword");

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        alertBox.classList.add("d-none");

        const currentPassword = document.getElementById("mat_khau_cu").value;
        const newPassword = document.getElementById("mat_khau_moi").value;
        const confirmation = document.getElementById("mat_khau_moi_confirmation").value;

        if (newPassword !== confirmation || newPassword === currentPassword) {
            alertBox.textContent = newPassword !== confirmation
                ? "Mật khẩu mới nhập lại không khớp."
                : "Mật khẩu mới phải khác mật khẩu hiện tại.";
            alertBox.classList.remove("d-none");
            return;
        }

        button.disabled = true;
        button.textContent = "Đang cập nhật...";

        try {
            await apiRequest("/users/change-password", {
                method: "PUT",
                body: JSON.stringify({
                    mat_khau_cu: currentPassword,
                    mat_khau_moi: newPassword,
                    mat_khau_moi_confirmation: confirmation,
                }),
            });

            const user = getCurrentUser() || {};
            user.phai_doimatkhau = false;
            localStorage.setItem("current_user", JSON.stringify(user));
            window.location.href = "/dashboard";
        } catch (error) {
            alertBox.textContent = error.message || "Không thể đổi mật khẩu.";
            alertBox.classList.remove("d-none");
        } finally {
            button.disabled = false;
            button.textContent = "Xác nhận đổi mật khẩu";
        }
    });
});
