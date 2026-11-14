let petsList = [];

let currentUserRole = "";

function isDoctor() {
    return currentUserRole === "BAC_SI";
}

// Tự động sinh mã thú cưng hiển thị tạm trên form
function generateNextPetCode() {
    if (!petsList.length) {
        return "PET-001";
    }

    let maxNum = 0;

    petsList.forEach((p) => {
        const match = (p.mathucung || "").match(/\d+/);

        if (match) {
            const num = parseInt(match[0], 10);

            if (num > maxNum) {
                maxNum = num;
            }
        }
    });

    return "PET-" + String(maxNum + 1).padStart(3, "0");
}

// Chuẩn hóa giới tính
function normalizeGender(raw) {
    const val = (raw || "").trim().toUpperCase();

    if (val === "DUC" || val === "ĐỰC" || val === "MALE") return "DUC";
    if (val === "CAI" || val === "CÁI" || val === "FEMALE") return "CAI";

    return "CHUA_XAC_DINH";
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

    currentUserRole = String(currentUser?.vaitro || "")
        .trim()
        .toUpperCase();

    // Tải danh sách sau khi xác định vai trò để hiển thị đúng quyền cập nhật.
    await loadPets();

    const canReceivePets = ["QUAN_TRI", "NHAN_VIEN"].includes(currentUserRole);

    const addPetButton = document.getElementById("btnOpenAddPet");

    if (addPetButton && canReceivePets) {
        addPetButton.classList.remove("d-none");
    }
    $("#vaccinationScheduleModal").on("show.bs.modal", loadVaccinationSchedule);


    // Tự động điền mã thú cưng khi mở modal tiếp nhận
    $("#addPetModal").on("show.bs.modal", function () {
        const codeInput = document.getElementById("mathucung");

        if (codeInput) {
            codeInput.value = generateNextPetCode();
        }
    });

    // Tìm kiếm bằng nút Tìm hoặc phím Enter.
    const runPetSearch = () => {
        const kw = document.getElementById("searchPet").value.trim().toLocaleLowerCase("vi");

        const filtered = petsList.filter((p) => {
            const petName = (p.name || p.ten_thucung || "").toLowerCase();

            const ownerName = (
                (p.owner && (p.owner.name || p.owner.ho_ten)) ||
                p.ho_ten_chu ||
                ""
            ).toLowerCase();

            const ownerPhone = (p.owner && p.owner.phone) || p.phone || "";

            return (
                (p.mathucung && p.mathucung.toLowerCase().includes(kw)) ||
                petName.includes(kw) ||
                ownerName.includes(kw) ||
                ownerPhone.includes(kw)
            );
        });

        renderPetsTable(filtered);
    };
    document.getElementById("btnSearchPet")?.addEventListener("click", runPetSearch);
    document.getElementById("searchPet")?.addEventListener("keydown", event => { if (event.key === "Enter") runPetSearch(); });

    // Mở hộp thoại ghi chú khi yêu cầu đưa thú cưng vào danh sách chờ.
    document.getElementById("petsTableBody").addEventListener("click", event => {
        const button = event.target.closest("[data-add-medical-queue]");
        if (!button) return;
        const petId = Number(button.dataset.petId || 0);
        const pet = petsList.find(item => Number(item.pet_id ?? item.id) === petId);
        document.getElementById("queuePetId").value = String(petId);
        document.getElementById("queuePetName").textContent =
            (pet?.mathucung ? pet.mathucung + " · " : "") + (pet?.ten_thucung || pet?.name || "Thú cưng");
        document.getElementById("queuePetNote").value = button.dataset.queueNote || "";
        $("#addMedicalQueueModal").modal("show");
    });

    // Xác nhận gửi vào hàng đợi; ghi chú là tùy chọn.
    document.getElementById("addMedicalQueueForm")?.addEventListener("submit", async event => {
        event.preventDefault();
        const petId = Number(document.getElementById("queuePetId").value || 0);
        const note = document.getElementById("queuePetNote").value.trim();
        const button = document.getElementById("btnConfirmAddMedicalQueue");
        if (!petId) {
            alert("Không xác định được thú cưng cần đưa vào chờ khám.");
            return;
        }
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Đang gửi...';
        try {
            const result = await apiRequest("/medical/queue", {
                method: "POST",
                body: JSON.stringify({ pet_id: petId, ghi_chu: note })
            });
            $("#addMedicalQueueModal").modal("hide");
            document.getElementById("addMedicalQueueForm").reset();
            await loadPets();
            alert(result?.message || "Đã đưa thú cưng vào danh sách chờ khám.");
        } catch (error) {
            alert("Không thể đưa thú cưng vào chờ khám: " + error.message);
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-check mr-1"></i>Xác nhận';
        }
    });

    // Tiếp nhận hồ sơ chủ nuôi và thú cưng
    document.getElementById("petForm").addEventListener("submit", async (e) => {
        e.preventDefault();

        if (!canReceivePets) {
            alert("Chỉ nhân viên hoặc quản trị viên được tiếp nhận thú cưng.");
            return;
        }

        const ownerName = document.getElementById("ho_ten_chu").value.trim();
        const ownerPhone = document.getElementById("phone").value.trim();
        const petName = document.getElementById("ten_thucung").value.trim();
        const species = document.getElementById("loai").value;
        const breed = document.getElementById("giong").value.trim() || null;
        const dob = document.getElementById("ngaysinh").value || null;

        const gender = normalizeGender(
            document.getElementById("gioitinh").value,
        );

        const weight = parseFloat(document.getElementById("can_nang").value);

        if (!ownerName || !ownerPhone) {
            alert("Vui lòng nhập đầy đủ Họ tên và Số điện thoại chủ nuôi!");
            return;
        }

        if (!petName) {
            alert("Vui lòng nhập tên thú cưng!");
            return;
        }

        if (isNaN(weight) || weight <= 0) {
            alert("Cân nặng thú cưng phải lớn hơn 0 kg!");
            document.getElementById("can_nang").focus();
            return;
        }

        const btnSave = document.getElementById("btnSavePet");

        btnSave.disabled = true;
        btnSave.innerHTML =
            '<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...';

        try {
            let ownerId = null;

            try {
                const ownersRes = await apiRequest("/owners");
                const owners = extractList(ownersRes);

                const existingOwner = owners.find(
                    (o) => (o.phone || "").trim() === ownerPhone,
                );

                if (existingOwner) {
                    ownerId = existingOwner.owner_id ?? existingOwner.id;
                }
            } catch (_) {}

            const payload = {
                owner_id: ownerId,
                ho_ten: ownerName,
                phone: ownerPhone,
                name: petName,
                species: species,
                breed: breed,
                gender: gender,
                dob: dob,
                weight: weight,
            };

            await apiRequest("/pets", {
                method: "POST",
                body: JSON.stringify(payload),
            });

            e.target.reset();

            $("#addPetModal").modal("hide");

            await loadPets();

            alert("Tiếp nhận hồ sơ chủ nuôi & thú cưng thành công!");
        } catch (err) {
            alert("Lỗi thêm hồ sơ thú cưng: " + err.message);
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save mr-1"></i> Lưu Hồ sơ';
        }
    });
});

