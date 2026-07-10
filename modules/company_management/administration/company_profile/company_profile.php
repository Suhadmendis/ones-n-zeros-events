<?php /* company_management/company_profile/company_profile.php */ ?>

<div id="company-app" class="row g-4">

  <!-- ── Basic Information ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header d-flex align-items-center">
        <div class="card-title me-auto"><i class="bi bi-building me-2"></i>Basic Information</div>
        <span v-if="loading" class="spinner-border spinner-border-sm text-secondary" role="status"></span>
        <button type="button" class="btn btn-outline-secondary btn-sm ms-2 module-help-btn" title="Help">
          <i class="bi bi-question-circle me-1"></i>Help
        </button>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-8">
            <label class="form-label">Company Name</label>
            <input type="text" class="form-control" id="cp-name" placeholder="Full legal company name"
                   v-model="form.name" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Company Short Name</label>
            <input type="text" class="form-control" id="cp-short-name" placeholder="Abbreviation or short name"
                   v-model="form.short_name" :disabled="loading">
          </div>

          <div class="col-md-4">
            <label class="form-label">Business Registration Number</label>
            <input type="text" class="form-control" id="cp-reg-number" placeholder="e.g. PV 12345"
                   v-model="form.reg_number" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tax Identification Number (TIN)</label>
            <input type="text" class="form-control" id="cp-tin" placeholder="TIN"
                   v-model="form.tin" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">VAT Number</label>
            <input type="text" class="form-control" id="cp-vat-number" placeholder="VAT registration number"
                   v-model="form.vat_number" :disabled="loading">
          </div>

          <div class="col-md-3">
            <label class="form-label">Business Type</label>
            <select class="form-select" id="cp-business-type" v-model="form.business_type" :disabled="loading">
              <option value="">— Select —</option>
              <option>Sole Proprietorship</option>
              <option>Partnership</option>
              <option>Private Limited Company (Pvt Ltd)</option>
              <option>Public Limited Company (PLC)</option>
              <option>Non-Profit / NGO</option>
              <option>Government</option>
              <option>Other</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Industry</label>
            <input type="text" class="form-control" id="cp-industry" placeholder="e.g. Transport, Retail, IT"
                   v-model="form.industry" :disabled="loading">
          </div>
          <div class="col-md-3">
            <label class="form-label">Established Date</label>
            <input type="date" class="form-control" id="cp-established-date"
                   v-model="form.established_date" :disabled="loading">
          </div>
          <div class="col-md-3">
            <label class="form-label">Company Status</label>
            <select class="form-select" id="cp-company-status" v-model="form.company_status" :disabled="loading">
              <option>Active</option>
              <option>Inactive</option>
              <option>Suspended</option>
            </select>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Contact Information ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-telephone me-2"></i>Contact Information</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Telephone</label>
            <input type="tel" class="form-control" id="cp-telephone" placeholder="Landline number"
                   v-model="form.telephone" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Mobile</label>
            <input type="tel" class="form-control" id="cp-mobile" placeholder="Mobile number"
                   v-model="form.mobile" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fax</label>
            <input type="tel" class="form-control" id="cp-fax" placeholder="Fax number"
                   v-model="form.fax" :disabled="loading">
          </div>

          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="cp-email" placeholder="company@example.com"
                   v-model="form.email" :disabled="loading">
          </div>
          <div class="col-md-6">
            <label class="form-label">Website</label>
            <input type="url" class="form-control" id="cp-website" placeholder="https://www.example.com"
                   v-model="form.website" :disabled="loading">
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Address ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-geo-alt me-2"></i>Address</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-12">
            <label class="form-label">Address Line 1</label>
            <input type="text" class="form-control" id="cp-address-line1" placeholder="Street address"
                   v-model="form.address_line1" :disabled="loading">
          </div>
          <div class="col-12">
            <label class="form-label">Address Line 2</label>
            <input type="text" class="form-control" id="cp-address-line2" placeholder="Apartment, suite, floor, etc. (optional)"
                   v-model="form.address_line2" :disabled="loading">
          </div>

          <div class="col-md-4">
            <label class="form-label">City</label>
            <input type="text" class="form-control" id="cp-city" placeholder="City"
                   v-model="form.city" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">District</label>
            <input type="text" class="form-control" id="cp-district" placeholder="District"
                   v-model="form.district" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Province / State</label>
            <input type="text" class="form-control" id="cp-province" placeholder="Province or state"
                   v-model="form.province" :disabled="loading">
          </div>

          <div class="col-md-3">
            <label class="form-label">Postal Code</label>
            <input type="text" class="form-control" id="cp-postal-code" placeholder="Postal code"
                   v-model="form.postal_code" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Country</label>
            <input type="text" class="form-control" id="cp-country" placeholder="Country"
                   v-model="form.country" :disabled="loading">
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Branding ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-image me-2"></i>Branding</div>
      </div>
      <div class="card-body">
        <div class="row g-4">

          <div class="col-md-6">
            <label class="form-label">Company Logo</label>
            <div class="d-flex align-items-start gap-3">
              <div class="border rounded d-flex align-items-center justify-content-center bg-light flex-shrink-0"
                   style="width:80px;height:80px;">
                <img v-if="form.logo_path" :src="form.logo_path" alt="Company logo"
                     style="max-width:100%;max-height:100%;object-fit:contain;">
                <i v-else class="bi bi-building text-muted" style="font-size:2rem;"></i>
              </div>
              <div class="flex-grow-1">
                <input type="file" class="form-control" accept="image/*"
                       @change="onLogoChange" :disabled="loading">
                <div class="form-text">PNG, JPG or SVG. Recommended: 300×150 px.</div>
              </div>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label">Company Seal <span class="text-muted fw-normal">(Optional)</span></label>
            <div class="d-flex align-items-start gap-3">
              <div class="border rounded d-flex align-items-center justify-content-center bg-light flex-shrink-0"
                   style="width:80px;height:80px;">
                <img v-if="form.seal_path" :src="form.seal_path" alt="Company seal"
                     style="max-width:100%;max-height:100%;object-fit:contain;">
                <i v-else class="bi bi-award text-muted" style="font-size:2rem;"></i>
              </div>
              <div class="flex-grow-1">
                <input type="file" class="form-control" accept="image/*"
                       @change="onSealChange" :disabled="loading">
                <div class="form-text">PNG or SVG with transparent background. Recommended: 300×300 px.</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Financial ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-currency-exchange me-2"></i>Financial</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label">Base Currency</label>
            <select class="form-select" id="cp-base-currency" v-model="form.base_currency" :disabled="loading">
              <option value="LKR">LKR — Sri Lankan Rupee</option>
              <option value="USD">USD — US Dollar</option>
              <option value="EUR">EUR — Euro</option>
              <option value="GBP">GBP — British Pound</option>
              <option value="AUD">AUD — Australian Dollar</option>
              <option value="SGD">SGD — Singapore Dollar</option>
              <option value="INR">INR — Indian Rupee</option>
              <option value="AED">AED — UAE Dirham</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Financial Year Start</label>
            <input type="date" class="form-control" id="cp-fy-start"
                   v-model="form.financial_year_start" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Financial Year End</label>
            <input type="date" class="form-control" id="cp-fy-end"
                   v-model="form.financial_year_end" :disabled="loading">
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Regional ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-globe me-2"></i>Regional</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-3">
            <label class="form-label">Language</label>
            <select class="form-select" id="cp-language" v-model="form.language" :disabled="loading">
              <option value="en">English</option>
              <option value="si">Sinhala</option>
              <option value="ta">Tamil</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Time Zone</label>
            <select class="form-select" id="cp-timezone" v-model="form.timezone" :disabled="loading">
              <option value="Asia/Colombo">Asia/Colombo (UTC+5:30)</option>
              <option value="UTC">UTC</option>
              <option value="Asia/Dubai">Asia/Dubai (UTC+4)</option>
              <option value="Asia/Singapore">Asia/Singapore (UTC+8)</option>
              <option value="Asia/Kolkata">Asia/Kolkata (UTC+5:30)</option>
              <option value="Europe/London">Europe/London</option>
              <option value="America/New_York">America/New_York</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Date Format</label>
            <select class="form-select" id="cp-date-format" v-model="form.date_format" :disabled="loading">
              <option value="DD/MM/YYYY">DD/MM/YYYY</option>
              <option value="MM/DD/YYYY">MM/DD/YYYY</option>
              <option value="YYYY-MM-DD">YYYY-MM-DD</option>
              <option value="DD-MM-YYYY">DD-MM-YYYY</option>
              <option value="DD.MM.YYYY">DD.MM.YYYY</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Time Format</label>
            <select class="form-select" id="cp-time-format" v-model="form.time_format" :disabled="loading">
              <option value="24h">24-hour (14:30)</option>
              <option value="12h">12-hour (2:30 PM)</option>
            </select>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Bank Information ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-bank me-2"></i>Bank Information</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label">Bank Name</label>
            <input type="text" class="form-control" id="cp-bank-name" placeholder="e.g. Bank of Ceylon"
                   v-model="form.bank_name" :disabled="loading">
          </div>
          <div class="col-md-6">
            <label class="form-label">Branch Name</label>
            <input type="text" class="form-control" id="cp-branch-name" placeholder="Branch name"
                   v-model="form.branch_name" :disabled="loading">
          </div>

          <div class="col-md-5">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-control" id="cp-account-name" placeholder="Account holder name"
                   v-model="form.account_name" :disabled="loading">
          </div>
          <div class="col-md-4">
            <label class="form-label">Account Number</label>
            <input type="text" class="form-control" id="cp-account-number" placeholder="Account number"
                   v-model="form.account_number" :disabled="loading">
          </div>
          <div class="col-md-3">
            <label class="form-label">SWIFT Code <span class="text-muted fw-normal">(Optional)</span></label>
            <input type="text" class="form-control" id="cp-swift-code" placeholder="SWIFT / BIC code"
                   v-model="form.swift_code" :disabled="loading">
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Report Settings ── -->
  <div class="col-12">
    <div class="card card-primary card-outline mb-0">
      <div class="card-header">
        <div class="card-title"><i class="bi bi-printer me-2"></i>Report Settings</div>
      </div>
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-3">
            <label class="form-label">Print Logo on Reports</label>
            <div class="form-check form-switch mt-1">
              <input class="form-check-input" type="checkbox" id="sw-print-logo"
                     v-model="form.print_logo" :disabled="loading">
              <label class="form-check-label" for="sw-print-logo">{{ form.print_logo ? 'Yes' : 'No' }}</label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Print Company Seal</label>
            <div class="form-check form-switch mt-1">
              <input class="form-check-input" type="checkbox" id="sw-print-seal"
                     v-model="form.print_seal" :disabled="loading">
              <label class="form-check-label" for="sw-print-seal">{{ form.print_seal ? 'Yes' : 'No' }}</label>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Paper Size</label>
            <select class="form-select" id="cp-paper-size" v-model="form.paper_size" :disabled="loading">
              <option value="A4">A4</option>
              <option value="Letter">Letter</option>
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">Report Footer Text</label>
            <textarea class="form-control" rows="2" id="cp-report-footer"
                      placeholder="Text to appear in the footer of printed reports"
                      v-model="form.report_footer" :disabled="loading"></textarea>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Save Feedback ── -->
  <div class="col-12" v-if="saveStatus">
    <div v-if="saveStatus === 'ok'" class="alert alert-success d-flex align-items-center gap-2 py-2 mb-0">
      <i class="bi bi-check-circle-fill"></i>Saved successfully.
    </div>
    <div v-if="saveStatus === 'error'" class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-0">
      <i class="bi bi-exclamation-triangle-fill"></i>{{ saveMsg }}
    </div>
  </div>

  <!-- ── Save Bar ── -->
  <div class="col-12 d-flex align-items-center gap-3 pb-3">
    <span class="text-secondary small" v-if="isDirty">You have unsaved changes.</span>
    <button type="button" class="btn btn-success btn-lg px-5 ms-auto"
            @click="onSave" :disabled="!isDirty || saving">
      <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
      <i v-else class="bi bi-check-lg me-2"></i>Save
    </button>
  </div>

</div>

<script src="/modules/company_management/administration/company_profile/company_profile.js"></script>
