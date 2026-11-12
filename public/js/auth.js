document.addEventListener("DOMContentLoaded", () => {
    if (getToken()) {
        const user = getCurrentUser();
        window.location.href = user && user.phai_doimatkhau
            ? "/change-password"
            : "/dashboard";
        return;
    }

    const loginForm = document.getElementById("loginForm");
    const alertBox = document.getElementById("loginAlert");
    const btnSubmit = document.getElementById("btnSubmitLogin");

    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        alertBox.classList.add("d-none");
        btnSubmit.disabled = true;
        btnSubmit.innerHTML =
            '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xác thực...';

        const username = document.getElementById("username").value.trim();
        const mat_khau = document.getElementById("mat_khau").value;

        try {
            const res = await fetch(API_BASE_URL + "/auth/login", {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ username, mat_khau }),
            });

            const result = await res.json();
            if (!res.ok) {
                throw new Error(result.message || "Sai tên đăng nhập hoặc mật khẩu");
            }

            const payload = result.data || result;
            localStorage.setItem("access_token", payload.token);
            localStorage.setItem("current_user", JSON.stringify({
                user_id: payload.user_id,
                username: payload.username,
                ho_ten: payload.ho_ten,
                vaitro: payload.vaitro,
                phai_doimatkhau: payload.phai_doimatkhau,
            }));

            window.location.href = payload.phai_doimatkhau
                ? "/change-password"
                : "/dashboard";
        } catch (err) {
            alertBox.textContent = err.message;
            alertBox.classList.remove("d-none");
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML =
                '<i class="fas fa-sign-in-alt mr-1"></i> Đăng nhập hệ thống';
        }
    });
});
