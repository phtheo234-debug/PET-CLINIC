let paymentPets = [], paymentCatalog = [], medicalChargeLines = [], extraChargeLines = [];
let selectedPaymentPetId = null, paymentPreview = null;

function invoiceUnwrap(r) { return r && r.data && r.data.data ? r.data.data : (r && r.data ? r.data : (r || {})); }
function invoiceList(r) { const d=invoiceUnwrap(r); return Array.isArray(d)?d:[]; }
function invoiceMoney(v) { return Number(v||0).toLocaleString("vi-VN")+" ₫"; }
function escapeInvoiceHtml(v) { return String(v??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[c])); }
function invoiceCategoryLabel(c) { return ({DICH_VU_SPA:"Dịch vụ",THUC_AN:"Thức ăn",DO_DUNG:"Đồ dùng",THUOC:"Thuốc",VAC_XIN:"Vắc-xin",PHI_KHAM:"Phí khám",PHAU_THUAT:"Phẫu thuật",LUU_TRU:"Lưu trú"}[c]||c); }

document.addEventListener("DOMContentLoaded", async function() {
    // Danh sách hóa đơn là khu vực độc lập, tải ngay khi trang mở,
    // không chờ khởi tạo hồ sơ hay dữ liệu Bảng 1.
    loadInvoices();

    try {
        await initPage();
    } catch (e) {
        console.error("Không khởi tạo được trang thanh toán:", e);
    }

    loadPaymentPets().catch(function(e) {
        console.error("Không tải được danh sách thú cưng:", e);
        document.getElementById("paymentPetInfo").textContent = "Không tải được danh sách thú cưng: " + e.message;
    });

    document.getElementById("paymentPetSearch").addEventListener("input",renderEligiblePaymentPets);
    document.getElementById("eligiblePaymentPetsBody").addEventListener("click",function(event){
        const button=event.target.closest("[data-select-payment-pet]");
        if(button) choosePaymentPet(Number(button.dataset.selectPaymentPet));
    });
    document.getElementById("extraCategory").addEventListener("change",renderExtraItemOptions);
    document.getElementById("extraItem").addEventListener("change",updateExtraUnitPrice);
    document.getElementById("extraQuantity").addEventListener("input",recalculatePayment);
    document.getElementById("btnAddExtraItem").addEventListener("click",addExtraCharge);
    document.getElementById("btnPaymentSuccess").addEventListener("click",submitUnifiedPayment);
    document.getElementById("btnPrintNewInvoice").addEventListener("click",function(){ const id=Number(this.dataset.invoiceId); if(id) printInvoice(id); });
});
async function loadPaymentPets() {
    paymentPets=invoiceList(await apiRequest("/payment/eligible-pets"));
    renderEligiblePaymentPets();
}
function renderEligiblePaymentPets() {
    const body=document.getElementById("eligiblePaymentPetsBody");
    if(!body)return;
    const keyword=(document.getElementById("paymentPetSearch").value||"").trim().toLowerCase();
    const rows=paymentPets.filter(p=>{
        const owner=p.owner||{};
        return [p.mathucung,p.name,owner.name,owner.phone].some(v=>String(v||"").toLowerCase().includes(keyword));
    }).sort((a,b)=>{
        if(Number(a.id)===Number(selectedPaymentPetId))return -1;
        if(Number(b.id)===Number(selectedPaymentPetId))return 1;
        return 0;
    });
    body.innerHTML=rows.length?rows.map(p=>{
        const id=Number(p.id||p.pet_id), owner=p.owner||{};
        const selected=id===Number(selectedPaymentPetId);
        return '<tr'+(selected?' class="table-primary"':'')+'><td><strong>'+escapeInvoiceHtml(p.mathucung||"-")+'</strong> - '+escapeInvoiceHtml(p.name||"")+'</td><td>'+escapeInvoiceHtml(owner.name||"-")+'</td><td>'+escapeInvoiceHtml(owner.phone||"-")+'</td><td class="text-center"><button type="button" class="btn btn-sm '+(selected?'btn-primary':'btn-outline-primary')+'" data-select-payment-pet="'+id+'">Chọn</button></td></tr>';
    }).join(""):'<tr><td colspan="4" class="text-center text-muted">'+(paymentPets.length?'Không tìm thấy thú cưng phù hợp.':'Không có thú cưng đủ điều kiện thanh toán.')+'</td></tr>';
}
async function choosePaymentPet(id) {
    if(Number(selectedPaymentPetId)===Number(id)&&paymentPreview)return;
    selectedPaymentPetId=Number(id); paymentPreview=null; extraChargeLines=[];
    document.getElementById("unifiedPaymentResult").innerHTML="";
    document.getElementById("invoicePrintAction").classList.add("d-none");
    ["medicalChargesSection","extraServicesSection","finalPaymentSection"].forEach(id=>document.getElementById(id).classList.add("d-none"));
    document.getElementById("btnPaymentSuccess").disabled=true;
    try {
        paymentPreview=invoiceUnwrap(await apiRequest("/payment/pets/"+selectedPaymentPetId+"/preview"));
        const p=paymentPreview.pet||{};
        renderEligiblePaymentPets();
        document.getElementById("paymentPetInfo").innerHTML="<strong>Đã chọn: "+escapeInvoiceHtml(p.mathucung)+" - "+escapeInvoiceHtml(p.ten_thucung)+"</strong> · Chủ nuôi: "+escapeInvoiceHtml(p.ten_chu_nuoi||"-")+" · SĐT: "+escapeInvoiceHtml(p.phone||"-");
        const inventoryResponse = await apiRequest("/inventory");
        paymentCatalog = invoiceList(inventoryResponse).filter(x => ["DICH_VU_SPA","THUC_AN","DO_DUNG"].includes(x.danhmuc) && (x.danhmuc === "DICH_VU_SPA" || Number(x.tong_ton_kho ?? x.soluongton ?? 0) > 0));
        renderMedicalCharges(paymentPreview.charges||{});
        renderExtraItemOptions();
        document.getElementById("medicalChargesSection").classList.toggle("d-none",medicalChargeLines.length===0);
        document.getElementById("extraServicesSection").classList.remove("d-none");
        document.getElementById("finalPaymentSection").classList.remove("d-none");
        document.getElementById("invoicePrintAction").classList.add("d-none");
        renderExtraCharges(); recalculatePayment();
    } catch(e) {
        paymentPreview=null;
        document.getElementById("paymentPetInfo").innerHTML='<span class="text-danger">'+escapeInvoiceHtml(e.message)+'</span>';
        ["medicalChargesSection","extraServicesSection","finalPaymentSection"].forEach(id=>document.getElementById(id).classList.add("d-none"));
    }
}
function renderMedicalCharges(c) {
    medicalChargeLines=[];
    if(Number(c.exam_count)>0) medicalChargeLines.push({name:"Phí khám bệnh ("+c.exam_count+" lượt)",quantity:c.exam_count,price:50000,unit:"lượt"});
    (c.prescriptions||[]).forEach(x=>medicalChargeLines.push({name:x.ten_sanpham,quantity:Number(x.soluong),price:Number(x.dongia),unit:x.donvitinh||""}));
    (c.surgeries||[]).forEach(x=>medicalChargeLines.push({name:x.name,quantity:1,price:Number(x.price),unit:"ca"}));
    (c.boardings||[]).forEach(x=>medicalChargeLines.push({
        name:"Lưu trú lồng "+(x.malong||"")+" - "+(x.ten_long||"")+" ("+(String(x.check_in||"").slice(0,10))+" → "+(x.check_out?String(x.check_out).slice(0,10):"hiện tại")+")",
        quantity:Number(x.days),price:Number(x.rate),unit:"ngày"
    }));
    document.getElementById("medicalChargesBody").innerHTML=medicalChargeLines.length?medicalChargeLines.map(x=>"<tr><td>"+escapeInvoiceHtml(x.name)+"</td><td>"+x.quantity+" "+escapeInvoiceHtml(x.unit)+"</td><td>"+invoiceMoney(x.price)+"</td><td>"+invoiceMoney(x.quantity*x.price)+"</td></tr>").join(""):'<tr><td colspan="4" class="text-center text-muted">Không có chi phí khám hoặc điều trị chưa thanh toán.</td></tr>';
    document.getElementById("medicalChargesTotal").textContent=invoiceMoney(medicalChargeLines.reduce((s,x)=>s+x.quantity*x.price,0));
}
function renderExtraItemOptions() {
    const category=document.getElementById("extraCategory").value;
    const quantityGroup=document.getElementById("extraQuantityGroup");
    const isService=category==="DICH_VU_SPA";
    quantityGroup.classList.toggle("d-none",!isService);
    document.querySelector("#extraQuantityGroup label").textContent="Số lượng dịch vụ";
    const items=paymentCatalog.filter(x=>x.danhmuc===category);
    document.getElementById("extraItem").innerHTML='<option value="">-- Chọn sản phẩm / dịch vụ --</option>'+items.map(x=>'<option value="'+Number(x.item_id)+'">'+escapeInvoiceHtml(x.masanpham)+' - '+escapeInvoiceHtml(x.ten_sanpham)+' ('+invoiceMoney(x.dongia)+')</option>').join("");
    updateExtraUnitPrice();
}
function updateExtraUnitPrice() {
    const id=Number(document.getElementById("extraItem").value);
    const item=paymentCatalog.find(x=>Number(x.item_id)===id);
    document.getElementById("extraUnitPrice").value=item?invoiceMoney(item.dongia):"0 ₫";
}
function addExtraCharge() {
    const id=Number(document.getElementById("extraItem").value), item=paymentCatalog.find(x=>Number(x.item_id)===id);
    const qty=item&&item.danhmuc==="DICH_VU_SPA"?Number(document.getElementById("extraQuantity").value):1;
    if(!item||(item.danhmuc==="DICH_VU_SPA"&&(!Number.isInteger(qty)||qty<1))){alert("Vui lòng chọn sản phẩm/dịch vụ và nhập số lượng dịch vụ hợp lệ.");return;}
    const old=extraChargeLines.find(x=>x.item_id===id);
    if(old)old.quantity+=qty;else extraChargeLines.push({item_id:id,name:item.ten_sanpham,category:item.danhmuc,quantity:qty,price:Number(item.dongia),unit:item.donvitinh||""});
    document.getElementById("extraQuantity").value=1; renderExtraCharges(); recalculatePayment();
}
function renderExtraCharges() {
    const body=document.getElementById("extraItemsBody");
    body.innerHTML=extraChargeLines.length?extraChargeLines.map((x,i)=>'<tr><td>'+invoiceCategoryLabel(x.category)+'</td><td>'+escapeInvoiceHtml(x.name)+'</td><td>'+(x.category==="DICH_VU_SPA"?x.quantity+" "+escapeInvoiceHtml(x.unit):"-")+'</td><td>'+invoiceMoney(x.price)+'</td><td>'+invoiceMoney(x.price*x.quantity)+'</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExtraCharge('+i+')">Xóa</button></td></tr>').join(""):'<tr><td colspan="6" class="text-center text-muted">Chưa chọn dịch vụ ngoài khám.</td></tr>';
}
function removeExtraCharge(i){extraChargeLines.splice(i,1);renderExtraCharges();recalculatePayment();}
function recalculatePayment() {
    const lines=medicalChargeLines.concat(extraChargeLines), body=document.getElementById("finalChargesBody");
    body.innerHTML=lines.length?lines.map(x=>'<tr><td>'+escapeInvoiceHtml(x.name)+'</td><td>'+x.quantity+' '+escapeInvoiceHtml(x.unit||"")+'</td><td>'+invoiceMoney(x.price)+'</td><td>'+invoiceMoney(x.quantity*x.price)+'</td></tr>').join(""):'<tr><td colspan="4" class="text-center text-muted">Chưa có khoản phải trả.</td></tr>';
    const total=lines.reduce((s,x)=>s+x.quantity*x.price,0);
    document.getElementById("finalGrandTotal").textContent=invoiceMoney(total);
    document.getElementById("btnPaymentSuccess").disabled=!selectedPaymentPetId||total<=0;
}
async function submitUnifiedPayment() {
    if(!selectedPaymentPetId||!paymentPreview)return;
    const total=medicalChargeLines.concat(extraChargeLines).reduce((s,x)=>s+x.quantity*x.price,0);
    if(total<=0){alert("Không có khoản thu để thanh toán.");return;}
    if(!confirm("Xác nhận chủ nuôi đã thanh toán "+invoiceMoney(total)+"?"))return;
    const button=document.getElementById("btnPaymentSuccess");button.disabled=true;
    try {
        const response=await apiRequest("/payment/checkout",{method:"POST",body:JSON.stringify({pet_id:selectedPaymentPetId,payment_method:document.getElementById("finalPaymentMethod").value,items:extraChargeLines.map(x=>({item_id:x.item_id,quantity:x.quantity}))})});
        const data=invoiceUnwrap(response);
        document.getElementById("unifiedPaymentResult").innerHTML='<div class="alert alert-success">Đã thanh toán và lập hóa đơn <strong>'+escapeInvoiceHtml(data.mahoadon)+'</strong> · Tổng tiền: <strong>'+invoiceMoney(data.tong_thanhtoan)+'</strong></div>';
        const printAction=document.getElementById("invoicePrintAction");
        const printButton=document.getElementById("btnPrintNewInvoice");
        printButton.dataset.invoiceId=String(Number(data.invoice_id));
        printAction.classList.remove("d-none");
        selectedPaymentPetId=null;paymentPreview=null;
        document.getElementById("paymentPetInfo").textContent="Đã thanh toán. Chọn thú cưng khác trong danh sách nếu cần.";
        ["medicalChargesSection","extraServicesSection","finalPaymentSection"].forEach(id=>document.getElementById(id).classList.add("d-none"));

        // Giữ nguyên trang sau thanh toán: chỉ cập nhật danh sách hóa đơn và
        // làm mới dữ liệu thú cưng để lần thanh toán tiếp theo dùng dữ liệu mới.
        await Promise.all([
            loadInvoices(),
            loadPaymentPets().catch(function(err) {
                console.error("Không thể làm mới danh sách thú cưng:", err);
            })
        ]);
        button.disabled = false;
    } catch(e){
        document.getElementById("unifiedPaymentResult").innerHTML='<div class="alert alert-danger">'+escapeInvoiceHtml(e.message)+'</div>';
        button.disabled=false;
    }
}
async function loadInvoices() {
    const body=document.getElementById("invoicesTableBody");
    try {
        const rows=invoiceList(await apiRequest("/payment/invoices")), methods={TIEN_MAT:"Tiền mặt",CHUYEN_KHOAN:"Chuyển khoản",THE:"Quẹt thẻ"};
        body.innerHTML=rows.length?rows.map(x=>'<tr><td class="font-weight-bold text-primary">'+escapeInvoiceHtml(x.mahoadon||"-")+'</td><td>'+escapeInvoiceHtml(x.mathucung||"")+' - '+escapeInvoiceHtml(x.ten_thucung||"")+'</td><td>'+escapeInvoiceHtml(x.ten_chu_nuoi||"-")+'</td><td class="font-weight-bold text-success">'+invoiceMoney(x.tongtien)+'</td><td>'+escapeInvoiceHtml(methods[x.phuongthucthanhtoan]||x.phuongthucthanhtoan||"-")+'</td><td>'+escapeInvoiceHtml(x.ngaytao||"-")+'</td><td><button type="button" class="btn btn-sm btn-outline-primary" onclick="printInvoice('+Number(x.invoice_id)+')">In hóa đơn</button></td></tr>').join(""):'<tr><td colspan="7" class="text-center text-muted">Chưa có hóa đơn thanh toán.</td></tr>';
    }catch(e){body.innerHTML='<tr><td colspan="7" class="text-danger text-center">'+escapeInvoiceHtml(e.message)+'</td></tr>';}
}
async function printInvoice(id) {
    try {
        const invoice=invoiceUnwrap(await apiRequest("/payment/invoices/"+id)), details=invoice.chi_tiet||[];
        const rows=details.map((x,i)=>'<tr><td>'+(i+1)+'</td><td>'+escapeInvoiceHtml(x.ten_sanpham)+'</td><td>'+invoiceCategoryLabel(x.loai_chiphi)+'</td><td>'+x.soluong+' '+escapeInvoiceHtml(x.donvitinh||"")+'</td><td>'+invoiceMoney(x.dongia)+'</td><td>'+invoiceMoney(x.thanhtien)+'</td></tr>').join("");
        const w=window.open("","_blank","width=900,height=750");if(!w){alert("Trình duyệt đang chặn cửa sổ in.");return;}
        w.document.write('<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Hóa đơn</title><style>body{font-family:Arial;padding:28px;color:#222}h2,h3{text-align:center}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #777;padding:8px;text-align:left}.total{text-align:right;font-size:18px;font-weight:bold;margin-top:20px}</style></head><body><h2>PET CLINIC</h2><h3>HÓA ĐƠN THANH TOÁN</h3><p><b>Mã hóa đơn:</b> '+escapeInvoiceHtml(invoice.mahoadon)+'</p><p><b>Ngày tạo:</b> '+escapeInvoiceHtml(invoice.ngaytao)+'</p><p><b>Thú cưng:</b> '+escapeInvoiceHtml(invoice.mathucung)+' - '+escapeInvoiceHtml(invoice.ten_thucung)+'</p><p><b>Chủ nuôi:</b> '+escapeInvoiceHtml(invoice.ten_chu_nuoi)+' · '+escapeInvoiceHtml(invoice.phone)+'</p><p><b>Phương thức:</b> '+escapeInvoiceHtml(invoice.phuongthucthanhtoan)+'</p><table><thead><tr><th>STT</th><th>Nội dung</th><th>Loại phí</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>'+rows+'</tbody></table><div class="total">TỔNG CỘNG: '+invoiceMoney(invoice.tongtien)+'</div><script>window.onload=function(){window.print();};<\/script></body></html>');
        w.document.close();
    }catch(e){alert("Không thể tải hóa đơn: "+e.message);}
}
