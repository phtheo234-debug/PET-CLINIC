let cagesList = [];
let cageWaitingList = [];
let isAdminCage = false;
let canManageBoarding = false;

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
    isAdminCage = !!currentUser && currentUser.vaitro === "QUAN_TRI";
    canManageBoarding = !!currentUser && ["QUAN_TRI", "NHAN_VIEN"].includes(currentUser.vaitro);

    if (isAdminCage) {
        document.getElementById("adminCageToolbar")?.classList.remove("d-none");
    }

    document.getElementById("cageForm")?.addEventListener("submit", saveCage);

    if (canManageBoarding) {
        await loadPetsForSelect();
        document.getElementById("assignCageForm")?.addEventListener("submit", submitAssignCage);
    }

    document.getElementById("cageSearch")?.addEventListener("input", renderCagesGrid);
    document.getElementById("cageStatusFilter")?.addEventListener("change", renderCagesGrid);
    document.getElementById("cageTypeFilter")?.addEventListener("change", renderCagesGrid);

    await Promise.all([loadCages(), loadWaitingList()]);
    // Cập nhật danh sách chờ định kỳ để quản trị viên sớm nhận biết thiếu lồng.
    window.setInterval(loadWaitingList, 30000);
});

async function submitAssignCage(e) {
    e.preventDefault();
    if (!canManageBoarding) {
        alert("Bạn không có quyền xếp thú cưng vào lồng.");
        return;
    }

    const cageId = Number(document.getElementById("selected_cage_id").value);
    const petId = Number(document.getElementById("assign_pet_id").value);
    const note = document.getElementById("assign_note").value.trim();

    if (!cageId || !petId) {
        alert("Vui lòng chọn đầy đủ lồng và thú cưng!");
        return;
    }

    const btn = document.getElementById("btnConfirmAssign");
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xếp...';

    try {
        await apiRequest("/boarding/assign", {
            method: "POST",
            body: JSON.stringify({
                cage_id: cageId,
                pet_id: petId,
                check_in: getCurrentDateTime(),
                isolation_required: document.getElementById("assign_isolation_required")?.checked || false,
                ghi_chu: note || null,
                lydo: note || null,
            }),
        });

        e.target.reset();
        $("#assignCageModal").modal("hide");
        await Promise.all([loadCages(), loadPetsForSelect()]);
        alert("Đã xếp thú cưng vào lồng lưu trú thành công!");
    } catch (err) {
        alert("Không thể xếp lồng: " + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check mr-1"></i> Xác nhận xếp lồng';
    }
}

async function loadCages() {
    try {
        const res = await apiRequest("/boarding/cages");
        cagesList = extractList(res);
        renderCagesGrid();
    } catch (err) {
        document.getElementById("cagesGrid").innerHTML =
            `<div class="col-12"><div class="alert alert-danger">${err.message}</div></div>`;
    }
}

async function loadWaitingList() {
    const body = document.getElementById("cageWaitingBody");
    if (!body) return;
    try {
        const res = await apiRequest("/boarding/waiting-list");
        cageWaitingList = extractList(res);
        renderWaitingList();
    } catch (err) {
        body.innerHTML = `<tr><td colspan="6" class="text-danger text-center">${err.message}</td></tr>`;
    }
}

function renderWaitingList() {
    const body = document.getElementById("cageWaitingBody");
    const count = document.getElementById("waitingCount");
    const notice = document.getElementById("waitingAdminNotice");
    if (!body) return;

    if (count) count.textContent = cageWaitingList.length;
    if (notice && isAdminCage) notice.classList.toggle("d-none", cageWaitingList.length === 0);

    if (!cageWaitingList.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Hiện không có thú cưng chờ xếp lồng.</td></tr>';
        return;
    }

    body.innerHTML = cageWaitingList.map((pet) => {
        const requirement = pet.isolation_required ? "Cách ly" : (pet.treatment_type === "SURGERY" ? "Phẫu thuật" : "Nội trú");
        return `<tr>
            <td>${pet.mathucung || "PET-" + pet.pet_id}</td>
            <td>${pet.ten_thucung || ""}</td>
            <td>${pet.loai || ""}</td>
            <td>${pet.weight} kg</td>
            <td>${requirement}</td>
            <td>${pet.created_at || ""}</td>
        </tr>`;
    }).join("");
}

async function loadPetsForSelect() {
    if (!canManageBoarding) return;
    try {
        const res = await apiRequest("/pets");
        const pets = extractList(res);
        const select = document.getElementById("assign_pet_id");
        if (!select) return;

        select.innerHTML = pets.length
            ? '<option value="">-- Chọn thú cưng --</option>' + pets.map((p) => {
                const id = p.id ?? p.pet_id;
                const code = p.mathucung || `PET-${id}`;
                const name = p.name || p.ten_thucung || "";
                const species = p.species || p.loai || "";
                const status = p.trangthai === "NOI_TRU" ? " [Đang nội trú]" : "";
                return `<option value="${id}">${code} - ${name} (${species})${status}</option>`;
            }).join("")
            : '<option value="">-- Chưa có thú cưng trong hệ thống --</option>';
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
    const keyword = (document.getElementById("cageSearch")?.value || "").trim().toLocaleLowerCase("vi");
    const statusFilter = document.getElementById("cageStatusFilter")?.value || "";
    const typeFilter = document.getElementById("cageTypeFilter")?.value || "";

    const filteredCages = cagesList.filter((c) => {
        const petsText = (c.pets || []).map((p) => [p.mathucung, p.ten_thucung, p.loai].join(" ")).join(" ");
        const searchable = [c.malong, c.ten_long, c.loai_phu_hop, petsText].join(" ").toLocaleLowerCase("vi");
        return (!keyword || searchable.includes(keyword))
            && (!statusFilter || c.trangthai === statusFilter)
            && (!typeFilter || c.loailong === typeFilter);
    });

    if (!cagesList.length) {
        grid.innerHTML = '<div class="col-12 text-muted">Chưa có dữ liệu lồng lưu trú.</div>';
        return;
    }
    if (!filteredCages.length) {
        grid.innerHTML = '<div class="col-12 text-muted">Không tìm thấy lồng phù hợp điều kiện tìm kiếm.</div>';
        return;
    }

    grid.innerHTML = filteredCages.map((c) => {
        const cageId = c.cage_id ?? c.id;
        const cageCode = c.malong || `L-${cageId}`;
        const cageName = c.ten_long || c.tenlong || "";
        const st = statusMap[c.trangthai] || { text: c.trangthai, cls: "info" };
        const currentCount = Number(c.soluonghientai ?? 0);
        const maxCapacity = Number(c.succhua ?? 1);
        const effectiveCapacity = c.loailong === "CACH_LY" ? 1 : maxCapacity;
        const isMaintenance = ["DANG_VE_SINH", "BAO_TRI"].includes(c.trangthai);
        const isFull = currentCount >= effectiveCapacity && !isMaintenance;
        const displayStatus = isMaintenance ? st.text : (isFull ? "Đã đủ sức chứa" : st.text);
        const displayClass = isFull ? "warning" : st.cls;
        const isUnavailable = isFull || isMaintenance;

        return `
            <div class="col-md-4 mb-3">
                <div class="card cage-card border-${displayClass}">
                    <div class="card-header bg-${displayClass} text-white d-flex justify-content-between">
                        <strong>${cageCode} - ${cageName}</strong><span>${displayStatus}</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Phân loại:</strong> ${typeLabel[c.loailong] || c.loailong}</p>
                        <p class="mb-1"><strong>Loài phù hợp:</strong> ${c.loai_phu_hop === "CHO" ? "Chó" : c.loai_phu_hop === "MEO" ? "Mèo" : "Tất cả"}</p>
                        <p class="mb-1"><strong>Tải trọng tối đa:</strong> ${c.tai_trong_toi_da ? c.tai_trong_toi_da + " kg" : "Không giới hạn"}</p>
                        <p class="mb-3"><strong>Sức chứa:</strong> ${currentCount} / ${effectiveCapacity} thú cưng</p>
                        ${(c.pets || []).length ? '<div class="small mb-2"><strong>Đang lưu trú:</strong> ' + c.pets.map(p => (p.mathucung || "PET-" + p.pet_id) + " - " + (p.ten_thucung || "")).join(", ") + '</div>' : ""}
                        ${canManageBoarding ? `
                            <button type="button" class="btn btn-sm btn-primary" ${isUnavailable ? "disabled" : ""}
                                onclick="openAssignModal(${cageId}, '${cageCode}')">
                                <i class="fas fa-plus-circle mr-1"></i> Xếp thú vào lồng
                            </button>
                        ` : ""}
                        ${isAdminCage ? `
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
    }).join("");
}

function openCageForm(cage = null) {
    if (!isAdminCageUser()) return;
    document.getElementById("cageForm").reset();
    document.getElementById("cage_form_id").value = cage ? cage.cage_id : "";
    document.getElementById("cageFormTitle").innerText = cage ? "Cập nhật lồng lưu trú" : "Thêm lồng lưu trú";
    document.getElementById("cage_malong").value = cage ? cage.malong : "Tự động tạo khi lưu";
    document.getElementById("cage_ten_long").value = cage ? cage.ten_long : "";
    document.getElementById("cage_loailong").value = cage ? cage.loailong : "THUONG";
    document.getElementById("cage_succhua").value = cage ? cage.succhua : 1;
    document.getElementById("cage_loai_phu_hop").value = cage ? (cage.loai_phu_hop || "TAT_CA") : "TAT_CA";
    document.getElementById("cage_tai_trong_toi_da").value = cage ? (cage.tai_trong_toi_da || "") : "";
    document.getElementById("cage_trangthai").value = cage ? cage.trangthai : "CON_TRONG";
    $("#cageFormModal").modal("show");
}

function isAdminCageUser() {
    const user = getCurrentUser();
    return !!user && user.vaitro === "QUAN_TRI";
}

function editCage(cageId) {
    if (!isAdminCageUser()) return;
    const cage = cagesList.find((item) => Number(item.cage_id) === Number(cageId));
    if (!cage) return alert("Không tìm thấy thông tin lồng.");
    openCageForm(cage);
}

async function saveCage(event) {
    event.preventDefault();
    if (!isAdminCageUser()) return alert("Chỉ quản trị viên được quản lý danh mục lồng.");

    const id = document.getElementById("cage_form_id").value;
    const payload = {
        ten_long: document.getElementById("cage_ten_long").value.trim(),
        loailong: document.getElementById("cage_loailong").value,
        succhua: Number(document.getElementById("cage_succhua").value),
        loai_phu_hop: document.getElementById("cage_loai_phu_hop").value,
        tai_trong_toi_da: document.getElementById("cage_tai_trong_toi_da").value || null,
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
    if (!cage || !confirm(`Bạn có chắc muốn xóa lồng ${cage.malong} - ${cage.ten_long}?`)) return;
    try {
        await apiRequest(`/admin/cages/${cageId}`, { method: "DELETE" });
        await loadCages();
        alert("Đã xóa lồng lưu trú.");
    } catch (error) {
        alert("Không thể xóa lồng: " + error.message);
    }
}

function openAssignModal(cageId, cageCode) {
    if (!canManageBoarding) return;
    document.getElementById("selected_cage_id").value = cageId;
    document.getElementById("modalCageCode").innerText = cageCode;
    const cage = cagesList.find((item) => Number(item.cage_id) === Number(cageId));
    const isolationCheckbox = document.getElementById("assign_isolation_required");
    if (isolationCheckbox) isolationCheckbox.checked = cage?.loailong === "CACH_LY";
    $("#assignCageModal").modal("show");
}
