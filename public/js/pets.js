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

    // Tải danh sách thú cưng ngay khi mở trang
    await loadPets();

    const currentUser = getCurrentUser();

    currentUserRole = String(currentUser?.vaitro || "")
        .trim()
        .toUpperCase();

    const canReceivePets = ["QUAN_TRI", "NHAN_VIEN"].includes(currentUserRole);

    const addPetButton = document.getElementById("btnOpenAddPet");

    if (addPetButton && canReceivePets) {
        addPetButton.classList.remove("d-none");
    }

    // Tự động điền mã thú cưng khi mở modal tiếp nhận
    $("#addPetModal").on("show.bs.modal", function () {
        const codeInput = document.getElementById("mathucung");

        if (codeInput) {
            codeInput.value = generateNextPetCode();
        }
    });

    // Tìm kiếm nhanh
    document.getElementById("searchPet").addEventListener("input", (e) => {
        const kw = e.target.value.trim().toLowerCase();

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
                    <button type="button" class="btn btn-sm btn-outline-info"
                        onclick="showPetQr(${Number(petId)})">
                        <i class="fas fa-qrcode mr-1"></i> Xem QR
                    </button>
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
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        onclick="openPetDetail(${Number(petId)})">
                        <i class="fas fa-eye"></i> Chi tiết
                    </button>
                </td>
            </tr>
        `;
        })
        .join("");
}

function showPetQr(petId) {
    const pet = petsList.find((item) => Number(item.id ?? item.pet_id) === Number(petId));
    if (!pet || !pet.qr_url || !pet.qr_code) {
        alert("Chưa có dữ liệu QR. Vui lòng tải lại danh sách hoặc kiểm tra migration.");
        return;
    }

    const container = document.getElementById("petQrContainer");
    container.innerHTML = "";
    document.getElementById("petQrName").textContent =
        (pet.name || pet.ten_thucung || "Thú cưng") + " · " + (pet.mathucung || "");
    document.getElementById("petQrCodeText").textContent = "Mã QR: " + pet.qr_code;
    document.getElementById("petQrOpenLink").href = pet.qr_url;

    if (typeof QRCode === "undefined") {
        container.textContent = "Không tải được thư viện tạo QR. Vui lòng kiểm tra kết nối Internet.";
    } else {
        new QRCode(container, {
            text: pet.qr_url,
            width: 220,
            height: 220,
            colorDark: "#111827",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M,
        });
    }

    $("#petQrModal").modal("show");
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

// Xem chi tiết thú cưng
async function openPetDetail(petId) {
    const content = document.getElementById("petDetailContent");

    content.innerHTML =
        '<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin mr-1"></i> Đang tải thông tin...</div>';

    $("#petDetailModal").modal("show");

    try {
        const response = await apiRequest(`/pets/${petId}`);

        const pet = response?.data?.data || response?.data || response;

        const history = Array.isArray(pet.boarding_history)
            ? pet.boarding_history
            : [];

        const statusLabel = {
            checked_in: '<span class="badge badge-success">Đang lưu trú</span>',
            checked_out:
                '<span class="badge badge-secondary">Đã trả lồng</span>',
        };

        const historyRows = history.length
            ? history
                  .map(
                      (item) => `
                <tr>
                    <td>
                        ${escapePetHtml(item.malong || "-")}
                        <br>
                        <small class="text-muted">
                            ${escapePetHtml(item.ten_long || "")}
                        </small>
                    </td>

                    <td>${formatPetDate(item.check_in)}</td>
                    <td>${formatPetDate(item.check_out)}</td>

                    <td>
                        ${statusLabel[item.status] || escapePetHtml(item.status || "-")}
                    </td>
                </tr>
            `,
                  )
                  .join("")
            : '<tr><td colspan="4" class="text-center text-muted">Chưa có lịch sử lưu trú / trả lồng</td></tr>';

        content.innerHTML = `
            <div class="row mb-4">
                <div class="col-md-6 mb-2">
                    <strong>Mã thú cưng:</strong>
                    ${escapePetHtml(pet.mathucung || "-")}
                </div>

                <div class="col-md-6 mb-2">
                    <strong>Tên:</strong>
                    ${escapePetHtml(pet.name || "-")}
                </div>

                <div class="col-md-6 mb-2">
                    <strong>Loài / Giống:</strong>
                    ${escapePetHtml(pet.species || "-")} /
                    ${escapePetHtml(pet.breed || "-")}
                </div>

                <div class="col-md-6 mb-2">
                    <strong>Trạng thái:</strong>
                    ${escapePetHtml(pet.trangthai || "-")}
                </div>

                <div class="col-md-6 mb-2">
                    <strong>Chủ nuôi:</strong>
                    ${escapePetHtml(pet.owner?.name || "-")}
                </div>

                <div class="col-md-6 mb-2">
                    <strong>Số điện thoại:</strong>
                    ${escapePetHtml(pet.owner?.phone || "-")}
                </div>
            </div>

            <h6 class="font-weight-bold text-primary mb-3">
                <i class="fas fa-history mr-1"></i>
                Lịch sử lưu trú / trả lồng
            </h6>

            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Mã lồng</th>
                            <th>Thời gian nhận</th>
                            <th>Thời gian trả</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>${historyRows}</tbody>
                </table>
            </div>
        `;
    } catch (err) {
        content.innerHTML = `<div class="alert alert-danger mb-0">Không tải được chi tiết thú cưng: ${escapePetHtml(err.message)}</div>`;
    }
}
