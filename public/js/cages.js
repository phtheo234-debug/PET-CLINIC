let cagesList = [];
let boardingRequests = [];
let isAdminCage = false;
let canConfirmBoarding = false;
let manualBoardingRequestId = null;

function getCurrentRole() {
    const user = getCurrentUser();
    return String(user?.vaitro || "")
        .trim()
        .toUpperCase();
}

function isAdminCageUser() {
    return getCurrentRole() === "QUAN_TRI";
}

function extractList(res) {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
    return [];
}

document.addEventListener("DOMContentLoaded", async () => {
    const role = getCurrentRole();

    isAdminCage = role === "QUAN_TRI";
    canConfirmBoarding = ["QUAN_TRI", "NHAN_VIEN"].includes(role);

    // Chỉ quản trị viên được thấy nút thêm lồng
    const toolbar = document.getElementById("adminCageToolbar");

    if (toolbar) {
        toolbar.classList.toggle("d-none", !isAdminCage);
    }

    if (isAdminCage) {
        document
            .getElementById("cageForm")
            ?.addEventListener("submit", saveCage);
    }

    document.getElementById("btnConfirmManualCage")?.addEventListener("click", submitManualCageAssignment);

    await initPage();
    await Promise.all([loadCages(), loadBoardingRequests()]);

    window.setInterval(loadBoardingRequests, 30000);
});

async function loadCages() {
    const grid = document.getElementById("cagesGrid");

    try {
        const res = await apiRequest("/boarding/cages");
        cagesList = extractList(res);
        renderCagesGrid();
    } catch (err) {
        grid.innerHTML = `<div class="col-12"><div class="alert alert-danger">${err.message}</div></div>`;
    }
}

async function loadBoardingRequests() {
    const tbody = document.getElementById("boardingRequestsBody");

    try {
        const res = await apiRequest("/boarding/requests");
        boardingRequests = extractList(res);
        renderBoardingRequests();
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger text-center">${err.message}</td></tr>`;
    }
}

function renderBoardingRequests() {
    const tbody = document.getElementById("boardingRequestsBody");

    if (!boardingRequests.length) {
        tbody.innerHTML =
            '<tr><td colspan="5" class="text-center text-muted">Hiện không có yêu cầu lưu trú chờ xác nhận.</td></tr>';
        return;
    }

    const typeLabels = {
        THUONG: "Lồng thường",
        CAP_CUU_ICU: "Hậu phẫu / ICU",
        CACH_LY: "Lồng cách ly",
    };

    tbody.innerHTML = boardingRequests
        .map((item) => {
            const cage = item.recommended_cage;
            const petName = item.ten_thucung || "Chưa có tên";
            const petCode = item.mathucung || `PET-${item.pet_id}`;
            const typeLabel =
                typeLabels[item.loai_long_yeu_cau] || item.loai_long_yeu_cau;
            const isolationLabel =
                Number(item.yeu_cau_cach_ly) === 1 ? " · Bắt buộc cách ly" : "";
            const cageLabel = cage
                ? `<strong>${cage.malong}</strong> - ${cage.ten_long}<br><small class="text-muted">${Number(cage.soluonghientai || 0)}/${Number(cage.succhua || 0)} chỗ · tối đa ${Number(cage.tai_trong_toi_da || 0)} kg · loài ${cage.loai_ap_dung || "?"}</small>`
                : '<span class="text-danger">Chưa có lồng phù hợp</span><br><small class="text-muted">Yêu cầu vẫn được giữ chờ, hệ thống sẽ tìm lại khi làm mới.</small>';
            const action = canConfirmBoarding
                ? `<div class="d-flex flex-column" style="gap:6px;">${cage ? `<button type="button" class="btn btn-sm btn-success" onclick="confirmBoardingRequest(${Number(item.request_id)})"><i class="fas fa-check mr-1"></i> Xác nhận xếp</button>` : '<span class="small text-danger">Chưa có đề xuất phù hợp</span>'}<button type="button" class="btn btn-sm btn-outline-primary" onclick="manualAssignBoardingRequest(${Number(item.request_id)})"><i class="fas fa-hand-pointer mr-1"></i> Tự xếp lồng</button></div>`
                : '<span class="text-muted small">Chỉ xem</span>';

            return `
            <tr>
                <td><strong>${petCode}</strong><br>${petName}<br><small class="text-muted">${item.loai || ""} ${item.giong || ""}</small></td>
                <td>${item.chandoan || "Chưa có chẩn đoán"}<br><small class="text-muted">${item.trieuchung || ""}</small></td>
                <td>${typeLabel}${isolationLabel}<br><small class="text-muted">${item.ghi_chu || ""}</small></td>
                <td>${cageLabel}</td>
                <td>${action}</td>
            </tr>
        `;
        })
        .join("");
}

