@extends('layouts.app')

@section('title', 'Quản lý kho - Pet Clinic')
@section('page_title', 'Quản lý các kho hàng & Dịch vụ')

@section('content')
<div id="inventoryExpiryNotice" class="mb-3"></div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-primary">Kho thuốc & vắc-xin</strong>
        <button type="button" class="btn btn-primary btn-sm d-none inventoryAddButton" onclick="openInventoryForm(null, 'THUOC')"><i class="fas fa-plus mr-1"></i>Thêm thuốc / vắc-xin</button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr><th>Mã</th><th>Tên thuốc / vắc-xin</th><th>Số lô</th><th>Số lượng</th><th>Ngày sản xuất</th><th>Hạn sử dụng</th><th>Giá</th><th>Trạng thái</th><th class="inventoryActionHeader d-none">Thao tác</th></tr></thead>
            <tbody id="medicalInventoryTableBody"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-success">Kho thức ăn (quản lý theo lô và hạn sử dụng)</strong>
        <button type="button" class="btn btn-success btn-sm d-none inventoryAddButton" onclick="openInventoryForm(null, 'THUC_AN')"><i class="fas fa-plus mr-1"></i>Thêm thức ăn</button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr><th>Mã</th><th>Tên thức ăn</th><th>Số lô</th><th>Số lượng</th><th>Ngày sản xuất</th><th>Hạn sử dụng</th><th>Giá</th><th>Trạng thái</th><th class="inventoryActionHeader d-none">Thao tác</th></tr></thead>
            <tbody id="foodInventoryTableBody"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-warning">Kho đồ dùng</strong>
        <button type="button" class="btn btn-warning btn-sm d-none inventoryAddButton" onclick="openInventoryForm(null, 'DO_DUNG')"><i class="fas fa-plus mr-1"></i>Thêm đồ dùng</button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light"><tr><th>Mã</th><th>Tên đồ dùng</th><th>Số lượng tồn</th><th>Giá bán</th><th class="inventoryActionHeader d-none">Thao tác</th></tr></thead>
            <tbody id="goodsInventoryTableBody"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong class="text-info">Kho dịch vụ Spa & Grooming (thanh toán riêng)</strong>
        <button type="button" id="btnAddSpaService" class="btn btn-info btn-sm d-none" onclick="openSpaServiceForm()">
            <i class="fas fa-plus mr-1"></i> Thêm dịch vụ
        </button>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Mã DV</th>
                    <th>Tên gói dịch vụ</th>
                    <th>Giá dịch vụ</th>
                    <th id="spaActionHeader" class="d-none">Thao tác</th>
                </tr>
            </thead>
            <tbody id="spaTableBody"></tbody>
        </table>
    </div>
</div>


