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
                    : `<button class="btn btn-sm btn-outline-primary mb-1" data-action="edit" data-id="${item.user_id}" data-user="${encodeURIComponent(JSON.stringify(item))}">Sửa</button>                        <button class="btn btn-sm btn-outline-danger mb-1" data-action="delete" data-id="${item.user_id}">Xóa</button>                        <button class="btn btn-sm btn-outline-warning mb-1" data-action="reset" data-id="${item.user_id}">Cấp lại MK</button>
                       <div class="input-group input-group-sm mt-1" style="min-width: 190px;">
                           <select class="form-control" id="status-${item.user_id}" aria-label="Trạng thái tài khoản">
                               <option value="HOAT_DONG" ${item.trangthai === "HOAT_DONG" ? "selected" : ""}>Hoạt động</option>
                               <option value="TAM_NGUNG" ${item.trangthai === "TAM_NGUNG" ? "selected" : ""}>Tạm ngừng hoạt động</option>
                               <option value="KHOA" ${item.trangthai === "KHOA" ? "selected" : ""}>Đã khóa</option>
                           </select>
                           <div class="input-group-append">
                               <button class="btn btn-outline-primary" data-action="status" data-id="${item.user_id}">Lưu</button>
                           </div>
                       </div>`;
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
            if (button.dataset.action === "edit") {
                const item = JSON.parse(decodeURIComponent(button.dataset.user));
                document.getElementById("editUserId").value = item.user_id;
                document.getElementById("editUsername").value = item.username || "";
                document.getElementById("editName").value = item.ho_ten || "";
                document.getElementById("editEmail").value = item.email || "";
                document.getElementById("editPhone").value = item.phone || "";
                document.getElementById("editRole").value = item.vaitro || "NHAN_VIEN";
                $("#editUserModal").modal("show");
            } else if (button.dataset.action === "delete") {
                const username = button.closest("tr")?.children[1]?.textContent || ("#" + id);
                if (!confirm("Xóa tài khoản " + username + "? Thao tác này không thể hoàn tác.")) return;
                await apiRequest("/admin/users/" + id, { method: "DELETE" });
                notify("Đã xóa tài khoản thành công.", true);
                await loadUsers();
            } else if (button.dataset.action === "reset") {
                document.getElementById("resetUserId").value = id;
                document.getElementById("temporaryPassword").value = "";
                document.getElementById("resetPasswordAlert").classList.add("d-none");
                $("#resetPasswordModal").modal("show");
            } else {
                const status = document.getElementById(`status-${id}`).value;
                const confirmMessage = status === "KHOA"
                    ? "Bạn chắc chắn muốn khóa tài khoản này?"
                    : status === "TAM_NGUNG"
                        ? "Bạn chắc chắn muốn tạm ngừng hoạt động tài khoản này?"
                        : "Mở lại hoạt động cho tài khoản này?";
                if (!confirm(confirmMessage)) return;
                await apiRequest(`/admin/users/${id}/status`, {
                    method: "PUT", body: JSON.stringify({ trangthai: status })
                });
                notify("Cập nhật trạng thái tài khoản thành công.", true);
                await loadUsers();
            }
        } catch (error) { notify(error.message); }
    });

    document.getElementById("editUserForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        const id = document.getElementById("editUserId").value;
        try {
            await apiRequest("/admin/users/" + id, {
                method: "PUT",
                body: JSON.stringify({
                    username: document.getElementById("editUsername").value.trim(),
                    ho_ten: document.getElementById("editName").value.trim(),
                    email: document.getElementById("editEmail").value.trim(),
                    phone: document.getElementById("editPhone").value.trim(),
                    vaitro: document.getElementById("editRole").value
                })
            });
            $("#editUserModal").modal("hide");
            notify("Cập nhật tài khoản thành công.", true);
            await loadUsers();
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
