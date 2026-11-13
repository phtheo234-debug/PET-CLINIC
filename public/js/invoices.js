let paymentPets = [], paymentCatalog = [], medicalChargeLines = [], extraChargeLines = [];
let selectedPaymentPetId = null, paymentPreview = null;

function invoiceUnwrap(r) { return r && r.data && r.data.data ? r.data.data : (r && r.data ? r.data : (r || {})); }
function invoiceList(r) { const d=invoiceUnwrap(r); return Array.isArray(d)?d:[]; }
function invoiceMoney(v) { return Number(v||0).toLocaleString("vi-VN")+" ₫"; }
function escapeInvoiceHtml(v) { return String(v??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[c])); }
function invoiceCategoryLabel(c) { return ({DICH_VU_SPA:"Dịch vụ",THUC_AN:"Thức ăn",DO_DUNG:"Đồ dùng",THUOC:"Thuốc",VAC_XIN:"Vắc-xin",PHI_KHAM:"Phí khám",PHAU_THUAT:"Phẫu thuật",LUU_TRU:"Lưu trú"}[c]||c); }

document.addEventListener("DOMContentLoaded", async function() {
    await initPage();
    await Promise.all([loadPaymentPets(),loadInvoices()]);
    document.getElementById("paymentPetSearch").addEventListener("change",selectPaymentPetFromSearch);
    document.getElementById("paymentPetSearch").addEventListener("input",function(){
        const v=this.value.trim().toLowerCase();
        const p=paymentPets.find(x=>(x.mathucung||"").toLowerCase()===v||(x.name||"").toLowerCase()===v);
        if(p) choosePaymentPet(p.id||p.pet_id);
    });
    document.getElementById("extraCategory").addEventListener("change",renderExtraItemOptions);
    document.getElementById("extraItem").addEventListener("change",updateExtraUnitPrice);
    document.getElementById("extraQuantity").addEventListener("input",recalculatePayment);
    document.getElementById("btnAddExtraItem").addEventListener("click",addExtraCharge);
    document.getElementById("btnFinishExtra").addEventListener("click",function(){ document.getElementById("finalPaymentSection").classList.remove("d-none"); recalculatePayment(); });
    document.getElementById("btnPaymentSuccess").addEventListener("click",submitUnifiedPayment);
});
async function loadPaymentPets() {
    paymentPets=invoiceList(await apiRequest("/pets"));
    document.getElementById("paymentPetSuggestions").innerHTML=paymentPets.map(p=>'<option value="'+escapeInvoiceHtml(p.mathucung)+'">'+escapeInvoiceHtml(p.name||"")+'</option>').join("");
}
function selectPaymentPetFromSearch() {
    const v=document.getElementById("paymentPetSearch").value.trim().toLowerCase();
    const p=paymentPets.find(x=>(x.mathucung||"").toLowerCase()===v||(x.name||"").toLowerCase()===v||((x.mathucung||"")+" - "+(x.name||"")).toLowerCase()===v);
    if(p) choosePaymentPet(p.id||p.pet_id);
    else document.getElementById("paymentPetInfo").textContent="Hãy chọn đúng thú cưng từ danh sách gợi ý.";
}
async function choosePaymentPet(id) {
    if(Number(selectedPaymentPetId)===Number(id)&&paymentPreview)return;
    selectedPaymentPetId=Number(id); extraChargeLines=[];
    document.getElementById("unifiedPaymentResult").innerHTML="";
    try {
        paymentPreview=invoiceUnwrap(await apiRequest("/payment/pets/"+selectedPaymentPetId+"/preview"));
        const p=paymentPreview.pet||{};
        document.getElementById("paymentPetSearch").value=(p.mathucung||"")+" - "+(p.ten_thucung||"");
        document.getElementById("paymentOwnerName").value=p.ten_chu_nuoi||"";
        document.getElementById("paymentPetInfo").innerHTML="<strong>"+escapeInvoiceHtml(p.mathucung)+" - "+escapeInvoiceHtml(p.ten_thucung)+"</strong> · Trạng thái: "+escapeInvoiceHtml(p.trangthai||"-")+" · SĐT chủ: "+escapeInvoiceHtml(p.phone||"-");
        const inventoryResponse = await apiRequest("/inventory");
        paymentCatalog = invoiceList(inventoryResponse).filter(x => ["DICH_VU_SPA","THUC_AN","DO_DUNG"].includes(x.danhmuc) && (x.danhmuc === "DICH_VU_SPA" || Number(x.tong_ton_kho ?? x.soluongton ?? 0) > 0));
        renderMedicalCharges(paymentPreview.charges||{});
        renderExtraItemOptions();
        document.getElementById("medicalChargesSection").classList.toggle("d-none",medicalChargeLines.length===0);
        document.getElementById("extraServicesSection").classList.remove("d-none");
        document.getElementById("finalPaymentSection").classList.add("d-none");
        renderExtraCharges(); recalculatePayment();
    } catch(e) {
        paymentPreview=null; document.getElementById("paymentOwnerName").value="";
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
        name:"Lưu trú lồng "+(x.malong||"")+" - "+(x.ten_long||""),
        quantity:Number(x.days),price:Number(x.rate),unit:"ngày",
        note:"Từ "+(x.check_in||"")+" đến "+(x.check_out||"đang lưu trú")
    }));
    document.getElementById("medicalChargesBody").innerHTML=medicalChargeLines.length?medicalChargeLines.map(x=>"<tr><td>"+escapeInvoiceHtml(x.name)+"</td><td>"+x.quantity+" "+escapeInvoiceHtml(x.unit)+"</td><td>"+invoiceMoney(x.price)+"</td><td>"+invoiceMoney(x.quantity*x.price)+"</td></tr>").join(""):'<tr><td colspan="4" class="text-center text-muted">Không có chi phí khám hoặc điều trị chưa thanh toán.</td></tr>';
    document.getElementById("medicalChargesTotal").textContent=invoiceMoney(medicalChargeLines.reduce((s,x)=>s+x.quantity*x.price,0));
}
function renderExtraItemOptions() {
    const category=document.getElementById("extraCategory").value;
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
    const qty=Number(document.getElementById("extraQuantity").value);
    if(!item||!Number.isInteger(qty)||qty<1){alert("Vui lòng chọn mặt hàng và số lượng hợp lệ.");return;}
    const old=extraChargeLines.find(x=>x.item_id===id);
    if(old)old.quantity+=qty;else extraChargeLines.push({item_id:id,name:item.ten_sanpham,category:item.danhmuc,quantity:qty,price:Number(item.dongia),unit:item.donvitinh||""});
    document.getElementById("extraQuantity").value=1; renderExtraCharges(); recalculatePayment();
}
function renderExtraCharges() {
    const body=document.getElementById("extraItemsBody");
    body.innerHTML=extraChargeLines.length?extraChargeLines.map((x,i)=>'<tr><td>'+invoiceCategoryLabel(x.category)+'</td><td>'+escapeInvoiceHtml(x.name)+'</td><td>'+x.quantity+' '+escapeInvoiceHtml(x.unit)+'</td><td>'+invoiceMoney(x.price)+'</td><td>'+invoiceMoney(x.price*x.quantity)+'</td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExtraCharge('+i+')">Xóa</button></td></tr>').join(""):'<tr><td colspan="6" class="text-center text-muted">Chưa chọn dịch vụ ngoài khám.</td></tr>';
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
        document.getElementById("unifiedPaymentResult").innerHTML='<div class="alert alert-success">Đã lập hóa đơn <strong>'+escapeInvoiceHtml(data.mahoadon)+'</strong> · Tổng tiền: <strong>'+invoiceMoney(data.tong_thanhtoan)+'</strong> <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="printInvoice('+Number(data.invoice_id)+')">In hóa đơn</button></div>';
        selectedPaymentPetId=null;paymentPreview=null;
        document.getElementById("paymentPetSearch").value="";document.getElementById("paymentOwnerName").value="";
        document.getElementById("paymentPetInfo").textContent="Chưa chọn hồ sơ thú cưng.";
        ["medicalChargesSection","extraServicesSection","finalPaymentSection"].forEach(id=>document.getElementById(id).classList.add("d-none"));
        await loadInvoices();
    } catch(e){document.getElementById("unifiedPaymentResult").innerHTML='<div class="alert alert-danger">'+escapeInvoiceHtml(e.message)+'</div>';button.disabled=false;}
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
