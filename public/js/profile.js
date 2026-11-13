document.addEventListener("DOMContentLoaded", async () => {
    await initPage();
    const alert = (id, message, ok = false) => {
        const el = document.getElementById(id);
        el.textContent = message;
        el.className = "alert " + (ok ? "alert-success" : "alert-danger");
    };
    try {
        const result = await apiRequest("/users/me");
        const user = result.data || {};
        document.getElementById("profileUsername").value = user.username || "";
        document.getElementById("profileName").value = user.ho_ten || "";
        document.getElementById("profileEmail").value = user.email || "";
        document.getElementById("profilePhone").value = user.phone || "";
    } catch (e) { alert("profileAlert", e.message); }

    document.getElementById("profileForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        try {
            const result = await apiRequest("/users/profile", {
                method: "PUT",
                body: JSON.stringify({
                    ho_ten: document.getElementById("profileName").value.trim(),
                    email: document.getElementById("profileEmail").value.trim(),
                    phone: document.getElementById("profilePhone").value.trim()
                })
            });
            localStorage.setItem("current_user", JSON.stringify(result.data));
            const name = document.getElementById("navUserName");
            if (name) name.textContent = result.data.ho_ten;
            alert("profileAlert", result.message, true);
        } catch (error) { alert("profileAlert", error.message); }
    });

    document.getElementById("profilePasswordForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const password = document.getElementById("profileNewPassword").value;
        const confirmation = document.getElementById("profileConfirmPassword").value;
        if (password !== confirmation) return alert("passwordAlert", "Mật khẩu nhập lại không khớp.");
        try {
            const result = await apiRequest("/users/change-password", {
                method: "PUT",
                body: JSON.stringify({
                    mat_khau_cu: document.getElementById("oldPassword").value,
                    mat_khau_moi: password,
                    mat_khau_moi_confirmation: confirmation
                })
            });
            alert("passwordAlert", result.message, true);
            e.target.reset();
        } catch (error) { alert("passwordAlert", error.message); }
    });
});