<?php /* entries/customer_master_file/customer_master_file.php — Customer form, included by home.php */ ?>

<div id="customer-app" class="row g-4">

  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#customerSearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mt-2 g-4">

          <!-- Left column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="cu-ref" class="col-sm-4 col-form-label">Reference No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="cu-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-code" class="col-sm-4 col-form-label">Code</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-code" placeholder="Customer code" v-model="form.code" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-name" class="col-sm-4 col-form-label">Customer Name</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-name" placeholder="Customer name" v-model="form.customer_name" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Customer Type</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="cu-customer-type-ref" v-model="form.customer_type_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.customer_type_name" placeholder="Customer type…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#cuCustomerTypePickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearCustomerType" v-if="form.customer_type_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-contact-person" class="col-sm-4 col-form-label">Contact Person</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-contact-person" v-model="form.contact_person" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-phone" class="col-sm-4 col-form-label">Phone</label>
              <div class="col-sm-7">
                <input type="tel" class="form-control" id="cu-phone" placeholder="Phone number" v-model="form.phone" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-mobile" class="col-sm-4 col-form-label">Mobile</label>
              <div class="col-sm-7">
                <input type="tel" class="form-control" id="cu-mobile" placeholder="Mobile number" v-model="form.mobile" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-email" class="col-sm-4 col-form-label">Email</label>
              <div class="col-sm-8">
                <input type="email" class="form-control" id="cu-email" placeholder="customer@example.com" v-model="form.email" />
              </div>
            </div>
          </div>

          <!-- Right column -->
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="cu-status" class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-6">
                <select class="form-select" id="cu-status" v-model="form.record_status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-address" class="col-sm-4 col-form-label">Address</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="cu-address" rows="3" placeholder="Customer address" v-model="form.address"></textarea>
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-city" class="col-sm-4 col-form-label">City</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-city" v-model="form.city" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-country" class="col-sm-4 col-form-label">Country</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-country" v-model="form.country" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-tax-number" class="col-sm-4 col-form-label">Tax Number</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="cu-tax-number" v-model="form.tax_number" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-credit-limit" class="col-sm-4 col-form-label">Credit Limit</label>
              <div class="col-sm-8">
                <input type="number" step="0.01" class="form-control" id="cu-credit-limit" v-model.number="form.credit_limit" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-payment-terms-days" class="col-sm-4 col-form-label">Payment Terms (Days)</label>
              <div class="col-sm-8">
                <input type="number" class="form-control" id="cu-payment-terms-days" v-model.number="form.payment_terms_days" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="cu-remarks" class="col-sm-4 col-form-label">Remarks</label>
              <div class="col-sm-8">
                <textarea class="form-control" id="cu-remarks" rows="3" v-model="form.remarks"></textarea>
              </div>
            </div>
          </div>

        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Customer saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/customer_master_file_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/master_files/references/customer_master_file/customer_master_file.js"></script>