async function confirmBoardingRequest(requestId) {
    if (!canConfirmBoarding) {
        alert("Chỉ nhân viên hoặc quản trị viên được xác nhận xếp lồng.");
        return;
    }

    const requestItem = boardingRequests.find(
        (item) => Number(item.request_id) === Number(requestId),
    );
    if (!requestItem || !requestItem.recommended_cage) {
        alert(
            "Hiện chưa có lồng phù hợp để xác nhận. Vui lòng làm mới danh sách.",
        );
        return;
    }

    const cage = requestItem.recommended_cage;
    const petName =
        requestItem.ten_thucung || `thú cưng #${requestItem.pet_id}`;

    if (
        !confirm(
            `Xác nhận xếp ${petName} vào lồng ${cage.malong} - ${cage.ten_long}? Hệ thống sẽ kiểm tra lại loại lồng, loài, cân nặng tối đa và sức chứa trước khi lưu.`,
        )
    ) {
        return;
    }

    try {
        await apiRequest(`/boarding/requests/${requestId}/confirm`, {
            method: "POST",
            body: JSON.stringify({}),
        });

        await Promise.all([loadCages(), loadBoardingRequests()]);
        alert("Đã xác nhận xếp lồng thành công.");
    } catch (err) {
        alert("Không thể xác nhận xếp lồng: " + err.message);
        await Promise.all([loadCages(), loadBoardingRequests()]);
    }
}

function renderCagesGrid() {
    const typeLabels = {
        THUONG: "Lồng thường",
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
        .map((cage) => {
            const cageId = Number(cage.cage_id);
            const currentCount = Number(cage.soluonghientai || 0);
            const maxCapacity = Number(cage.succhua || 1);
            const status = statusMap[cage.trangthai] || {
                text: cage.trangthai || "Không rõ",
                cls: "info",
            };
            const isMaintenance = ["DANG_VE_SINH", "BAO_TRI"].includes(
                cage.trangthai,
            );
            const isFull = currentCount >= maxCapacity && !isMaintenance;
            const statusText = isFull ? "Đã đủ sức chứa" : status.text;
            const statusClass = isFull ? "warning" : status.cls;
            const occupants = Array.isArray(cage.pets) ? cage.pets : [];
            const occupantsHtml = occupants.length
                ? occupants
                      .map(
                          (pet) =>
                              `<div><i class="fas fa-paw text-muted mr-1"></i>${pet.mathucung || ""} - ${pet.ten_thucung || ""}</div>`,
                      )
                      .join("")
                : '<span class="text-muted">Chưa có thú cưng</span>';

            const adminActions = isAdminCage
                ? `
                <hr>
                <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editCage(${cageId})">
                    <i class="fas fa-edit mr-1"></i> Sửa
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCage(${cageId})">
                    <i class="fas fa-trash mr-1"></i> Xóa
                </button>
            `
                : "";

            return `
            <div class="col-md-4 mb-3">
                <div class="card cage-card border-${statusClass} h-100">
                    <div class="card-header bg-${statusClass} text-white d-flex justify-content-between">
                        <strong>${cage.malong || `L-${cageId}`} - ${cage.ten_long || ""}</strong>
                        <span>${statusText}</span>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Phân loại:</strong> ${typeLabels[cage.loailong] || cage.loailong}</p>
                        <p class="mb-1"><strong>Sức chứa:</strong> ${currentCount}/${maxCapacity} thú cưng</p>
                        <p class="mb-2"><strong>Giới hạn:</strong> ${Number(cage.tai_trong_toi_da || 0)} kg · Loài: ${cage.loai_ap_dung || "Chưa cấu hình"}</p>
                        <div class="small"><strong>Thú cưng đang lưu trú:</strong>${occupantsHtml}</div>
                        ${adminActions}
                    </div>
                </div>
            </div>
        `;
        })
        .join("");
}

function openCageForm(cage = null) {
    if (!isAdminCageUser()) {
        alert("Chỉ quản trị viên được quản lý danh mục lồng.");
        return;
    }

    const form = document.getElementById("cageForm");
    form.reset();

    document.getElementById("cage_form_id").value = cage ? cage.cage_id : "";
    document.getElementById("cageFormTitle").innerText = cage
        ? "Cập nhật lồng lưu trú"
        : "Thêm lồng lưu trú";
    document.getElementById("cage_malong").value = cage
        ? cage.malong
        : "Tự động tạo khi lưu";
    document.getElementById("cage_ten_long").value = cage ? cage.ten_long : "";
    document.getElementById("cage_loailong").value = cage
        ? cage.loailong
        : "THUONG";
    document.getElementById("cage_succhua").value = cage ? cage.succhua : 1;
    document.getElementById("cage_tai_trong").value = cage ? cage.tai_trong_toi_da : "";
    document.getElementById("cage_loai_ap_dung").value = cage ? cage.loai_ap_dung : "Chó";
    document.getElementById("cage_trangthai").value = cage
        ? cage.trangthai
        : "CON_TRONG";

    $("#cageFormModal").modal("show");
}

function editCage(cageId) {
    if (!isAdminCageUser()) return;

    const cage = cagesList.find(
        (item) => Number(item.cage_id) === Number(cageId),
    );
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
        ten_long: document.getElementById("cage_ten_long").value.trim(),
        loailong: document.getElementById("cage_loailong").value,
        succhua: Number(document.getElementById("cage_succhua").value),
        tai_trong_toi_da: Number(document.getElementById("cage_tai_trong").value),
        loai_ap_dung: document.getElementById("cage_loai_ap_dung").value,
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
    } catch (err) {
        alert("Không thể lưu lồng: " + err.message);
    } finally {
        button.disabled = false;
        button.innerText = "Lưu lồng";
    }
}