<div class="modal fade" id="spaServiceFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="spaServiceForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="spaServiceFormTitle">Thêm dịch vụ Spa</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="spa_service_form_id">
                    <div class="form-group">
                        <label for="spa_service_code">Mã dịch vụ</label>
                        <input id="spa_service_code" class="form-control" readonly placeholder="Mã sẽ hiển thị tại đây">
                        <small class="form-text text-muted">Mã dịch vụ được tạo trước và sẽ được lưu đúng như mã đang hiển thị.</small>
                    </div>
                    <div class="form-group">
                        <label for="spa_service_name">Tên dịch vụ</label>
                        <input id="spa_service_name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label for="spa_service_price">Giá (nghìn đồng)</label>
                        <input id="spa_service_price" type="number" min="0" step="0.01" class="form-control" placeholder="Ví dụ: 10 = 10.000 VNĐ" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btnSaveSpaService" class="btn btn-info">Lưu dịch vụ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="inventoryForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="inventoryFormTitle">Thêm mặt hàng kho</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="inventory_form_id">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="inventory_code">Mã sản phẩm</label>
                            <input id="inventory_code" class="form-control" readonly placeholder="Mã sẽ hiển thị tại đây">
                            <small class="form-text text-muted">Mã sản phẩm được tạo trước và sẽ được lưu đúng như mã đang hiển thị.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inventory_name">Tên mặt hàng</label>
                            <input id="inventory_name" class="form-control" maxlength="100" required>
                        </div>
                        <div class="form-group col-md-6" id="inventoryCategoryGroup">
                            <label for="inventory_category">Danh mục</label>
                            <select id="inventory_category" class="form-control">
                                <option value="THUOC">Thuốc</option>
                                <option value="VAC_XIN">Vắc-xin</option>
                                <option value="THUC_AN">Thức ăn</option>
                                <option value="DO_DUNG">Đồ dùng</option>
                            </select>
                            <small class="form-text text-muted">Chỉ kho thuốc mới cần chọn danh mục thuốc hoặc vắc-xin.</small>
                        </div>
                        <div class="form-group col-md-6" id="inventoryUnitGroup">
                            <label for="inventory_unit">Đơn vị tính</label>
                            <input id="inventory_unit" class="form-control" maxlength="20" placeholder="Viên, lọ, kg...">
                        </div>
                        <div class="form-group col-md-6" id="inventoryStockGroup">
                            <label for="inventory_stock" id="inventoryStockLabel">Số lượng</label>
                            <input id="inventory_stock" type="number" class="form-control" min="0" step="1" value="0" required>
                            <small class="form-text text-muted" id="inventoryStockHelp">Nhập số lượng ban đầu của mặt hàng.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="inventory_price">Giá (nghìn đồng)</label>
                            <input id="inventory_price" type="number" class="form-control" min="0" step="0.01" placeholder="Ví dụ: 10 = 10.000 VNĐ" required>
                        </div>
                        <div class="form-group col-md-6 d-none" id="inventoryBatchFields">
                            <label for="inventory_lot_preview">Số lô tự động</label>
                            <input id="inventory_lot_preview" class="form-control" readonly>
                            <small class="form-text text-muted">Mã lô hiển thị tại đây sẽ được lưu đúng như vậy.</small>
                        </div>
                        <div class="form-group col-md-6 d-none" id="inventoryImportGroup">
                            <label for="inventory_import_date">Ngày nhập</label>
                            <input id="inventory_import_date" type="date" class="form-control">
                        </div>
                        <div class="form-group col-md-6 d-none" id="inventoryManufactureGroup">
                            <label for="inventory_manufacture">Ngày sản xuất</label>
                            <input id="inventory_manufacture" type="date" class="form-control">
                        </div>
                        <div class="form-group col-md-6 d-none" id="inventoryExpiryGroup">
                            <label for="inventory_expiry">Hạn sử dụng</label>
                            <input id="inventory_expiry" type="date" class="form-control">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" id="btnSaveInventory" class="btn btn-primary">Lưu mặt hàng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryBatchModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <form id="inventoryBatchForm">
            <div class="modal-header">
                <h5 class="modal-title">Nhập lô hàng</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="batch_item_id">
                <p class="text-muted mb-3" id="batch_item_label"></p>
                <div class="form-group">
                    <label for="batch_lot_preview">Số lô tự động</label>
                    <input id="batch_lot_preview" class="form-control" readonly>
                    <small class="form-text text-muted">Mã lô hiển thị tại đây sẽ được lưu đúng như vậy.</small>
                </div>
                <div class="form-group">
                    <label for="batch_quantity">Số lượng nhập</label>
                    <input id="batch_quantity" type="number" class="form-control" min="1" step="1" required>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="batch_import_date">Ngày nhập</label>
                        <input id="batch_import_date" type="date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="batch_manufacture_date">Ngày sản xuất</label>
                        <input id="batch_manufacture_date" type="date" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="batch_expiry_date">Hạn sử dụng</label>
                        <input id="batch_expiry_date" type="date" class="form-control" required>
                    </div>
                </div>
                <small class="text-muted">Thuốc, vắc-xin và thức ăn cần ngày sản xuất và hạn sử dụng. Hệ thống xuất theo FEFO.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" id="btnSaveInventoryBatch" class="btn btn-primary">Xác nhận nhập lô</button>
            </div>
        </form>
    </div></div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/inventory.js') }}"></script>
@endpush