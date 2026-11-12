document.addEventListener("DOMContentLoaded", async () => {
    initPage();
    await loadInventory();
});

async function loadInventory() {
    const candidates = ["/inventory", "/inventory/items", "/products"];
    let lastError = null;
    for (const ep of candidates) {
        try {
            const res = await apiRequest(ep);
            const items = Array.isArray(res)
                ? res
                : res.data?.data || res.data || [];
            renderInventory(items);
            return;
        } catch (err) {
            lastError = err;
            if (!err.message.includes("could not be found")) break;
        }
    }
    document.getElementById("inventoryTableBody").innerHTML =
        `<tr><td colspan="8" class="text-danger text-center">${lastError?.message || "Lỗi tải kho"}</td></tr>`;
}

function renderInventory(items) {
    const today = new Date().toISOString().split("T")[0];

    const goods = items.filter((i) => i.danhmuc !== "DICH_VU_SPA");
    document.getElementById("inventoryTableBody").innerHTML = goods.length
        ? goods
              .map((item) => {
                  const expiry =
                      item.ngayhethan ||
                      (item.batches &&
                          item.batches[0] &&
                          item.batches[0].ngayhethan) ||
                      "-";
                  const batchNo =
                      item.solo ||
                      (item.batches &&
                          item.batches[0] &&
                          item.batches[0].solo) ||
                      "-";
                  const qty = item.soluong ?? item.tong_ton ?? 0;
                  const isExpired = expiry !== "-" && expiry < today;

                  const statusHtml = isExpired
                      ? '<span class="badge badge-danger">Quá hạn - Khóa xuất (BR-06)</span>'
                      : '<span class="badge badge-success">Khả dụng (FEFO)</span>';

                  return `
                <tr class="${isExpired ? "table-danger" : ""}">
                    <td><code>${item.masanpham}</code></td>
                    <td><strong>${item.ten_sanpham || item.name}</strong></td>
                    <td>${item.danhmuc}</td>
                    <td>${batchNo}</td>
                    <td><strong>${qty}</strong></td>
                    <td>${expiry}</td>
                    <td>${formatCurrency(item.dongia)}</td>
                    <td>${statusHtml}</td>
                </tr>
            `;
              })
              .join("")
        : '<tr><td colspan="8" class="text-center text-muted">Chưa có vật tư / thuốc trong kho</td></tr>';

    const services = items.filter((i) => i.danhmuc === "DICH_VU_SPA");
    document.getElementById("spaTableBody").innerHTML = services.length
        ? services
              .map(
                  (s) => `
            <tr>
                <td><code>${s.masanpham}</code></td>
                <td><strong>${s.ten_sanpham || s.name}</strong></td>
                <td class="text-primary font-weight-bold">${formatCurrency(s.dongia)}</td>
            </tr>
        `,
              )
              .join("")
        : '<tr><td colspan="3" class="text-center text-muted">Chưa có dịch vụ Spa</td></tr>';
}