async function deleteCage(cageId) {
    if (!isAdminCageUser()) return;

    const cage = cagesList.find(
        (item) => Number(item.cage_id) === Number(cageId),
    );
    if (!cage) return;

    if (
        !confirm(`Bạn có chắc muốn xóa lồng ${cage.malong} - ${cage.ten_long}?`)
    ) {
        return;
    }

    try {
        await apiRequest(`/admin/cages/${cageId}`, { method: "DELETE" });
        await loadCages();
        alert("Đã xóa lồng lưu trú.");
    } catch (err) {
        alert("Không thể xóa lồng: " + err.message);
    }
}


/**
 * Cho nhân viên/quản trị tự chọn lồng khác khi đề xuất tự động chưa phù hợp.
 * Danh sách trên giao diện chỉ để hỗ trợ chọn; backend vẫn xác thực lại tuyệt đối.
 */
function manualAssignBoardingRequest(requestId) {
    if (!canConfirmBoarding) {
        alert("Chỉ nhân viên hoặc quản trị viên được tự xếp lồng.");
        return;
    }

    const requestItem = boardingRequests.find(
        (item) => Number(item.request_id) === Number(requestId),
    );
    if (!requestItem) {
        alert("Không tìm thấy yêu cầu lưu trú. Vui lòng làm mới danh sách.");
        return;
    }

    const weight = Number(requestItem.cannangkhi_kham || 0);
    const petType = String(requestItem.loai || "").trim().toLowerCase();
    const options = cagesList.filter((cage) => {
        if (cage.loailong !== requestItem.loai_long_yeu_cau) return false;
        if (["DANG_VE_SINH", "BAO_TRI"].includes(cage.trangthai)) return false;
        if (Number(cage.soluonghientai || 0) >= Number(cage.succhua || 0)) return false;
        if (String(cage.loai_ap_dung || "").trim().toLowerCase() !== petType) return false;
        if (weight > 0 && Number(cage.tai_trong_toi_da || 0) < weight) return false;
        const occupants = Array.isArray(cage.pets) ? cage.pets : [];
        if (Number(requestItem.yeu_cau_cach_ly) === 1 && occupants.length > 0) return false;
        if (occupants.some((pet) => String(pet.loai || "").trim().toLowerCase() !== petType)) return false;
        return true;
    });

    if (!options.length) {
        alert("Không có lồng nào đáp ứng điều kiện. Hãy kiểm tra loại lồng, loài, cân nặng và sức chứa.");
        return;
    }

    const select = document.getElementById("manualCageSelect");
    const petInfo = document.getElementById("manualCagePetInfo");
    if (!select || !petInfo || !window.jQuery || !jQuery.fn.modal) {
        alert("Không tải được cửa sổ chọn lồng. Vui lòng tải lại trang (Ctrl + F5).");
        return;
    }

    manualBoardingRequestId = Number(requestId);
    petInfo.textContent = `Thú cưng: ${requestItem.mathucung || requestItem.pet_id} - ${requestItem.ten_thucung || "Chưa có tên"} · Cân nặng: ${weight || "Chưa ghi nhận"} kg`;
    select.innerHTML = options.map((cage) => {
        const label = `${cage.malong} - ${cage.ten_long} | ${cage.soluonghientai}/${cage.succhua} chỗ | tối đa ${cage.tai_trong_toi_da} kg | ${cage.loai_ap_dung}`;
        return `<option value="${Number(cage.cage_id)}">${label}</option>`;
    }).join("");

    $("#manualCageModal").modal("show");
}

async function submitManualCageAssignment() {
    if (!canConfirmBoarding || !manualBoardingRequestId) return;

    const button = document.getElementById("btnConfirmManualCage");
    const cageId = Number(document.getElementById("manualCageSelect")?.value);
    if (!cageId) {
        alert("Vui lòng chọn lồng.");
        return;
    }

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang kiểm tra...';

    try {
        const response = await apiRequest(`/boarding/requests/${manualBoardingRequestId}/confirm`, {
            method: "POST",
            body: JSON.stringify({ cage_id: cageId }),
        });
        $("#manualCageModal").modal("hide");
        manualBoardingRequestId = null;
        await Promise.all([loadCages(), loadBoardingRequests()]);
        alert(response?.message || "Đã tự xếp lồng thành công.");
    } catch (error) {
        alert("Không thể tự xếp lồng: " + error.message);
        await Promise.all([loadCages(), loadBoardingRequests()]);
    } finally {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-check mr-1"></i> Xác nhận xếp lồng';
    }
}
