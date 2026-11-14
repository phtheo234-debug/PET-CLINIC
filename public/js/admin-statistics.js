document.addEventListener("DOMContentLoaded", async () => {
    initPage();

    const $ = (id) => document.getElementById(id);
    const today = new Date();
    const dateValue = (d) => [d.getFullYear(), String(d.getMonth() + 1).padStart(2, "0"), String(d.getDate()).padStart(2, "0")].join("-");
    const monthValue = (d) => dateValue(d).slice(0, 7);
    const money = (value) => formatCurrency(Number(value || 0));
    const categoryNames = {
        THUOC: "Thuốc", VAC_XIN: "Vắc-xin", THUC_AN: "Thức ăn",
        PHAU_THUAT: "Phẫu thuật", DO_DUNG: "Đồ dùng", PHI_KHAM: "Phí khám", LUU_TRU: "Lưu trú",
        DICH_VU_SPA: "Dịch vụ Spa"
    };
    let report = null;
    let dayChart = null;
    let weekChart = null;
    let selectedStore = null;
    let storeRows = [];

    const formatDate = (value) => {
        if (!value) return "-";
        const p = String(value).substring(0, 10).split("-");
        return p.length === 3 ? p.reverse().join("/") : value;
    };
    const makeCell = (value, className = "") => {
        const td = document.createElement("td");
        td.textContent = value;
        if (className) td.className = className;
        return td;
    };
    const renderCategoryTable = (bodyId, rows) => {
        const body = $(bodyId);
        body.replaceChildren();
        if (!rows || !rows.length) {
            const tr = document.createElement("tr");
            const td = makeCell("Không có khoản thu trong kỳ.");
            td.colSpan = 2; td.className = "text-center text-muted";
            tr.appendChild(td); body.appendChild(tr); return;
        }
        rows.forEach(row => {
            const tr = document.createElement("tr");
            tr.appendChild(makeCell(categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác"));
            tr.appendChild(makeCell(money(row.tong_doanh_thu), "text-right font-weight-bold"));
            body.appendChild(tr);
        });
    };
    const drawCharts = (data) => {
        if (dayChart) dayChart.destroy();
        if (weekChart) weekChart.destroy();

        const dailyCategories = data.theo_loai_chi_phi_ngay || [];
        $("khongCoDoanhThuNgay").classList.toggle("d-none", dailyCategories.length > 0);
        if (dailyCategories.length && typeof Chart !== "undefined") {
            dayChart = new Chart($("chartDoanhThuNgay"), {
                type: "doughnut",
                data: {
                    labels: dailyCategories.map(x => categoryNames[x.loai_chiphi] || x.loai_chiphi || "Khác"),
                    datasets: [{ data: dailyCategories.map(x => Number(x.tong_doanh_thu || 0)) }]
                },
                options: { responsive: true, plugins: { legend: { position: "bottom" }, tooltip: {
                    callbacks: { label: (ctx) => ctx.label + ": " + money(ctx.raw) }
                } } }
            });
        }
        const weekly = data.theo_ngay_trong_tuan || [];
        if (typeof Chart !== "undefined") {
            weekChart = new Chart($("chartDoanhThuTuan"), {
                type: "bar",
                data: {
                    labels: weekly.map(x => formatDate(x.ngay)),
                    datasets: [{ label: "Doanh thu", data: weekly.map(x => Number(x.tong_doanh_thu || 0)), borderWidth: 1 }]
                },
                options: { responsive: true, plugins: { legend: { display: false }, tooltip: {
                    callbacks: { label: (ctx) => money(ctx.raw) }
                } }, scales: { y: { beginAtZero: true, ticks: {
                    callback: value => Number(value).toLocaleString("vi-VN") + " đ"
                } } } }
            });
        }
    };

    const loadStatistics = async () => {
        const query = new URLSearchParams({
            ngay: $("chonNgay").value,
            tuan: $("chonTuan").value,
            thang: $("chonThang").value,
            nam: $("chonNam").value
        });
        try {
            const response = await apiRequest("/analytics/revenue?" + query.toString());
            report = response.data || {};
            $("tongDoanhThu").textContent = money(report.tong_doanh_thu_ngay);
            $("soHoaDon").textContent = Number(report.so_hoa_don_ngay || 0).toLocaleString("vi-VN");
            $("soCaKham").textContent = Number(report.so_ca_kham || 0).toLocaleString("vi-VN") + " CA";
            $("tongNgay").textContent = money(report.tong_doanh_thu_ngay);
            $("hoaDonNgay").textContent = Number(report.so_hoa_don_ngay || 0).toLocaleString("vi-VN");
            $("caKhamNgay").textContent = Number(report.so_ca_kham || 0).toLocaleString("vi-VN") + " CA";
            $("nhanTuan").textContent = formatDate(report.tuan_tu_ngay) + " – " + formatDate(report.tuan_den_ngay);
            $("tongTuan").textContent = money(report.tong_doanh_thu_tuan);
            $("tongThang").textContent = money(report.tong_doanh_thu_thang);
            $("tongNam").textContent = money(report.tong_doanh_thu_nam);
            renderCategoryTable("bangTheoLoaiNgay", report.theo_loai_chi_phi_ngay);
            renderCategoryTable("bangTheoThang", report.theo_loai_chi_phi_thang);
            renderCategoryTable("bangTheoNam", report.theo_loai_chi_phi_nam);
            drawCharts(report);
        } catch (error) {
            alert("Không thể tải thống kê doanh thu: " + error.message);
        }
    };

    const csvEscape = value => '"' + String(value ?? "").replace(/"/g, '""') + '"';
    const exportCsv = (filename, title, rows) => {
        const csv = "\uFEFF" + [[title], ["Nội dung", "Giá trị"], ...rows]
            .map(row => row.map(csvEscape).join(",")).join("\r\n");
        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    };
    const printReport = (title, rows) => {
        const popup = window.open("", "_blank", "width=850,height=650");
        if (!popup) { alert("Trình duyệt đang chặn cửa sổ in. Hãy cho phép pop-up rồi thử lại."); return; }
        const htmlRows = rows.map(row => "<tr><td>" + escapeHtml(row[0]) + "</td><td>" + escapeHtml(row[1]) + "</td></tr>").join("");
        popup.document.write("<!doctype html><html lang='vi'><head><meta charset='utf-8'><title>" + escapeHtml(title) +
            "</title><style>body{font-family:Arial;padding:28px}h2{text-align:center}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{border:1px solid #999;padding:9px}th{background:#eee}td:last-child{text-align:right}</style></head><body><h2>" +
            escapeHtml(title) + "</h2><table><thead><tr><th>Nội dung</th><th>Giá trị</th></tr></thead><tbody>" + htmlRows +
            "</tbody></table><script>window.onload=()=>window.print();<\\/script></body></html>");
        popup.document.close();
    };
    const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, ch => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[ch]);

    const categoryRows = (rows) => (rows || []).map(row => [
        categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác", money(row.tong_doanh_thu)
    ]);
    const dayRows = () => [
        ["Ngày", formatDate(report.ngay)],
        ["Tổng doanh thu", money(report.tong_doanh_thu_ngay)],
        ["Số hóa đơn đã thanh toán", report.so_hoa_don_ngay || 0],
        ["Số ca đã khám", report.so_ca_kham || 0],
        ...categoryRows(report.theo_loai_chi_phi_ngay)
    ];
    const weekRows = () => [
        ["Tuần", formatDate(report.tuan_tu_ngay) + " – " + formatDate(report.tuan_den_ngay)],
        ["Tổng doanh thu tuần", money(report.tong_doanh_thu_tuan)],
        ...(report.theo_ngay_trong_tuan || []).map(row => [formatDate(row.ngay), money(row.tong_doanh_thu)])
    ];
    const monthRows = () => [["Tháng", report.thang], ["Tổng doanh thu", money(report.tong_doanh_thu_thang)], ...categoryRows(report.theo_loai_chi_phi_thang)];
    const yearRows = () => [["Năm", report.nam], ["Tổng doanh thu", money(report.tong_doanh_thu_nam)], ...categoryRows(report.theo_loai_chi_phi_nam)];

    $("chonNgay").value = dateValue(today);
    $("chonTuan").value = dateValue(today);
    $("chonThang").value = monthValue(today);
    $("chonNam").value = today.getFullYear();

    $("btnNgay").addEventListener("click", loadStatistics);
    $("btnTuan").addEventListener("click", loadStatistics);
    $("btnThang").addEventListener("click", loadStatistics);
    $("btnNam").addEventListener("click", loadStatistics);
    $("xuatNgay").addEventListener("click", () => report && exportCsv("doanh-thu-ngay-" + report.ngay + ".csv", "DOANH THU THEO NGÀY", dayRows()));
    $("inNgay").addEventListener("click", () => report && printReport("DOANH THU THEO NGÀY", dayRows()));
    $("xuatTuan").addEventListener("click", () => report && exportCsv("doanh-thu-tuan-" + report.tuan_tu_ngay + ".csv", "DOANH THU THEO TUẦN", weekRows()));
    $("inTuan").addEventListener("click", () => report && printReport("DOANH THU THEO TUẦN", weekRows()));
    $("xuatThang").addEventListener("click", () => report && exportCsv("doanh-thu-thang-" + report.thang + ".csv", "DOANH THU THEO THÁNG", monthRows()));
    $("inThang").addEventListener("click", () => report && printReport("DOANH THU THEO THÁNG", monthRows()));
    $("xuatNam").addEventListener("click", () => report && exportCsv("doanh-thu-nam-" + report.nam + ".csv", "DOANH THU THEO NĂM", yearRows()));
    $("inNam").addEventListener("click", () => report && printReport("DOANH THU THEO NĂM", yearRows()));

    const loadInventoryAlerts = async () => {
        const dateText = value => value ? formatDate(value) : "-";
        try {
            const response = await apiRequest("/analytics/inventory-alerts");
            const data = response.data || {};
            $("countSapHetHan").textContent = data.so_sap_het_han || 0;
            $("countDaHetHan").textContent = data.so_da_het_han || 0;

            const soonBody = $("tableSapHetHan");
            soonBody.replaceChildren();
            (data.sap_het_han || []).forEach(item => {
                const tr = document.createElement("tr");
                [item.ten_sanpham + " (" + (categoryNames[item.danhmuc] || item.danhmuc) + ")",
                    item.solo, Number(item.soluong).toLocaleString("vi-VN"), dateText(item.ngayhethan)]
                    .forEach(value => tr.appendChild(makeCell(value)));
                soonBody.appendChild(tr);
            });
            if (!soonBody.children.length) {
                const tr = document.createElement("tr"); const td = makeCell("Không có lô hàng sắp hết hạn.");
                td.colSpan = 4; td.className = "text-center text-muted"; tr.appendChild(td); soonBody.appendChild(tr);
            }

            const expiredBody = $("tableDaHetHan");
            expiredBody.replaceChildren();
            (data.da_het_han || []).forEach(item => {
                const tr = document.createElement("tr");
                [item.ten_sanpham + " (" + (categoryNames[item.danhmuc] || item.danhmuc) + ")",
                    item.solo, Number(item.soluong).toLocaleString("vi-VN"), dateText(item.ngayhethan)]
                    .forEach(value => tr.appendChild(makeCell(value)));
                const action = document.createElement("td");
                const button = document.createElement("button");
                button.type = "button"; button.className = "btn btn-sm btn-outline-danger"; button.textContent = "Xóa lô";
                button.addEventListener("click", async () => {
                    if (!confirm("Xóa lô đã hết hạn này khỏi danh sách kho?")) return;
                    try {
                        await apiRequest("/admin/inventory/batches/" + Number(item.batch_id), { method: "DELETE" });
                        await loadInventoryAlerts();
                    } catch (error) { alert("Không thể xóa lô: " + error.message); }
                });
                action.appendChild(button); tr.appendChild(action); expiredBody.appendChild(tr);
            });
            if (!expiredBody.children.length) {
                const tr = document.createElement("tr"); const td = makeCell("Không có lô hàng đã hết hạn.");
                td.colSpan = 5; td.className = "text-center text-muted"; tr.appendChild(td); expiredBody.appendChild(tr);
            }

            const stores = data.kho_sap_het || {};
            const storesBox = $("danhSachKhoSapHet");
            storesBox.replaceChildren();
            Object.entries(stores).forEach(([key, store]) => {
                const col = document.createElement("div");
                col.className = "col-md-6 col-xl-3 mb-2";
                const button = document.createElement("button");
                button.type = "button";
                button.className = "btn btn-outline-warning btn-block text-left h-100";
                button.innerHTML = "<strong></strong><br><small></small>";
                button.querySelector("strong").textContent = store.ten_kho;
                button.querySelector("small").textContent = (store.so_mat_hang || 0) + " mặt hàng sắp hết";
                button.addEventListener("click", () => {
                    selectedStore = key;
                    storeRows = store.items || [];
                    $("tenKhoSapHet").textContent = store.ten_kho;
                    const body = $("bangChiTietKhoSapHet"); body.replaceChildren();
                    if (!storeRows.length) {
                        const tr = document.createElement("tr"); const td = makeCell("Kho này chưa có mặt hàng sắp hết.");
                        td.colSpan = 3; td.className = "text-center text-muted"; tr.appendChild(td); body.appendChild(tr);
                    } else {
                        storeRows.forEach(item => {
                            const tr = document.createElement("tr");
                            tr.appendChild(makeCell(item.ma));
                            tr.appendChild(makeCell(item.ten));
                            tr.appendChild(makeCell(Number(item.so_luong).toLocaleString("vi-VN"), "text-right"));
                            body.appendChild(tr);
                        });
                    }
                    $("chiTietKhoSapHet").classList.remove("d-none");
                    $("chiTietKhoSapHet").scrollIntoView({ behavior: "smooth", block: "nearest" });
                });
                col.appendChild(button); storesBox.appendChild(col);
            });
            $("xuatKhoSapHet").onclick = () => selectedStore && exportCsv("hang-sap-het-" + selectedStore.toLowerCase() + ".csv",
                "HÀNG SẮP HẾT - " + $("tenKhoSapHet").textContent,
                [["Mã", "Tên", "Số lượng"], ...storeRows.map(item => [item.ma, item.ten, item.so_luong])]);
            $("inKhoSapHet").onclick = () => selectedStore && printReport("HÀNG SẮP HẾT - " + $("tenKhoSapHet").textContent,
                [["Mã", "Tên", "Số lượng"], ...storeRows.map(item => [item.ma + " - " + item.ten, item.so_luong])]);
        } catch (error) {
            $("danhSachKhoSapHet").textContent = "Không tải được danh sách kho sắp hết hàng.";
        }
    };

    await Promise.all([loadStatistics(), loadInventoryAlerts()]);
});