// Tải danh sách thú cưng
async function loadPets() {
    try {
        const res = await apiRequest("/pets");

        petsList = extractList(res);

        renderPetsTable(petsList);
    } catch (err) {
        document.getElementById("petsTableBody").innerHTML =
            `<tr><td colspan="7" class="text-danger text-center">${escapePetHtml(err.message)}</td></tr>`;
    }
}

// Hiển thị danh sách thú cưng
function renderPetsTable(items) {
    const statusBadges = {
        NOI_TRU: '<span class="badge badge-warning">Nội trú</span>',
        NGOAI_TRU: '<span class="badge badge-success">Ngoại trú</span>',
        DA_XUAT_VIEN: '<span class="badge badge-secondary">Đã xuất viện</span>',
        CHUYEN_VIEN: '<span class="badge badge-danger">Chuyển viện</span>',
    };

    const tbody = document.getElementById("petsTableBody");

    if (!items.length) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="text-center text-muted">Chưa có dữ liệu thú cưng</td></tr>';
        return;
    }

    tbody.innerHTML = items
        .map((p) => {
            const petId = p.id ?? p.pet_id;

            const petName = p.name || p.ten_thucung || "-";
            const species = p.species || p.loai || "";
            const breed = p.breed || p.giong || "";

            const ownerName =
                (p.owner && (p.owner.name || p.owner.ho_ten)) ||
                p.ho_ten_chu ||
                "-";

            const ownerPhone = (p.owner && p.owner.phone) || p.phone || "";

            const weight =
                p.weight ??
                p.can_nang ??
                (p.latest_weight && p.latest_weight.can_nang) ??
                "-";

            return `
            <tr>
                <td class="font-weight-bold text-primary">
                    ${escapePetHtml(p.mathucung || "-")}
                </td>

                <td>
                    <strong>${escapePetHtml(petName)}</strong>
                    (${escapePetHtml(species)}${breed ? " - " + escapePetHtml(breed) : ""})
                </td>

                <td>
                    ${escapePetHtml(ownerName)}
                    <br>
                    <small class="text-muted">
                        ${escapePetHtml(ownerPhone)}
                    </small>
                </td>

                <td>
                    <strong>
                        ${
                            weight !== "-" && weight !== null && weight !== ""
                                ? escapePetHtml(weight) + " kg"
                                : "-"
                        }
                    </strong>
                </td>

                <td>
                    ${statusBadges[p.trangthai] || escapePetHtml(p.trangthai || "Ngoại trú")}
                </td>
                <td>
                    ${p.trangthai_hangdoi ? '<span class="badge ' + (p.trangthai_hangdoi === "DANG_KHAM" ? "badge-warning" : p.trangthai_hangdoi === "DA_KHAM" ? "badge-success" : "badge-secondary") + '">' + ({CHO_KHAM:"Chưa khám",DANG_KHAM:"Đang khám",DA_KHAM:"Đã khám"})[p.trangthai_hangdoi] + '</span>' : p.trangthai_kham ? '<span class="badge badge-info">' + escapePetHtml(({CHUA_KHAM:"Chưa khám",DANG_DIEU_TRI:"Đang điều trị",HOAN_THANH:"Hoàn thành",CHUYEN_VIEN:"Chuyển viện"})[p.trangthai_kham] || p.trangthai_kham) + '</span>' : '<span class="text-muted">Chưa khám</span>'}
                    ${p.kham_da_thanh_toan ? '<br><small class="text-success">Đã thanh toán</small>' : ''}
                    ${p.ngaykham_gannhat ? '<br><small class="text-muted">' + formatPetDate(p.ngaykham_gannhat) + '</small>' : ''}

                </td>

                <td>
                    ${["QUAN_TRI", "NHAN_VIEN"].includes(currentUserRole) && !p.trangthai_hangdoi && (!p.trangthai_kham || p.trangthai_kham === "CHUA_KHAM") ? '<button type="button" class="btn btn-sm btn-outline-danger mr-1" data-add-medical-queue data-pet-id="' + Number(petId) + '" data-queue-note="' + escapePetHtml(p.ghi_chu_cho_kham || "") + '"><i class="fas fa-user-clock mr-1"></i>Đưa vào chờ khám</button>' : p.trangthai_hangdoi === "CHO_KHAM" ? '<span class="badge badge-secondary mr-1">Đang chờ khám</span>' : p.trangthai_hangdoi === "DANG_KHAM" ? '<span class="badge badge-warning mr-1">Đang khám</span>' : ""}
                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="openPetDetail(${Number(petId)})"><i class="fas fa-eye"></i> Chi tiết</button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="printPetProfile(${Number(petId)})"><i class="fas fa-print"></i> In hồ sơ</button>
                </td>
            </tr>
        `;
        })
        .join("");
}

