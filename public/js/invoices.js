// Endpoint chuẩn theo routes/api.php (Module 7)
let invoiceListEndpoint = "/payment/invoices";
let checkoutEndpoint = "/payment/checkout";

function extractList(res) {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
    return [];
}

document.addEventListener("DOMContentLoaded", async () => {
    initPage();

    // Tải danh sách thú cưng (nếu có dropdown) và danh sách hóa đơn
    await Promise.all([loadPetsForInvoice(), loadInvoices()]);

    // Các ô nhập chi phí dự tính (nếu có trên form)
    const feeInputs = ["fee_medical", "fee_medicine", "fee_cage", "fee_spa"];
    feeInputs.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener("input", calculateTotal);
    });

    // Xử lý lập hóa đơn & duyệt xuất viện (POST /payment/checkout)
    const form = document.getElementById("invoiceForm");
    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const petEl =
                document.getElementById("pet_select") ||
                document.getElementById("inv_pet");
            const rawPetVal = petEl ? petEl.value.trim() : "";
            const petId = Number(rawPetVal.replace(/^#/, ""));

            if (!petId || isNaN(petId)) {
                alert(
                    "Vui lòng chọn hoặc nhập đúng ID thú cưng cần thanh toán!",
                );
                if (petEl) petEl.focus();
                return;
            }

            const paymentMethodEl =
                document.getElementById("phuongthucthanhtoan") ||
                document.getElementById("payment_method");
            const paymentMethod = paymentMethodEl
                ? paymentMethodEl.value
                : "TIEN_MAT";

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : "";
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Đang thanh toán...';
            }

            // Payload khớp với validation của PaymentController::createInvoiceAndCheckout
            const payload = {
                pet_id: petId,
                payment_method: paymentMethod,
            };

            try {
                const res = await apiRequest(checkoutEndpoint, {
                    method: "POST",
                    body: JSON.stringify(payload),
                });

                e.target.reset();
                calculateTotal();
                await loadInvoices();
                await loadPetsForInvoice();

                const detail = res?.data;
                if (detail && detail.mahoadon) {
                    alert(
                        `Thanh toán thành công (${detail.mahoadon})!\n` +
                            `- Tiền thuốc: ${formatCurrency(detail.tien_thuoc || 0)}\n` +
                            `- Tiền lồng lưu trú: ${formatCurrency(detail.tien_long || 0)}\n` +
                            `- Tổng thanh toán: ${formatCurrency(detail.tong_thanhtoan || 0)}\n` +
                            `Đã duyệt xuất viện và giải phóng lồng lưu trú (BR-05).`,
                    );
                } else {
                    alert(
                        res?.message ||
                            "Thanh toán thành công! Đã duyệt xuất viện và giải phóng lồng lưu trú.",
                    );
                }
            } catch (err) {
                alert("Lỗi lập hóa đơn: " + err.message);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }
});

// Tự động đổ danh sách thú cưng nếu phần tử chọn thú cưng là thẻ <select>
async function loadPetsForInvoice() {
    const petEl =
        document.getElementById("pet_select") ||
        document.getElementById("inv_pet");
    if (!petEl || petEl.tagName !== "SELECT") return;

    try {
        const res = await apiRequest("/pets");
        const pets = extractList(res);
        if (!pets.length) {
            petEl.innerHTML =
                '<option value="">-- Chưa có thú cưng trong CSDL --</option>';
            return;
        }
        petEl.innerHTML =
            '<option value="">-- Chọn thú cưng cần thanh toán --</option>' +
            pets
                .map((p) => {
                    const id = p.pet_id ?? p.id;
                    const code = p.mathucung || `PET-${id}`;
                    const name = p.ten_thucung || p.name || "";
                    const owner = p.owner?.ho_ten || p.owner?.name || "";
                    return `<option value="${id}">${code} - ${name}${owner ? ` (Chủ: ${owner})` : ""}</option>`;
                })
                .join("");
    } catch (err) {
        petEl.innerHTML = `<option value="">Lỗi tải thú cưng: ${err.message}</option>`;
    }
}

// Tính tổng tiền tạm tính trên giao diện
function calculateTotal() {
    const m1 = Number(document.getElementById("fee_medical")?.value) || 0;
    const m2 = Number(document.getElementById("fee_medicine")?.value) || 0;
    const m3 = Number(document.getElementById("fee_cage")?.value) || 0;
    const m4 = Number(document.getElementById("fee_spa")?.value) || 0;

    const total = m1 + m2 + m3 + m4;
    const displayEl = document.getElementById("tongtien_display");
    if (displayEl) {
        displayEl.innerText = formatCurrency(total);
    }
    return total;
}

// Tải danh sách hóa đơn (GET /payment/invoices)
async function loadInvoices() {
    const candidates = ["/payment/invoices", "/payments/invoices", "/invoices"];
    let lastError = null;

    for (const ep of candidates) {
        try {
            const res = await apiRequest(ep);
            invoiceListEndpoint = ep;
            renderInvoices(extractList(res));
            return;
        } catch (err) {
            lastError = err;
            if (!err.message.includes("could not be found")) {
                break;
            }
        }
    }

    const tbody = document.getElementById("invoicesTableBody");
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-danger text-center">
                    ${lastError?.message || "Lỗi tải danh sách hóa đơn"}
                </td>
            </tr>`;
    }
}

// Hiển thị danh sách hóa đơn
function renderInvoices(invoices) {
    const tbody = document.getElementById("invoicesTableBody");
    if (!tbody) return;

    if (!invoices.length) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="text-center text-muted">Chưa có hóa đơn thanh toán</td></tr>';
        return;
    }

    const methodMap = {
        TIEN_MAT: "Tiền mặt",
        CHUYEN_KHOAN: "Chuyển khoản",
        THE: "Thẻ",
    };

    const statusMap = {
        DA_THANH_TOAN: '<span class="badge badge-success">Đã thanh toán</span>',
        CHO_THANH_TOAN:
            '<span class="badge badge-warning">Chờ thanh toán</span>',
        DA_HUY: '<span class="badge badge-danger">Đã hủy</span>',
    };

    tbody.innerHTML = invoices
        .map((inv) => {
            // Backend join bảng pets trả về ten_thucung và mathucung
            const petLabel = inv.ten_thucung
                ? `${inv.mathucung ? inv.mathucung + " - " : ""}${inv.ten_thucung}`
                : (inv.pet && inv.pet.ten_thucung) ||
                  inv.pet_info ||
                  `#${inv.pet_id || ""}`;

            // Backend join bảng owners trả về ten_chu_nuoi
            const ownerLabel =
                inv.ten_chu_nuoi ||
                (inv.owner && (inv.owner.ho_ten || inv.owner.name)) ||
                inv.owner_info ||
                `#${inv.owner_id || ""}`;

            return `
                <tr>
                    <td class="font-weight-bold text-primary">
                        ${inv.mahoadon || ""}
                    </td>
                    <td>${petLabel}</td>
                    <td>${ownerLabel}</td>
                    <td class="font-weight-bold text-success">
                        ${formatCurrency(inv.tongtien)}
                    </td>
                    <td>${methodMap[inv.phuongthucthanhtoan] || inv.phuongthucthanhtoan || ""}</td>
                    <td>${statusMap[inv.trangthai] || `<span class="badge badge-secondary">${inv.trangthai || ""}</span>`}</td>
                    <td><small>${inv.ngaytao || ""}</small></td>
                </tr>
            `;
        })
        .join("");
}
