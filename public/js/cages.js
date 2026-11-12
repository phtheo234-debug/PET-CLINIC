let cagesList = [];

// Tạo chuỗi ngày giờ hiện tại chuẩn định dạng Laravel/MySQL (YYYY-MM-DD HH:mm:ss)
function getCurrentDateTime() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return (
        `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ` +
        `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`
    );
}

function extractList(res) {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
    return [];
}

document.addEventListener("DOMContentLoaded", async () => {
    await initPage();
    const currentUser = getCurrentUser();
    const isAdmin = currentUser && currentUser.vaitro === "QUAN_TRI";
    if (isAdmin) {
        document.getElementById("adminCageToolbar").classList.remove("d-none");
    }

    document.getElementById("cageForm").addEventListener("submit", saveCage);
    await Promise.all([loadCages(), loadPetsForSelect()]);

    document
        .getElementById("assignCageForm")
        .addEventListener("submit", async (e) => {
            e.preventDefault();
            const cageId = Number(
                document.getElementById("selected_cage_id").value,
            );
            const petId = Number(
                document.getElementById("assign_pet_id").value,
            );
            const note = document.getElementById("assign_note").value.trim();

            if (!cageId || !petId) {
                alert("Vui lòng chọn đầy đủ lồng và thú cưng!");
                return;
            }

            const btn = document.getElementById("btnConfirmAssign");
            btn.disabled = true;
            btn.innerHTML =
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xếp...';

            const payload = {
                cage_id: cageId,
                pet_id: petId,
                check_in: getCurrentDateTime(),
                ghi_chu: note || null,
                lydo: note || null,
            };

            try {
                try {
                    await apiRequest("/boarding/assign", {
                        method: "POST",
                        body: JSON.stringify(payload),
                    });
                } catch (err1) {
                    if (err1.message.includes("could not be found")) {
                        await apiRequest("/boarding", {
                            method: "POST",
                            body: JSON.stringify(payload),
                        });
                    } else {
                        throw err1;
                    }
                }

                e.target.reset();
                $("#assignCageModal").modal("hide");
                await Promise.all([loadCages(), loadPetsForSelect()]);
                alert("Đã xếp thú cưng vào lồng lưu trú thành công!");
            } catch (err) {
                alert("Không thể xếp lồng: " + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML =
                    '<i class="fas fa-check mr-1"></i> Xác nhận xếp lồng';
            }
        });
});

async function loadCages() {
    try {
        let res;
        try {
            res = await apiRequest("/boarding/cages");
        } catch (e) {
            res = await apiRequest("/boarding");
        }
        cagesList = extractList(res);
        renderCagesGrid();
    } catch (err) {
        document.getElementById("cagesGrid").innerHTML =
            `<div class="col-12"><div class="alert alert-danger">${err.message}</div></div>`;
    }
}

async function loadPetsForSelect() {
    try {
        const res = await apiRequest("/pets");
        const pets = extractList(res);
        const select = document.getElementById("assign_pet_id");

        if (!pets.length) {
            select.innerHTML =
                '<option value="">-- Chưa có thú cưng trong hệ thống --</option>';
            return;
        }

        select.innerHTML =
            '<option value="">-- Chọn thú cưng --</option>' +
            pets
                .map((p) => {
                    const id = p.id ?? p.pet_id;
                    const code = p.mathucung || `PET-${id}`;
                    const name = p.name || p.ten_thucung || "";
                    const species = p.species || p.loai || "";
                    const status =
                        p.trangthai === "NOI_TRU" ? " [Đang nội trú]" : "";
                    return `<option value="${id}">${code} - ${name} (${species})${status}</option>`;
                })
                .join("");
    } catch (err) {
        console.error("Lỗi tải danh sách thú cưng:", err);
    }
}

function renderCagesGrid() {
    const typeLabel = {
        THUONG: "Lồng Thường",
        CAP_CUU_ICU: "Hậu phẫu / ICU",
        CACH_LY: "Cách ly truyền nhiễm",
    };
    const statusMap = {
        CON_TRONG: { text: "Còn trống", cls: "success" },
        DA_CO_THU: { text: "Đang có thú", cls: "primary" },
        DANG_VE_SINH: { text: "Đang vệ sinh", cls: "warning" },
        BAO_TRI: { text: "Đang bảo trì", cls: "secondary" },
    };

    const grid = document.getElementById("cagesGrid");
    if (!cagesList.length) {
        grid.innerHTML =
            '<div class="col-12 text-muted">Chưa có dữ liệu lồng lưu trú.</div>';
        return;
    }

    grid.innerHTML = cagesList
        .map((c) => {
            const cageId = c.cage_id ?? c.id;
            const cageCode = c.malong || `L-${cageId}`;
            const cageName = c.ten_long || c.tenlong || "";
            const st = statusMap[c.trangthai] || {
                text: c.trangthai,
                cls: "info",
            };
            const currentCount = Number(c.soluonghientai ?? 0);
            const maxCapacity = Number(c.succhua ?? 1);
            const isMaintenance = ["DANG_VE_SINH", "BAO_TRI"].includes(c.trangthai);
            const isFull = currentCount >= maxCapacity && !isMaintenance;
            const displayStatus = isMaintenance
                ? st.text
                : (isFull ? "Đã đủ sức chứa" : st.text);
            const displayClass = isFull ? "warning" : st.cls;

            // Không cho xếp thêm nếu lồng đầy, đang vệ sinh hoặc bảo trì.
            const isUnavailable = isFull || isMaintenance;

            return `
            <div class="col-md-4 mb-3">
                <div class="card cage-card border-${displayClass}">
                    <div class="card-header bg-${displayClass} text-white d-flex justify-content-between">
                        <strong>${cageCode} - ${cageName}</strong>
                        <span>${displayStatus}</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Phân loại:</strong> ${typeLabel[c.loailong] || c.loailong}</p>
                        <p class="mb-3"><strong>Sức chứa (BR-03):</strong> ${currentCount} / ${maxCapacity} thú cưng</p>
                        <button type="button" class="btn btn-sm btn-primary" ${isUnavailable ? "disabled" : ""}
                            onclick="openAssignModal(${cageId}, '${cageCode}')">
                            <i class="fas fa-plus-circle mr-1"></i> Xếp thú vào lồng
                        </button>
                        ${isAdmin ? `
                            <hr>
                            <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editCage(${cageId})">
                                <i class="fas fa-edit mr-1"></i> Sửa
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCage(${cageId})">
                                <i class="fas fa-trash mr-1"></i> Xóa
                            </button>
                        ` : ""}
                    </div>
                </div>
            </div>
        `;
        })
        .join("");
}

function openCageForm(cage = null) {
    if (!isAdminCageUser()) return;

    document.getElementById("cageForm").reset();
    document.getElementById("cage_form_id").value = cage ? cage.cage_id : "";
    document.getElementById("cageFormTitle").innerText = cage ? "Cập nhật lồng lưu trú" : "Thêm lồng lưu trú";
    document.getElementById("cage_malong").value = cage ? cage.malong : "";
    document.getElementById("cage_ten_long").value = cage ? cage.ten_long : "";
    document.getElementById("cage_loailong").value = cage ? cage.loailong : "THUONG";
    document.getElementById("cage_succhua").value = cage ? cage.succhua : 1;
    document.getElementById("cage_trangthai").value = cage ? cage.trangthai : "CON_TRONG";
    $("#cageFormModal").modal("show");
}

function isAdminCageUser() {
    const user = getCurrentUser();
    return !!user && user.vaitro === "QUAN_TRI";
}

function editCage(cageId) {
    const cage = cagesList.find((item) => Number(item.cage_id) === Number(cageId));
    if (!cage) {
        alert("Không tìm thấy thông tin lồng.");
        return;
    }
    openCageForm(cage);
}

async function saveCage(event) {
    event.preventDefault();
    if (!isAdminCageUser()) {
        alert("Chỉ quản trị viên được quản lý danh mục lồng.");
        return;
    }

    const id = document.getElementById("cage_form_id").value;
    const payload = {
        malong: document.getElementById("cage_malong").value.trim(),
        ten_long: document.getElementById("cage_ten_long").value.trim(),
        loailong: document.getElementById("cage_loailong").value,
        succhua: Number(document.getElementById("cage_succhua").value),
        trangthai: document.getElementById("cage_trangthai").value,
    };
    const button = document.getElementById("btnSaveCage");
    button.disabled = true;
    button.innerText = "Đang lưu...";

    try {
        await apiRequest(id ? `/admin/cages/${id}` : "/admin/cages", {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload),
        });
        $("#cageFormModal").modal("hide");
        await loadCages();
        alert(id ? "Đã cập nhật lồng lưu trú." : "Đã thêm lồng lưu trú.");
    } catch (error) {
        alert("Không thể lưu lồng: " + error.message);
    } finally {
        button.disabled = false;
        button.innerText = "Lưu lồng";
    }
}

async function deleteCage(cageId) {
    if (!isAdminCageUser()) return;
    const cage = cagesList.find((item) => Number(item.cage_id) === Number(cageId));
    if (!cage) return;

    if (!confirm(`Bạn có chắc muốn xóa lồng ${cage.malong} - ${cage.ten_long}?`)) return;

    try {
        await apiRequest(`/admin/cages/${cageId}`, { method: "DELETE" });
        await loadCages();
        alert("Đã xóa lồng lưu trú.");
    } catch (error) {
        alert("Không thể xóa lồng: " + error.message);
    }
}

function openAssignModal(cageId, cageCode) {
    document.getElementById("selected_cage_id").value = cageId;
    document.getElementById("modalCageCode").innerText = cageCode;
    $("#assignCageModal").modal("show");
}
