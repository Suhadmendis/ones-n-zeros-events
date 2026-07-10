<?php /* modules/operations/quotations/quotation_entries/quotation_entries.php — header + line-items quotation form */ ?>

<div id="quotations-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-primary" @click="onAdd">New</button>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#quotationsSearchModal">Search</button>
            <button type="button" class="btn btn-info" @click="onPrint">Print</button>
            <button type="button" class="btn btn-warning" @click="onCancel">Cancel</button>
            <button type="button" class="btn btn-danger" @click="onClose">Close</button>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <div class="row mt-2 g-4">
          <div class="col-md-6">
            <div class="row mb-3">
              <label for="quotations-ref" class="col-sm-4 col-form-label">Quotation No.</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="quotations-ref" v-model="form.ref" disabled />
                  <span v-if="loading" class="input-group-text"><span class="spinner-border spinner-border-sm"></span></span>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-revision-no" class="col-sm-4 col-form-label">Revision No.</label>
              <div class="col-sm-8">
                <input type="number" step="1" min="0" class="form-control" id="quotations-revision-no" v-model.number="form.revision_no" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Customer <span class="text-danger">*</span></label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="quotations-customer-ref" v-model="form.customer_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.customer_name" placeholder="Customer…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#qtCustomerPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearCustomer" v-if="form.customer_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-contact-person" class="col-sm-4 col-form-label">Contact Person</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-contact-person" v-model="form.contact_person" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-subject" class="col-sm-4 col-form-label">Subject / Title</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-subject" v-model="form.subject" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-customer-reference" class="col-sm-4 col-form-label">Customer Reference</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-customer-reference" v-model="form.customer_reference" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-quotation-date" class="col-sm-4 col-form-label">Quotation Date</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-quotation-date" v-flatpickr="dateTimePicker(form.quotation_date, (v) => form.quotation_date = v)" placeholder="Pick a date &amp; time…" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-valid-until" class="col-sm-4 col-form-label">Valid Until</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-valid-until" v-flatpickr="dateTimePicker(form.valid_until, (v) => form.valid_until = v)" placeholder="Pick a date &amp; time…" />
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Currency</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="quotations-currency-ref" v-model="form.currency_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.currency_name" placeholder="Currency…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#qtCurrencyPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearCurrency" v-if="form.currency_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Salesperson</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="quotations-salesperson-ref" v-model="form.salesperson_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.salesperson_name" placeholder="Prepared by…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#qtSalespersonPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearSalesperson" v-if="form.salesperson_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-price-list" class="col-sm-4 col-form-label">Price List</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-price-list" v-model="form.price_list" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-payment-terms" class="col-sm-4 col-form-label">Payment Terms</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-payment-terms" v-model="form.payment_terms" />
              </div>
            </div>
            <div class="row mb-3">
              <label for="quotations-delivery-period" class="col-sm-4 col-form-label">Delivery / Completion Period</label>
              <div class="col-sm-8">
                <input type="text" class="form-control" id="quotations-delivery-period" v-model="form.delivery_period" />
              </div>
            </div>
            <div class="row mb-3">
              <label class="col-sm-4 col-form-label">Status</label>
              <div class="col-sm-8">
                <div class="input-group">
                  <input type="text" class="form-control font-monospace" id="quotations-quotation-status-ref" v-model="form.quotation_status_ref" placeholder="Ref…" readonly style="max-width:150px" />
                  <input type="text" class="form-control" v-model="form.quotation_status_name" placeholder="Status…" readonly />
                  <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#qtStatusPickerModal">
                    <i class="bi bi-search"></i>
                  </button>
                  <button class="btn btn-outline-secondary" type="button" @click="clearStatus" v-if="form.quotation_status_ref">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-0">
          <div class="col-md-4">
            <label for="quotations-notes" class="form-label">Notes</label>
            <textarea class="form-control" id="quotations-notes" rows="3" v-model="form.notes"></textarea>
          </div>
          <div class="col-md-4">
            <label for="quotations-terms-conditions" class="form-label">Terms &amp; Conditions</label>
            <textarea class="form-control" id="quotations-terms-conditions" rows="3" v-model="form.terms_conditions"></textarea>
          </div>
          <div class="col-md-4">
            <label for="quotations-internal-notes" class="form-label">Internal Notes</label>
            <textarea class="form-control" id="quotations-internal-notes" rows="3" v-model="form.internal_notes"></textarea>
          </div>
        </div>

        <hr class="my-4" />

        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0">Quotation Lines</h6>
          <button type="button" class="btn btn-outline-primary btn-sm" @click="addLine">
            <i class="bi bi-plus-lg me-1"></i>Add Line
          </button>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:36px">#</th>
                <th style="min-width:160px">Item / Service</th>
                <th style="min-width:160px">Description</th>
                <th style="min-width:140px">Image</th>
                <th style="width:90px">Qty</th>
                <th style="width:100px">Unit</th>
                <th style="width:110px">Unit Price</th>
                <th style="width:100px">Discount</th>
                <th style="width:100px">Tax</th>
                <th style="width:110px">Amount</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(line, i) in form.lines" :key="i">
                <td class="text-center">{{ i + 1 }}</td>
                <td><input type="text" class="form-control form-control-sm" v-model="line.item_name" /></td>
                <td><input type="text" class="form-control form-control-sm" v-model="line.description" /></td>
                <td><input type="text" class="form-control form-control-sm" v-model="line.image" placeholder="File name / URL…" /></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="line.qty" /></td>
                <td><input type="text" class="form-control form-control-sm" v-model="line.unit" /></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="line.unit_price" /></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="line.discount" /></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" v-model.number="line.tax" /></td>
                <td class="text-end font-monospace">{{ lineAmount(line).toFixed(2) }}</td>
                <td class="text-center">
                  <button type="button" class="btn btn-outline-danger btn-sm" @click="removeLine(i)" :disabled="form.lines.length === 1" title="Remove line">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="row justify-content-end mt-3">
          <div class="col-md-5 col-lg-4">
            <div class="d-flex justify-content-between py-1">
              <span class="text-muted">Subtotal</span>
              <span class="font-monospace">{{ totals.subtotal.toFixed(2) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1">
              <span class="text-muted">Discount</span>
              <span class="font-monospace">{{ totals.discount.toFixed(2) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1">
              <span class="text-muted">Tax</span>
              <span class="font-monospace">{{ totals.tax.toFixed(2) }}</span>
            </div>
            <hr class="my-1" />
            <div class="d-flex justify-content-between py-1 fw-bold">
              <span>Total Amount</span>
              <span class="font-monospace">{{ totals.total_amount.toFixed(2) }}</span>
            </div>
          </div>
        </div>

      </div>
      <div class="card-footer d-flex align-items-center">
        <span class="text-danger small" v-if="error">{{ error }}</span>
        <span class="text-success small" v-if="saved">Quotation saved successfully.</span>
        <button type="button" class="btn btn-success btn-lg px-5 ms-auto" @click="onSave" :disabled="!isDirty || saving">
          <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
          <i v-else class="bi bi-check-lg me-2"></i>Save
        </button>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/quotation_entries_search.php'; ?>
<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/operations/quotations/quotation_entries/quotation_entries.js"></script>
