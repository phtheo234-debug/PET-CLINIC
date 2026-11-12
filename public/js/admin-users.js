document.addEventListener("DOMContentLoaded", async () => {
    const user = getCurrentUser();
    if (!user || user.vaitro !== "QUAN_TRI") {
        window.location.href = "/dashboard";
        return;
    }
    await initPage();
    const table = document.getElementById("usersTable");
    const alertBox = document.getElementById("usersAlert");
    const notify = (message, ok = false) => {
        alertBox.textContent = message;
        alertBox.className = "alert " + (ok ? "alert-success" : "alert-danger");
    };
    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, ch => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[ch]);
    const statusLabels = {
        HOAT_DONG: "Hoạt động",
        KHOA: "Đã khóa",
        TAM_NGUNG: "Tạm ngừng"
    };

    async function loadUsers() {
        try {
            const result = await apiRequest("/admin/users");
            const users = result.data || [];
            table.innerHTML = users.length ? users.map(item => {
                const active = item.trangthai === "HOAT_DONG";
                const isAdmin = item.vaitro === "QUAN_TRI";
                const role = { QUAN_TRI: "Quản trị viên", BAC_SI: "Bác sĩ", NHAN_VIEN: "Nhân viên" }[item.vaitro] || item.vaitro;
                const actions = isAdmin
                    ? '<span class="text-muted">Tài khoản quản trị hệ thống</span>'
                    : `<button class="btn btn-sm btn-outline-warning mb-1" data-action="reset" data-id="${item.user_id}">Cấp lại MK</button>
                       <button class="btn btn-sm ${active ? "btn-outline-danger" : "btn-outline-success"} mb-1" data-action="status" data-id="${item.user_id}" data-status="${active ? "KHOA" : "HOAT_DONG"}">${active ? "Khóa" : "Mở khóa"}</button>`;
                return `<tr>
                    <td>${item.user_id}</td><td>${escapeHtml(item.username)}</td>
                    <td>${escapeHtml(item.ho_ten)}</td><td>${escapeHtml(item.email)}</td>
                    <td>${escapeHtml(role)}</td><td>${escapeHtml(statusLabels[item.trangthai] || item.trangthai)}</td>
                    <td class="text-nowrap">${actions}</td></tr>`;
            }).join("") : '<tr><td colspan="7" class="text-center">Chưa có tài khoản.</td></tr>';
        } catch (error) {
            notify(error.message);
            table.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Không tải được dữ liệu.</td></tr>';
        }
    }

    table.addEventListener("click", async (event) => {
        const button = event.target.closest("button[data-action]");
        if (!button) return;
        const id = button.dataset.id;
        try {
            if (button.dataset.action === "reset") {
                document.getElementById("resetUserId").value = id;
                document.getElementById("temporaryPassword").value = "";
                document.getElementById("resetPasswordAlert").classList.add("d-none");
                $("#resetPasswordModal").modal("show");
            } else {
                const status = button.dataset.status;
                if (!confirm(status === "KHOA" ? "Bạn chắc chắn muốn khóa tài khoản này?" : "Mở khóa tài khoản này?")) return;
                await apiRequest(`/admin/users/${id}/status`, {
                    method: "PUT", body: JSON.stringify({ trangthai: status })
                });
                notify("Cập nhật trạng thái tài khoản thành công.", true);
                await loadUsers();
            }
        } catch (error) { notify(error.message); }
    });

    document.getElementById("resetPasswordForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        const id = document.getElementById("resetUserId").value;
        const temporaryPassword = document.getElementById("temporaryPassword").value;
        const resetAlert = document.getElementById("resetPasswordAlert");
        if (temporaryPassword.length < 6) {
            resetAlert.textContent = "Mật khẩu tạm phải có ít nhất 6 ký tự.";
            resetAlert.className = "alert alert-danger";
            return;
        }
        try {
            await apiRequest(`/admin/users/${id}/reset-password`, {
                method: "PUT", body: JSON.stringify({ mat_khau_tam: temporaryPassword })
            });
            $("#resetPasswordModal").modal("hide");
            notify("Đã cấp lại mật khẩu tạm. Người dùng sẽ phải đổi mật khẩu khi đăng nhập.", true);
            await loadUsers();
        } catch (error) {
            resetAlert.textContent = error.message;
            resetAlert.className = "alert alert-danger";
        }
    });

    document.getElementById("createUserForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        try {
            await apiRequest("/admin/users", {
                method: "POST",
                body: JSON.stringify({
                    username: document.getElementById("newUsername").value.trim(),
                    ho_ten: document.getElementById("newName").value.trim(),
                    email: document.getElementById("newEmail").value.trim(),
                    phone: document.getElementById("newPhone").value.trim(),
                    vaitro: document.getElementById("newRole").value,
                    mat_khau_tam: document.getElementById("newTempPassword").value
                })
            });
            $("#createUserModal").modal("hide");
            event.target.reset();
            notify("Tạo tài khoản thành công.", true);
            await loadUsers();
        } catch (error) { notify(error.message); }
    });
    await loadUsers();
});
