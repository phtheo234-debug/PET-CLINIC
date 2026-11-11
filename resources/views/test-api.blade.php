<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quản lý bệnh viện thú cưng</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#17303a;--bg:#f1f5f2;--line:#dce5df;--brand:#1f7a5c;--mut:#5f7279;--bad:#b83a2e;color-scheme:light}
*{box-sizing:border-box}
body{margin:0;font:15px/1.5 "Be Vietnam Pro",system-ui,"Segoe UI",sans-serif;background:var(--bg);color:var(--ink);display:grid;grid-template-columns:232px 1fr;min-height:100vh}
aside{background:var(--ink);color:#c3d6cc;padding:20px 12px;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:4px}
.brand{font-weight:700;font-size:17px;color:#fff;padding:4px 10px 18px;line-height:1.3}
.brand small{display:block;font-weight:400;font-size:12.5px;color:#8fb0a2;margin-top:2px}
nav{display:flex;flex-direction:column;gap:2px}
nav button{background:none;border:0;font:inherit;text-align:left;padding:9px 12px;border-radius:7px;cursor:pointer;color:#c3d6cc;white-space:nowrap}
nav button:hover{background:#ffffff14}
nav button[aria-current=page]{background:var(--brand);color:#fff;font-weight:600}
.foot{margin-top:auto;font-size:12.5px;color:#8fb0a2;padding:10px}
.foot button{margin-top:6px;background:none;border:1px solid #4a6a5d;color:#c3d6cc;border-radius:6px;padding:4px 10px;font:inherit;cursor:pointer}
main{padding:26px 32px;min-width:0}
.top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px}
h1{margin:0;font-size:22px;font-weight:700}
h2{margin:0 0 12px;font-size:16px;font-weight:600}
.mut{color:var(--mut)}
.btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:7px;padding:8px 14px;font:inherit;font-weight:600;cursor:pointer}
.btn:hover{filter:brightness(.93)}
.btn.ghost{background:#fff;color:var(--ink);border-color:var(--line);padding:4px 10px;font-weight:500;font-size:13.5px}
.btn.del{color:var(--bad)}
:focus-visible{outline:2px solid #2a9d77;outline-offset:2px}
.card{background:#fff;border:1px solid var(--line);border-radius:10px}
.bar{padding:12px 14px;border-bottom:1px solid var(--line)}
.bar input{width:min(360px,100%)}
.scroll{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-weight:600;color:var(--mut);font-size:13px;padding:10px 14px;border-bottom:1px solid var(--line);white-space:nowrap}
td{padding:10px 14px;border-bottom:1px solid #edf2ee;vertical-align:top}
tr:last-child td{border-bottom:0}
td.act{white-space:nowrap;text-align:right}
.empty{padding:36px 16px;text-align:center;color:var(--mut);margin:0}
.pill{display:inline-block;padding:2px 10px;border-radius:99px;font-size:12.5px;font-weight:600;background:#e8eeea;color:#3d5148;white-space:nowrap}
.pill.good{background:#dcf0e5;color:#14583f}.pill.warn{background:#fbecd0;color:#80510a}.pill.bad{background:#f8dcd8;color:#8f2a20}.pill.info{background:#dce9f5;color:#1f4d78}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:24px}
.stat{padding:14px 16px}.stat b{display:block;font-size:26px;line-height:1.25}.stat span{color:var(--mut);font-size:13.5px}
.board{display:grid;grid-template-columns:repeat(auto-fill,minmax(176px,1fr));gap:12px;margin-bottom:8px}
.cage{--c:#9aaca3;display:flex;flex-direction:column;gap:3px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-left:6px solid var(--c);border-radius:4px 12px 12px 4px;padding:10px 12px;text-align:left;font:inherit;color:inherit;cursor:pointer;min-height:112px}
.cage:hover{border-color:var(--c)}
.cage b{font-size:19px;line-height:1.2}.cage .pet{font-weight:600}.cage .pill{margin-top:auto}
.cage.free{--c:#2e9e6f}.cage.busy{--c:#d08a12}.cage.clean{--c:#4d86bd}.cage.fix{--c:#b83a2e}
.legend{display:flex;gap:16px;flex-wrap:wrap;font-size:13px;color:var(--mut);margin:0 0 24px}
.legend i{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px;background:var(--c)}
input,select,textarea{font:inherit;padding:8px 10px;border:1px solid #c5d2ca;border-radius:7px;color:var(--ink);background:#fff;width:100%}
dialog{border:0;border-radius:14px;padding:0;width:min(580px,94vw);max-height:92vh;box-shadow:0 20px 60px #0006;color:var(--ink)}
dialog::backdrop{background:#10241c99}
dialog form{padding:22px}
.fg{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:grid;gap:4px;font-size:13px;font-weight:600;color:var(--mut)}
label.w{grid-column:1/-1}
.err{color:var(--bad);font-size:13.5px;margin:10px 0 0;min-height:1.2em}
.df{display:flex;justify-content:flex-end;gap:8px;margin-top:10px}
.df .btn.ghost{padding:8px 14px}
.toast{position:fixed;right:20px;bottom:20px;background:var(--ink);color:#fff;padding:10px 16px;border-radius:8px;z-index:9;max-width:360px}
.pre{padding:14px;overflow:auto;font-size:13px;margin:0 0 20px}
@media(max-width:820px){
 body{grid-template-columns:1fr}
 aside{position:static;height:auto;padding:8px;overflow-x:auto}
 .brand,.foot{display:none}
 nav{flex-direction:row}
 main{padding:16px}
 .fg{grid-template-columns:1fr}
}
</style>
</head>
<body>
<aside>
 <div class="brand">Bệnh viện thú cưng<small>Quản lý phòng khám</small></div>
 <nav id="nav"></nav>
 <div class="foot" id="foot"></div>
</aside>
<main id="app"></main>
<dialog id="dlg"></dialog>
<div class="toast" id="toast" hidden></div>

<script>
/* ===== Cấu hình kết nối Laravel =====
   API_BASE để trống = chế độ demo (dữ liệu lưu trong trình duyệt).
   Đã nối API Laravel: đăng nhập bằng tài khoản trong bảng users (token lưu trong sessionStorage). */
const CONFIG={API_BASE:'http://127.0.0.1:8000'};
let TOKEN='',ME={};try{TOKEN=sessionStorage.getItem('pk_token')||''}catch(e){}
const setToken=t=>{TOKEN=t||'';try{t?sessionStorage.setItem('pk_token',t):sessionStorage.removeItem('pk_token')}catch(e){}};

const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const vnd=n=>Number(n||0).toLocaleString('vi-VN')+' ₫';
const ago=n=>new Date(Date.now()-n*864e5).toISOString().slice(0,10);
const today=ago(0);
const nrm=s=>String(s).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d');

const O={
 gt:{DUC:'Đực',CAI:'Cái',CHUA_XAC_DINH:'Chưa xác định'},
 pt:{NGOAI_TRU:'Ngoại trú',NOI_TRU:'Nội trú',DA_XUAT_VIEN:'Đã xuất viện',CHUYEN_VIEN:'Chuyển viện'},
 lt:{THUONG:'Thường',CAP_CUU_ICU:'Cấp cứu ICU',CACH_LY:'Cách ly'},
 ct:{CON_TRONG:'Còn trống',DA_CO_THU:'Có thú',DANG_VE_SINH:'Đang vệ sinh',BAO_TRI:'Bảo trì'},
 rt:{DANG_DIEU_TRI:'Đang điều trị',HOAN_THANH:'Hoàn thành',CHUYEN_VIEN:'Chuyển viện'},
 dm:{THUOC:'Thuốc',VAC_XIN:'Vắc-xin',THUC_AN:'Thức ăn',VAT_TU:'Vật tư',DICH_VU_SPA:'Dịch vụ spa'},
 pm:{TIEN_MAT:'Tiền mặt',CHUYEN_KHOAN:'Chuyển khoản',THE:'Thẻ'},
 it:{DA_THANH_TOAN:'Đã thanh toán',CHO_THANH_TOAN:'Chờ thanh toán',DA_HUY:'Đã hủy'},
 vr:{NHAN_VIEN:'Nhân viên',BAC_SI:'Bác sĩ',QUAN_TRI:'Quản trị'},
 us:{HOAT_DONG:'Hoạt động',TAM_NGUNG:'Tạm ngưng',KHOA:'Khóa'}
};
const tone=v=>['CON_TRONG','DA_THANH_TOAN','HOAN_THANH','NGOAI_TRU','HOAT_DONG'].includes(v)?'good':['DA_CO_THU','DANG_DIEU_TRI','CHO_THANH_TOAN','TAM_NGUNG'].includes(v)?'warn':['DA_HUY','BAO_TRI','CAP_CUU_ICU','CACH_LY','CHUYEN_VIEN','KHOA'].includes(v)?'bad':['NOI_TRU','DANG_VE_SINH'].includes(v)?'info':'';

/* Thực thể: f = [cột DB, nhãn, kiểu (t text, n số, d ngày, a văn bản dài, s enum, r tham chiếu), tùy chọn, bắt buộc] */
const E={
 pets:{t:'Thú cưng',n:'Thêm thú cưng',id:'pet_id',list:['mathucung','ten_thucung','loai','giong','owner_id','cage_id','trangthai'],f:[
  ['mathucung','Mã thú cưng','t',,1],['ten_thucung','Tên thú cưng','t',,1],['loai','Loài (chó, mèo…)','t',,1],['giong','Giống','t'],['weight','Cân nặng (kg)','n'],
  ['owner_id','Chủ nuôi','r','owners',1],['gioitinh','Giới tính','s',O.gt],['ngaysinh','Ngày sinh','d'],['machip','Mã chip','t'],
  ['cage_id','Lồng nội trú','r','cages'],['trangthai','Trạng thái','s',O.pt]]},
 owners:{t:'Chủ nuôi',n:'Thêm chủ nuôi',id:'owner_id',list:['ho_ten','phone','diachi','email'],f:[
  ['ho_ten','Họ và tên','t',,1],['phone','Số điện thoại','t',,1],['diachi','Địa chỉ','t'],['email','Email','t']]},
 cages:{t:'Lồng nội trú',n:'Thêm lồng',id:'cage_id',list:['malong','ten_long','loailong','slot','trangthai'],f:[
  ['malong','Mã lồng','t',,1],['ten_long','Tên lồng','t',,1],['loailong','Loại lồng','s',O.lt],['succhua','Sức chứa','n',,1],['trangthai','Trạng thái','s',O.ct]]},
 medical_records:{t:'Hồ sơ khám',n:'Thêm hồ sơ khám',id:'record_id',list:['ngaykham','pet_id','chandoan','cannangkhi_kham','trangthai'],f:[
  ['pet_id','Thú cưng','r','pets',1],['ngaykham','Ngày khám','d'],['trieuchung','Triệu chứng','a'],['chandoan','Chẩn đoán','a',,1],
  ['cannangkhi_kham','Cân nặng khi khám (kg)','n',,1],['trangthai','Trạng thái','s',O.rt]]},
 inventory:{t:'Kho và dịch vụ',n:'Thêm sản phẩm',id:'item_id',list:['masanpham','ten_sanpham','danhmuc','donvitinh','dongia','tonkhotoithieu'],f:[
  ['masanpham','Mã sản phẩm','t',,1],['ten_sanpham','Tên sản phẩm','t',,1],['danhmuc','Danh mục','s',O.dm],['donvitinh','Đơn vị tính','t',,1],
  ['dongia','Đơn giá (₫)','n',,1],['tonkhotoithieu','Tồn kho tối thiểu','n']]},
 invoices:{t:'Hóa đơn',n:'Lập hóa đơn',id:'invoice_id',list:['mahoadon','ngaytao','pet_id','owner_id','tongtien','phuongthucthanhtoan','trangthai'],f:[
  ['mahoadon','Mã hóa đơn','t',,1],['pet_id','Thú cưng','r','pets',1],['owner_id','Chủ nuôi','r','owners',1],['tongtien','Tổng tiền (₫)','n',,1],
  ['phuongthucthanhtoan','Thanh toán','s',O.pm],['trangthai','Trạng thái','s',O.it]]}
};
E.users={t:'Nhân viên',n:'Thêm nhân viên',id:'user_id',list:['username','ho_ten','email','vaitro','trangthai'],f:[['username','Tên đăng nhập','t',,1],['ho_ten','Họ và tên','t',,1],['email','Email','t',,1],['phone','Số điện thoại','t'],['vaitro','Vai trò','s',O.vr],['mat_khau_tam','Mật khẩu tạm (từ 6 ký tự)','t',,1],['trangthai','Trạng thái','s',O.us]]};
const NAVAPI=[['dash','Tổng quan'],['pets','Thú cưng'],['boarding','Xếp lồng'],['export','Xuất kho'],['rx','Kê đơn'],['pay','Thanh toán'],['users','Nhân viên'],['pw','Đổi mật khẩu']];
const ACT={
 boarding:{t:'Xếp lồng nội trú',b:'Xếp lồng',ep:'boarding/assign',f:[['pet_id','Thú cưng','pet'],['cage_id','Mã lồng (ID)','n'],['check_in','Giờ nhận lồng','dt']]},
 export:{t:'Xuất kho theo hạn dùng (FEFO)',b:'Xuất kho',ep:'v1/inventory/export',f:[['medicine_id','Mã thuốc (ID)','n'],['required_qty','Số lượng cần xuất','n']]},
 rx:{t:'Kê đơn thuốc',b:'Kê đơn',ep:'v1/medical/prescriptions',f:[['pet_id','Thú cưng','pet'],['medical_record_id','Mã hồ sơ khám (ID)','n'],['medicine_id','Mã thuốc (ID)','n'],['doses_per_day','Số lần dùng mỗi ngày','n'],['total_days','Số ngày dùng','n']]},
 pay:{t:'Thanh toán',b:'Thanh toán',ep:'v1/payment/checkout',f:[['pet_id','Thú cưng','pet'],['payment_method','Hình thức thanh toán','s',O.pm]]},
 pw:{t:'Đổi mật khẩu',b:'Đổi mật khẩu',m:'PUT',ep:'v1/users/change-password',f:[['mat_khau_cu','Mật khẩu hiện tại','pw'],['mat_khau_moi','Mật khẩu mới (từ 6 ký tự)','pw']]}
};
if(CONFIG.API_BASE)Object.assign(E.pets,{id:'id',list:['name','species','breed','weight','owner_id'],f:[['name','Tên thú cưng','t',,1],['species','Loài (chó, mèo…)','t',,1],['breed','Giống','t'],['weight','Cân nặng (kg)','n',,1],['owner_id','Chủ nuôi','r','owners',1]]});
const EP={pets:'pets',users:'v1/admin/users'};
const NAV=[['dash','Tổng quan'],['pets','Thú cưng'],['owners','Chủ nuôi'],['cages','Lồng nội trú'],['medical_records','Hồ sơ khám'],['inventory','Kho và dịch vụ'],['invoices','Hóa đơn']];
const HD={ngaytao:'Ngày lập',slot:'Đang ở'};
const RL={owners:r=>r.ho_ten,pets:r=>`${r.ten_thucung} (${r.mathucung})`,cages:r=>`${r.malong} - ${r.ten_long}`};
const dep={owners:[['pets','owner_id'],['invoices','owner_id']],pets:[['medical_records','pet_id'],['invoices','pet_id']],cages:[['pets','cage_id']]};

/* ===== Dữ liệu mẫu ===== */
const mk=(ks,rs)=>rs.map(r=>Object.fromEntries(ks.map((k,i)=>[k,r[i]])));
function seed(){return{
 owners:mk(['owner_id','ho_ten','phone','diachi','email'],[
  [1,'Nguyễn Thị Lan','0912345678','Từ Sơn, Bắc Ninh','lan.nguyen@mail.com'],[2,'Trần Văn Minh','0987654321','Đông Anh, Hà Nội',''],
  [3,'Lê Hoàng Anh','0905112233','Yên Phong, Bắc Ninh',''],[4,'Phạm Thu Hà','0933445566','Long Biên, Hà Nội','ha.pham@mail.com']]),
 cages:mk(['cage_id','malong','ten_long','loailong','succhua','soluonghientai','trangthai'],[
  [1,'L01','Lồng cún nhỏ A1','THUONG',1,0,'CON_TRONG'],[2,'L02','Lồng cún nhỏ A2','THUONG',1,0,'CON_TRONG'],
  [3,'L03','Lồng mèo B1','THUONG',1,0,'CON_TRONG'],[4,'L04','Lồng mèo B2','THUONG',1,0,'CON_TRONG'],
  [5,'L05','ICU 1','CAP_CUU_ICU',1,0,'CON_TRONG'],[6,'L06','Phòng cách ly 1','CACH_LY',2,0,'DANG_VE_SINH'],
  [7,'L07','Lồng cún lớn C1','THUONG',2,0,'CON_TRONG'],[8,'L08','Lồng cún lớn C2','THUONG',2,0,'BAO_TRI']]),
 pets:mk(['pet_id','mathucung','ten_thucung','loai','giong','gioitinh','owner_id','cage_id','trangthai'],[
  [1,'TC001','Mimi','Mèo','Mèo Anh','CAI',1,3,'NOI_TRU'],[2,'TC002','Lu','Chó','Poodle','DUC',2,1,'NOI_TRU'],
  [3,'TC003','Bông','Chó','Corgi','CAI',3,null,'NGOAI_TRU'],[4,'TC004','Mun','Mèo','Mèo ta','DUC',1,5,'NOI_TRU'],
  [5,'TC005','Kiki','Chó','Husky','DUC',4,null,'NGOAI_TRU']]),
 medical_records:mk(['record_id','pet_id','doctor_id','ngaykham','trieuchung','chandoan','cannangkhi_kham','trangthai'],[
  [1,1,1,ago(2),'Bỏ ăn, nôn hai ngày','Viêm dạ dày ruột',3.4,'DANG_DIEU_TRI'],
  [2,2,1,ago(1),'Ngứa, rụng lông quanh tai','Viêm da do ve',6.1,'HOAN_THANH'],
  [3,4,1,today,'Thở khò khè, mệt','Viêm phổi',4.2,'DANG_DIEU_TRI']]),
 inventory:mk(['item_id','masanpham','ten_sanpham','danhmuc','donvitinh','dongia','tonkhotoithieu'],[
  [1,'SP001','Amoxicillin 250mg','THUOC','viên',8000,200],[2,'SP002','Vắc-xin 7 bệnh cho chó','VAC_XIN','liều',180000,20],
  [3,'SP003','Hạt Royal Canin 2kg','THUC_AN','gói',320000,10],[4,'SP004','Tắm spa chó nhỏ','DICH_VU_SPA','lần',150000,0],
  [5,'SP005','Bơm tiêm 3ml','VAT_TU','cái',2000,100]]),
 invoices:mk(['invoice_id','mahoadon','pet_id','owner_id','tongtien','phuongthucthanhtoan','trangthai','ngaytao'],[
  [1,'HD001',2,2,330000,'TIEN_MAT','DA_THANH_TOAN',today],[2,'HD002',1,1,480000,'TIEN_MAT','CHO_THANH_TOAN',today],
  [3,'HD003',3,3,150000,'CHUYEN_KHOAN','DA_THANH_TOAN',today]])
}}

/* ===== Lớp dữ liệu: demo (localStorage) hoặc API Laravel ===== */
const KEY='pkthucung_demo_v1';
let DB;try{DB=JSON.parse(localStorage.getItem(KEY))}catch(e){}
DB=CONFIG.API_BASE?{pets:[],owners:[],cages:[],medical_records:[],inventory:[],invoices:[],users:[]}:(DB||seed());
const persist=()=>{try{localStorage.setItem(KEY,JSON.stringify(DB))}catch(e){}};
async function call(m,path,body){
 const r=await fetch(CONFIG.API_BASE+'/api/'+path,{method:m,headers:{'Content-Type':'application/json',Accept:'application/json',...(TOKEN&&{Authorization:'Bearer '+TOKEN})},body:body&&JSON.stringify(body)});
 if(!r.ok){const j=await r.json().catch(()=>({}));if(r.status==401&&path!='v1/auth/login'){setToken('');render()}const f=j.errors&&Object.values(j.errors)[0];throw new Error((f&&f[0])||j.message||j.error||'Lỗi '+r.status)}
 return r.status==204?null:r.json().catch(()=>null);
}
const rows=j=>Array.isArray(j)?j:Array.isArray(j?.data)?j.data:[];
const toApi=(k,r)=>r;
async function load(){
 if(!CONFIG.API_BASE||!TOKEN)return;
 DB.pets=rows(await call('GET','pets'));
 if(view=='users'&&TOKEN)DB.users=rows(await call('GET','v1/admin/users'));
}
function sync(){DB.cages.forEach(c=>{const n=DB.pets.filter(p=>p.cage_id==c.cage_id&&p.trangthai=='NOI_TRU').length;c.soluonghientai=n;if(!['BAO_TRI','DANG_VE_SINH'].includes(c.trangthai))c.trangthai=n?'DA_CO_THU':'CON_TRONG'})}
async function put(k,row){const id=E[k].id;
 if(CONFIG.API_BASE){const b={...toApi(k,row)},ep=EP[k]||k;delete b[id];row[id]?await call('PUT',`${ep}/${row[id]}`,b):await call('POST',ep,b);return load()}
 if(row[id]){const i=DB[k].findIndex(x=>x[id]==row[id]);DB[k][i]={...DB[k][i],...row}}
 else{row[id]=Math.max(0,...DB[k].map(x=>x[id]))+1;DB[k].push(row)}
 sync();persist();
}
async function drop(k,id){
 if(CONFIG.API_BASE){await call('DELETE',`${EP[k]||k}/${id}`);return load()}
 DB[k]=DB[k].filter(x=>x[E[k].id]!=id);sync();persist();
}
function check(k,r){
 if(k=='pets'&&r.cage_id){
  const c=DB.cages.find(x=>x.cage_id==r.cage_id);
  if(['BAO_TRI','DANG_VE_SINH'].includes(c.trangthai))return `Lồng ${c.malong} đang ở trạng thái “${O.ct[c.trangthai].toLowerCase()}”. Hãy chọn lồng khác.`;
  const n=DB.pets.filter(p=>p.cage_id==c.cage_id&&p.trangthai=='NOI_TRU'&&p.pet_id!=r.pet_id).length;
  if(n>=c.succhua)return `Lồng ${c.malong} đã đủ ${c.succhua} thú. Hãy chọn lồng còn chỗ.`;
 }
}

/* ===== Hiển thị ===== */
const lab=(k,id)=>{const r=DB[k].find(x=>x[E[k].id]==id);return r?RL[k](r):CONFIG.API_BASE?'#'+id:'-'};
const hd=(k,c)=>(E[k].f.find(x=>x[0]==c)||[,HD[c]])[1];
function cell(k,key,row){
 const v=row[key],def=E[k].f.find(x=>x[0]==key);
 if(key=='slot')return `${row.soluonghientai}/${row.succhua}`;
 if(!def)return esc(String(v).slice(0,10));
 if(def[2]=='s')return `<span class="pill ${tone(v)}">${esc(def[3][v]||v)}</span>`;
 if(def[2]=='r')return v?esc((key=='owner_id'&&(row.owner?.name||row.owner?.ho_ten))||lab(def[3],v)):'<span class="mut">-</span>';
 if(key=='dongia'||key=='tongtien')return vnd(v);
 if(key=='cannangkhi_kham'||key=='weight')return esc(v)+' kg';
 return esc(v);
}
let view='dash',q='';
function render(){
 if(CONFIG.API_BASE&&!TOKEN){$('#nav').innerHTML='';$('#foot').innerHTML='';return login()}
 $('#nav').innerHTML=(CONFIG.API_BASE?NAVAPI:NAV).map(([k,l])=>`<button data-v="${k}" ${k==view?'aria-current="page"':''}>${l}</button>`).join('');
 $('#foot').innerHTML=CONFIG.API_BASE?`${esc(ME.ho_ten||ME.username||'')}<br>Đang kết nối ${esc(CONFIG.API_BASE)}<br><button id="logout">Đăng xuất</button>`:'Chế độ demo: dữ liệu lưu trong trình duyệt.<br><button id="reset">Đặt lại dữ liệu mẫu</button>';
 if(view=='dash')return CONFIG.API_BASE?apiDash():dash();
 if(ACT[view])return action(view);
 const e=E[view];
 $('#app').innerHTML=`<div class="top"><h1>${e.t}</h1><button class="btn" data-add="${view}">${e.n}</button></div>
 <div class="card"><div class="bar"><input id="q" type="search" placeholder="Tìm trong ${e.t.toLowerCase()}" aria-label="Tìm kiếm" value="${esc(q)}"></div><div class="scroll" id="tb"></div></div>`;
 table();
}
function table(){
 const k=view,e=E[k],cols=e.list;
 const rows=[...DB[k]].reverse().filter(r=>!q||nrm(cols.map(c=>cell(k,c,r)).join(' ').replace(/<[^>]+>/g,' ')).includes(nrm(q)));
 $('#tb').innerHTML=rows.length?`<table><thead><tr>${cols.map(c=>`<th>${hd(k,c)}</th>`).join('')}<th></th></tr></thead><tbody>${rows.map(r=>`<tr>${cols.map(c=>`<td>${cell(k,c,r)}</td>`).join('')}<td class="act">${k=='users'?`<button class="btn ghost" data-st="${r.user_id??r.id}:${r.trangthai=='KHOA'?'HOAT_DONG':'KHOA'}">${r.trangthai=='KHOA'?'Mở khóa':'Khóa'}</button> <button class="btn ghost" data-rp="${r.user_id??r.id}">Cấp lại mật khẩu</button>`:`<button class="btn ghost" data-e="${r[e.id]}">Sửa</button> <button class="btn ghost del" data-d="${r[e.id]}">Xóa</button>`}</td></tr>`).join('')}</tbody></table>`
 :`<p class="empty">${q?'Không tìm thấy kết quả phù hợp.':`Chưa có dữ liệu. Chọn “${e.n}” để tạo bản ghi đầu tiên.`}</p>`;
}
function dash(){
 const noi=DB.pets.filter(p=>p.trangthai=='NOI_TRU').length,trong=DB.cages.filter(c=>c.trangthai=='CON_TRONG').length;
 const dt=DB.medical_records.filter(r=>r.trangthai=='DANG_DIEU_TRI');
 const dtoan=DB.invoices.filter(i=>i.trangthai=='DA_THANH_TOAN'&&String(i.ngaytao).slice(0,10)==today).reduce((s,i)=>s+Number(i.tongtien),0);
 const cls={CON_TRONG:'free',DA_CO_THU:'busy',DANG_VE_SINH:'clean',BAO_TRI:'fix'};
 $('#app').innerHTML=`<div class="top"><h1>Tổng quan hôm nay</h1><button class="btn" data-add="pets">Tiếp nhận thú cưng</button></div>
 <div class="stats">
  <div class="card stat"><b>${noi}</b><span>Thú đang nội trú</span></div>
  <div class="card stat"><b>${trong}/${DB.cages.length}</b><span>Lồng còn trống</span></div>
  <div class="card stat"><b>${dt.length}</b><span>Ca đang điều trị</span></div>
  <div class="card stat"><b>${vnd(dtoan)}</b><span>Đã thu hôm nay</span></div>
 </div>
 <h2>Sơ đồ lồng nội trú</h2>
 <div class="board">${DB.cages.map(c=>{const ps=DB.pets.filter(p=>p.cage_id==c.cage_id&&p.trangthai=='NOI_TRU');
  return `<button class="cage ${cls[c.trangthai]}" data-cage="${c.cage_id}"><b>${esc(c.malong)}</b><span class="mut">${esc(c.ten_long)}</span><span class="pet">${ps.length?ps.map(p=>esc(p.ten_thucung)).join(', '):`<span class="mut">${O.ct[c.trangthai]}</span>`}</span><span class="pill ${tone(c.loailong)}">${O.lt[c.loailong]}</span></button>`}).join('')}</div>
 <p class="legend"><span><i style="--c:#2e9e6f"></i>Còn trống</span><span><i style="--c:#d08a12"></i>Có thú</span><span><i style="--c:#4d86bd"></i>Đang vệ sinh</span><span><i style="--c:#b83a2e"></i>Bảo trì</span></p>
 <h2>Ca đang điều trị</h2>
 <div class="card scroll">${dt.length?`<table><thead><tr><th>Thú cưng</th><th>Chẩn đoán</th><th>Ngày khám</th></tr></thead><tbody>${dt.map(r=>`<tr><td>${esc(lab('pets',r.pet_id))}</td><td>${esc(r.chandoan)}</td><td>${esc(String(r.ngaykham).slice(0,10))}</td></tr>`).join('')}</tbody></table>`:'<p class="empty">Hiện không có ca nào đang điều trị.</p>'}</div>`;
}

/* ===== Màn hình theo API Laravel ===== */
function login(){
 $('#app').innerHTML=`<form id="lf" class="card" style="max-width:380px;margin:12vh auto 0;padding:26px"><h1 style="margin-bottom:16px">Đăng nhập</h1>
 <div class="fg" style="grid-template-columns:1fr"><label>Tên đăng nhập<input name="username" autocomplete="username" required autofocus></label>
 <label>Mật khẩu<input name="password" type="password" autocomplete="current-password" required></label></div>
 <p class="err" id="lerr" role="alert"></p><div class="df"><button class="btn">Đăng nhập</button></div></form>`;
 $('#lf').onsubmit=async ev=>{ev.preventDefault();const b=Object.fromEntries(new FormData(ev.target));
  try{const j=await call('POST','v1/auth/login',{username:b.username,mat_khau:b.password});const d=j?.data||j||{};
   const t=d.token||d.access_token||j?.token||j?.access_token;
   if(!t)throw new Error('Phản hồi đăng nhập không có token.');
   setToken(t);ME=d.user||{};if(d.phai_doimatkhau)view='pw';
   try{const m=await call('GET','v1/users/me');ME=m?.data||m||ME;await load()}catch(x){}
   render()}
  catch(x){$('#lerr').textContent=x.message}};
}
async function apiDash(){
 $('#app').innerHTML='<div class="top"><h1>Tổng quan hôm nay</h1></div><p class="mut">Đang tải…</p>';
 try{const j=await call('GET','v1/analytics/dashboard'),d=j?.data||{};
  const T=[['so_thu_noi_tru','Thú đang nội trú'],['so_long_trong','Lồng còn trống'],['so_ca_kham_ngay','Ca khám hôm nay'],['doanh_thu_ngay','Doanh thu hôm nay']];
  $('#app').innerHTML=`<div class="top"><h1>Tổng quan hôm nay</h1></div><div class="stats">${T.map(([k,l])=>`<div class="card stat"><b>${k=='doanh_thu_ngay'?vnd(d[k]):esc(d[k]??0)}</b><span>${l}</span></div>`).join('')}</div>`}
 catch(x){$('#app').innerHTML=`<div class="top"><h1>Tổng quan hôm nay</h1></div><p class="err">${esc(x.message)}</p>`}
}
function action(k){
 const a=ACT[k],now=new Date(Date.now()-new Date().getTimezoneOffset()*6e4).toISOString().slice(0,16);
 const inp=([n,l,t,x])=>`<label>${l}${t=='pet'?`<select name="${n}" required><option value="">Chọn…</option>${DB.pets.map(p=>`<option value="${p.id}">${esc(p.name)} (#${p.id})</option>`).join('')}</select>`
  :t=='s'?`<select name="${n}">${Object.entries(x).map(([v,w])=>`<option value="${v}">${w}</option>`).join('')}</select>`
  :`<input name="${n}" type="${t=='dt'?'datetime-local':t=='pw'?'password':'number'}" ${t=='dt'?`value="${now}"`:t=='n'?'min="1"':'autocomplete="off"'} required>`}</label>`;
 $('#app').innerHTML=`<div class="top"><h1>${a.t}</h1></div><form id="af" class="card" style="max-width:540px;padding:20px"><div class="fg">${a.f.map(inp).join('')}</div><p class="err" id="aerr" role="alert"></p><div class="df"><button class="btn">${a.b}</button></div></form><div id="ares"></div>`;
 $('#af').onsubmit=async ev=>{ev.preventDefault();const b=Object.fromEntries(new FormData(ev.target));
  a.f.forEach(([n,,t])=>{if(t=='pet'||t=='n')b[n]=+b[n];if(t=='dt')b[n]=b[n].replace('T',' ')+':00'});
  try{const j=await call(a.m||'POST',a.ep,b);toast(j?.message||'Thành công');$('#aerr').textContent='';
   const r=j?.data??(j?.invoice_id?{invoice_id:j.invoice_id}:null);
   $('#ares').innerHTML=r?`<h2 style="margin-top:20px">Kết quả</h2><pre class="card pre">${esc(JSON.stringify(r,null,2))}</pre>`:'';
   if(k=='pw')ev.target.reset();await load().catch(()=>{})}
  catch(x){$('#aerr').textContent=x.message}};
}
async function setStatus(s){const [id,st]=s.split(':');
 try{await call('PUT',`v1/admin/users/${id}/status`,{trangthai:st});toast('Đã cập nhật trạng thái');await load();render()}catch(x){toast(x.message)}}
async function resetPw(id){
 const pw=prompt('Nhập mật khẩu tạm mới (ít nhất 6 ký tự):');if(!pw)return;
 try{const j=await call('PUT',`v1/admin/users/${id}/reset-password`,{mat_khau_tam:pw});toast(j?.message||'Đã cấp lại mật khẩu')}catch(x){toast(x.message)}}

/* ===== Form thêm / sửa ===== */
const nx=(p,k)=>p+String(Math.max(0,...DB[k].map(x=>x[E[k].id]))+1).padStart(3,'0');
function openForm(k,row){
 const e=E[k],isNew=!row;row=row||{};
 const hide=CONFIG.API_BASE?{pets:['cage_id','trangthai','mathucung'],users:['trangthai']}[k]||[]:[];
 const dv={mathucung:nx('TC','pets'),malong:nx('L','cages'),masanpham:nx('SP','inventory'),mahoadon:nx('HD','invoices'),ngaykham:today,succhua:1,tonkhotoithieu:10};
 const fld=([key,l,t,x,req])=>{
  const v=row[key]??(isNew?dv[key]:'')??'';let inp;
  if(t=='s')inp=`<select name="${key}">${Object.entries(x).map(([a,b])=>`<option value="${a}" ${v==a?'selected':''}>${b}</option>`).join('')}</select>`;
  else if(t=='r'&&CONFIG.API_BASE&&!DB[x].length)inp=`<input name="${key}" type="number" min="1" placeholder="Nhập mã (ID)" value="${esc(v)}" ${req?'required':''}>`;
  else if(t=='r')inp=`<select name="${key}" ${req?'required':''}><option value="">${req?'Chọn…':'Không chọn'}</option>${DB[x].map(r=>{const id=r[E[x].id];return `<option value="${id}" ${v==id?'selected':''}>${esc(RL[x](r))}</option>`}).join('')}</select>`;
  else if(t=='a')inp=`<textarea name="${key}" rows="3" ${req?'required':''}>${esc(v)}</textarea>`;
  else inp=`<input name="${key}" type="${t=='n'?'number':t=='d'?'date':'text'}" ${t=='n'?'step="any" min="0"':''} value="${esc(String(v).slice(0,10)==v&&t=='d'?v:v)}" ${req?'required':''}>`;
  return `<label class="${t=='a'?'w':''}">${l}${inp}</label>`;
 };
 const d=$('#dlg');
 d.innerHTML=`<form id="frm"><h2>${isNew?'Thêm':'Sửa'} ${e.t.toLowerCase()}</h2><div class="fg">${e.f.filter(f=>!hide.includes(f[0])).map(fld).join('')}</div><p class="err" id="err" role="alert"></p><div class="df"><button type="button" class="btn ghost" data-close>Hủy</button><button class="btn">Lưu</button></div></form>`;
 d.showModal();
 $('#frm').onsubmit=async ev=>{
  ev.preventDefault();
  const out=Object.fromEntries(new FormData(ev.target));
  e.f.forEach(([key,,t])=>{const v=out[key];if(v===undefined)return;if(t=='n'||t=='r')out[key]=v===''?null:+v;else if(t=='d'&&!v)out[key]=null});
  if(!isNew)out[e.id]=row[e.id];
  else{if(k=='invoices')out.ngaytao=today;if(k=='medical_records')out.doctor_id=1}
  if(k=='pets'&&out.cage_id){if(out.trangthai=='NGOAI_TRU')out.trangthai='NOI_TRU';else if(out.trangthai!='NOI_TRU')out.cage_id=null}
  const er=$('#err'),bad=check(k,out);
  if(bad){er.textContent=bad;return}
  try{await put(k,out);d.close();toast('Đã lưu');render()}catch(x){er.textContent=x.message}
 };
}
async function remove(k,id){
 for(const [dk,f] of dep[k]||[])if(DB[dk].some(r=>r[f]==id))return toast(`Không thể xóa: còn dữ liệu liên quan trong “${E[dk].t}”.`);
 if(!confirm('Bạn chắc chắn muốn xóa bản ghi này?'))return;
 try{await drop(k,id);toast('Đã xóa');render()}catch(x){toast(x.message)}
}
let tt;function toast(m,ms=2600){const t=$('#toast');t.textContent=m;t.hidden=false;clearTimeout(tt);tt=setTimeout(()=>t.hidden=true,ms)}

document.addEventListener('click',ev=>{
 const t=ev.target.closest('button');if(!t)return;
 if(t.dataset.v){view=t.dataset.v;q='';(CONFIG.API_BASE?load():Promise.resolve()).catch(x=>toast(x.message)).then(render)}
 else if(t.dataset.add)openForm(t.dataset.add);
 else if(t.dataset.e)openForm(view,DB[view].find(r=>r[E[view].id]==t.dataset.e));
 else if(t.dataset.d)remove(view,t.dataset.d);
 else if(t.dataset.cage)openForm('cages',DB.cages.find(c=>c.cage_id==t.dataset.cage));
 else if(t.id=='reset'){if(confirm('Đặt lại toàn bộ dữ liệu mẫu?')){DB=seed();sync();persist();render()}}
 else if(t.id=='logout'){call('POST','v1/auth/logout').catch(()=>{}).then(()=>{setToken('');ME={};view='dash';render()})}
 else if(t.dataset.st)setStatus(t.dataset.st);
 else if(t.dataset.rp)resetPw(t.dataset.rp);
 else if(t.dataset.close!==undefined)$('#dlg').close();
});
document.addEventListener('input',ev=>{if(ev.target.id=='q'){q=ev.target.value;table()}});
document.addEventListener('change',ev=>{
 const t=ev.target;
 if(t.name=='pet_id'&&t.form&&t.form.owner_id){const p=DB.pets.find(x=>x.pet_id==t.value);if(p)t.form.owner_id.value=p.owner_id}
});

if(!CONFIG.API_BASE)sync();
(async()=>{try{if(CONFIG.API_BASE&&TOKEN){const m=await call('GET','v1/users/me');ME=m?.data||m||{}}await load()}catch(x){toast('Không tải được dữ liệu: '+x.message)}render()})();
</script>
</body>
</html>