// Mã hóa nội dung trước khi hiển thị HTML
function escapePetHtml(value) {
    return String(value ?? "").replace(
        /[&<>"']/g,
        (char) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[char],
    );
}

// Định dạng ngày giờ
function formatPetDate(value) {
    if (!value) return "-";

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? escapePetHtml(value)
        : date.toLocaleString("vi-VN");
}

// Xem chi tiết thú cưng, cập nhật cân nặng và trạng thái phiếu khám gần nhất
async function openPetDetail(petId) {
    const content = document.getElementById("petDetailContent");
    content.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải thông tin...</div>';
    $("#petDetailModal").modal("show");

    try {
        const response = await apiRequest(`/pets/${petId}`);
        const pet = response?.data?.data || response?.data || response;
        const history = Array.isArray(pet.medical_history) ? pet.medical_history : [];
        const exam = pet.latest_exam || null;

        const examStatusLabels = {
            CHUA_KHAM: "Chưa khám",
            DANG_DIEU_TRI: "Đang điều trị",
            HOAN_THANH: "Hoàn thành",
            CHUYEN_VIEN: "Chuyển viện",
        };
        const historyRows = history.length ? history.map((item) => `
            <tr><td>${escapePetHtml(item.record_id)}</td><td>${formatPetDate(item.ngaykham)}</td><td>${escapePetHtml(item.chandoan || "-")}</td><td>${escapePetHtml(item.ten_phauthuat || item.huongxuly || "-")}</td><td>${escapePetHtml(examStatusLabels[item.trangthai] || item.trangthai || "-")}</td><td>${escapePetHtml(item.mahoadon || (item.invoice_id ? "Đã thanh toán" : "Chưa thanh toán"))}</td></tr>
        `).join("") : '<tr><td colspan="6" class="text-center text-muted">Chưa có lịch sử khám bệnh</td></tr>';

        const examStatusControl = exam
            ? `<div class="mt-2"><strong>Trạng thái khám gần nhất:</strong> ${escapePetHtml(examStatusLabels[exam.trangthai] || exam.trangthai || "-")}<br><small class="text-muted">Phiếu khám #${escapePetHtml(exam.record_id)} · ${formatPetDate(exam.ngaykham)}</small></div>`
            : '<div class="text-muted mt-2">Thú cưng chưa có phiếu khám.</div>';

        content.innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6 mb-3"><strong>Mã thú cưng:</strong><br>${escapePetHtml(pet.mathucung || "-")}</div>
                <div class="col-md-6 mb-3"><strong>Tên:</strong><br>${escapePetHtml(pet.name || "-")}</div>
                <div class="col-md-6 mb-3"><strong>Loài / Giống:</strong><br>${escapePetHtml(pet.species || "-")} / ${escapePetHtml(pet.breed || "-")}</div>
                <div class="col-md-6 mb-3"><strong>Ngày sinh:</strong><br>${escapePetHtml(pet.dob || "Chưa cập nhật")}</div>
                <div class="col-md-6 mb-3"><strong>Chủ nuôi:</strong><br>${escapePetHtml(pet.owner?.name || "-")}</div>
                <div class="col-md-6 mb-3"><strong>Số điện thoại:</strong><br>${escapePetHtml(pet.owner?.phone || "-")}</div>
                <div class="col-md-6 mb-3"><strong>Trạng thái lưu trú:</strong><br>${escapePetHtml(pet.trangthai || "-")}</div>
            </div>

            <div class="card border-0 bg-light mb-4">
                <div class="card-body">
                    <h6 class="font-weight-bold text-primary"><i class="fas fa-weight mr-1"></i>Cân nặng hiện tại</h6>
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-7 mb-2">
                            <label for="detailPetWeight">Cân nặng (kg)</label>
                            <input type="number" id="detailPetWeight" class="form-control" min="0.1" max="999" step="0.1" value="${pet.weight ?? ""}" placeholder="Nhập cân nặng">
                        </div>
                        <div class="form-group col-md-5 mb-2">
                            <button type="button" id="btnUpdatePetWeight" class="btn btn-primary btn-block">Lưu cân nặng</button>
                        </div>
                    </div>
                    <small class="text-muted">Mỗi lần cập nhật sẽ được ghi thành một bản ghi mới trong lịch sử cân nặng.</small>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="font-weight-bold text-primary"><i class="fas fa-stethoscope mr-1"></i>Trạng thái khám</h6>
                ${examStatusControl}
            </div>

            <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-history mr-1"></i>Lịch sử khám bệnh</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light"><tr><th>Mã phiếu</th><th>Ngày khám</th><th>Chẩn đoán</th><th>Hướng xử lý</th><th>Trạng thái</th><th>Hóa đơn</th></tr></thead>
                    <tbody>${historyRows}</tbody>
                </table>
            </div>
        `;

        document.getElementById("btnUpdatePetWeight").addEventListener("click", async () => {
            const input = document.getElementById("detailPetWeight");
            const weight = Number(input.value);
            if (!Number.isFinite(weight) || weight <= 0 || weight > 999) {
                alert("Vui lòng nhập cân nặng lớn hơn 0 và không vượt quá 999 kg.");
                input.focus();
                return;
            }
            const button = document.getElementById("btnUpdatePetWeight");
            button.disabled = true;
            try {
                await apiRequest(`/pets/${petId}`, {
                    method: "PUT",
                    body: JSON.stringify({ weight }),
                });
                await loadPets();
                alert("Đã cập nhật cân nặng.");
                await openPetDetail(petId);
            } catch (err) {
                alert("Không cập nhật được cân nặng: " + err.message);
                button.disabled = false;
            }
        });


    } catch (err) {
        content.innerHTML = `<div class="alert alert-danger mb-0">Không tải được chi tiết thú cưng: ${escapePetHtml(err.message)}</div>`;
    }
}


/** In hồ sơ thú cưng và lịch sử lưu trú. */
async function printPetProfile(petId) {
    try {
        const response = await apiRequest("/pets/" + petId);
        const pet = response?.data?.data || response?.data || {};
        const history = Array.isArray(pet.medical_history) ? pet.medical_history : [];
        const historyRows = history.map((item, index) =>
            "<tr><td>" + (index + 1) + "</td><td>#" + escapePetHtml(item.record_id) + "</td><td>" + formatPetDate(item.ngaykham) +
            "</td><td>" + escapePetHtml(item.chandoan || "-") + "</td><td>" + escapePetHtml(item.trangthai || "-") +
            "</td><td>" + escapePetHtml(item.mahoadon || (item.invoice_id ? "Đã thanh toán" : "Chưa thanh toán")) + "</td></tr>"
        ).join("");
        const w = window.open("", "_blank", "width=900,height=750");
        if (!w) { alert("Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup rồi thử lại."); return; }
        w.document.write("<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><title>Hồ sơ thú cưng</title><style>body{font-family:Arial;padding:28px;color:#222}h2,h3{text-align:center}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:24px 0}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #777;padding:8px;text-align:left}</style></head><body>" +
            "<h2>PET CLINIC</h2><h3>HỒ SƠ THÚ CƯNG</h3><div class='grid'>" +
            "<div><b>Mã thú cưng:</b> " + escapePetHtml(pet.mathucung || "-") + "</div><div><b>Tên:</b> " + escapePetHtml(pet.name || "-") + "</div>" +
            "<div><b>Loài:</b> " + escapePetHtml(pet.species || "-") + "</div><div><b>Giống:</b> " + escapePetHtml(pet.breed || "-") + "</div>" +
            "<div><b>Giới tính:</b> " + escapePetHtml(pet.gender || "-") + "</div><div><b>Ngày sinh:</b> " + escapePetHtml(pet.dob || "-") + "</div>" +
            "<div><b>Cân nặng hiện tại:</b> " + escapePetHtml(pet.weight || "-") + " kg</div><div><b>Trạng thái:</b> " + escapePetHtml(pet.trangthai || "-") + "</div>" +
            "<div><b>Chủ nuôi:</b> " + escapePetHtml(pet.owner?.name || "-") + "</div><div><b>Số điện thoại:</b> " + escapePetHtml(pet.owner?.phone || "-") + "</div></div>" +
            "<h3>LỊCH SỬ KHÁM BỆNH</h3><table><thead><tr><th>STT</th><th>Mã phiếu</th><th>Ngày khám</th><th>Chẩn đoán</th><th>Trạng thái</th><th>Hóa đơn</th></tr></thead><tbody>" +
            (historyRows || "<tr><td colspan='5'>Chưa có lịch sử khám bệnh.</td></tr>") +
            "</tbody></table><script>window.onload=function(){window.print();};<\\/script></body></html>");
        w.document.close();
    } catch (error) { alert("Không thể in hồ sơ thú cưng: " + error.message); }
}


// Lịch tiêm toàn phòng khám, đánh dấu quá hạn và sắp đến hạn.
async function loadVaccinationSchedule() {
    const tbody = document.getElementById("vaccinationScheduleTable");
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Đang tải...</td></tr>';
    try {
        const response = await apiRequest("/vaccinations/schedule");
        const rows = extractList(response);
        tbody.innerHTML = rows.length ? rows.map(item => {
            const status = item.status === "QUA_HAN"
                ? '<span class="badge badge-danger">Quá hạn</span>'
                : item.status === "SAP_DEN_HAN"
                    ? '<span class="badge badge-warning">Sắp đến hạn</span>'
                    : '<span class="badge badge-success">Còn hạn</span>';
            return '<tr><td>' + escapePetHtml(item.mathucung) + '</td><td>' + escapePetHtml(item.ten_thucung) + '</td><td>' + escapePetHtml(item.owner_name || "-") + '<br><small>' + escapePetHtml(item.owner_phone || "") + '</small></td><td>' + escapePetHtml(item.vaccine_name) + '</td><td>' + escapePetHtml(item.vaccinated_at) + '</td><td>' + escapePetHtml(item.next_due_date) + '</td><td>' + status + '</td></tr>';
        }).join("") : '<tr><td colspan="7" class="text-center text-muted">Chưa có lịch hẹn tiêm chủng.</td></tr>';
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-danger text-center">' + escapePetHtml(error.message) + '</td></tr>';
    }
}
