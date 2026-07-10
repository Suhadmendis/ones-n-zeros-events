export default {
  url: '/home.php?page=company_profile',
  apiUrl: '/modules/company_management/administration/company_profile/company_profile_data.php',
  fields: [
    // Basic Information
    { selector: '#cp-name',              type: 'text',     value: 'Playwright Test Company Ltd',                    field: 'name',                 settingKey: 'cp_name' },
    { selector: '#cp-short-name',        type: 'text',     value: 'PW Test Co',                                     field: 'short_name',           settingKey: 'cp_short_name' },
    { selector: '#cp-reg-number',        type: 'text',     value: 'PV-TEST-0001',                                  field: 'reg_number',           settingKey: 'cp_reg_number' },
    { selector: '#cp-tin',               type: 'text',     value: 'TIN-TEST-0001',                                 field: 'tin',                  settingKey: 'cp_tin' },
    { selector: '#cp-vat-number',        type: 'text',     value: 'VAT-TEST-0001',                                 field: 'vat_number',           settingKey: 'cp_vat_number' },
    { selector: '#cp-business-type',     type: 'select',   value: 'Private Limited Company (Pvt Ltd)',             field: 'business_type',        settingKey: 'cp_business_type' },
    { selector: '#cp-industry',          type: 'text',     value: 'Software Testing',                              field: 'industry',             settingKey: 'cp_industry' },
    { selector: '#cp-established-date',  type: 'date',     value: '2020-06-15',                                    field: 'established_date',     settingKey: 'cp_established_date' },
    { selector: '#cp-company-status',    type: 'select',   value: 'Active',                                        field: 'company_status',       settingKey: 'cp_company_status' },
    // Contact Information
    { selector: '#cp-telephone',         type: 'text',     value: '0112345678',                                    field: 'telephone',            settingKey: 'cp_telephone' },
    { selector: '#cp-mobile',            type: 'text',     value: '0771234567',                                    field: 'mobile',               settingKey: 'cp_mobile' },
    { selector: '#cp-fax',               type: 'text',     value: '0112345679',                                    field: 'fax',                  settingKey: 'cp_fax' },
    { selector: '#cp-email',             type: 'text',     value: 'test.company@playwright.test',                  field: 'email',                settingKey: 'cp_email' },
    { selector: '#cp-website',           type: 'text',     value: 'https://playwright-test.example.com',           field: 'website',              settingKey: 'cp_website' },
    // Address
    { selector: '#cp-address-line1',     type: 'text',     value: '123 Test Street',                               field: 'address_line1',        settingKey: 'cp_address_line1' },
    { selector: '#cp-address-line2',     type: 'text',     value: 'Suite 4B',                                      field: 'address_line2',        settingKey: 'cp_address_line2' },
    { selector: '#cp-city',              type: 'text',     value: 'Colombo',                                       field: 'city',                 settingKey: 'cp_city' },
    { selector: '#cp-district',          type: 'text',     value: 'Colombo',                                       field: 'district',             settingKey: 'cp_district' },
    { selector: '#cp-province',          type: 'text',     value: 'Western',                                       field: 'province',             settingKey: 'cp_province' },
    { selector: '#cp-postal-code',       type: 'text',     value: '00100',                                         field: 'postal_code',          settingKey: 'cp_postal_code' },
    { selector: '#cp-country',           type: 'text',     value: 'Sri Lanka',                                     field: 'country',              settingKey: 'cp_country' },
    // Financial
    { selector: '#cp-base-currency',     type: 'select',   value: 'LKR',                                           field: 'base_currency',        settingKey: 'cp_base_currency' },
    { selector: '#cp-fy-start',          type: 'date',     value: '2025-01-01',                                    field: 'financial_year_start', settingKey: 'cp_financial_year_start' },
    { selector: '#cp-fy-end',            type: 'date',     value: '2025-12-31',                                    field: 'financial_year_end',   settingKey: 'cp_financial_year_end' },
    // Regional
    { selector: '#cp-language',          type: 'select',   value: 'en',                                            field: 'language',             settingKey: 'cp_language' },
    { selector: '#cp-timezone',          type: 'select',   value: 'Asia/Colombo',                                  field: 'timezone',             settingKey: 'cp_timezone' },
    { selector: '#cp-date-format',       type: 'select',   value: 'YYYY-MM-DD',                                    field: 'date_format',          settingKey: 'cp_date_format' },
    { selector: '#cp-time-format',       type: 'select',   value: '24h',                                           field: 'time_format',          settingKey: 'cp_time_format' },
    // Bank Information
    { selector: '#cp-bank-name',         type: 'text',     value: 'Bank of Ceylon',                                field: 'bank_name',            settingKey: 'cp_bank_name' },
    { selector: '#cp-branch-name',       type: 'text',     value: 'Colombo Main',                                  field: 'branch_name',          settingKey: 'cp_branch_name' },
    { selector: '#cp-account-name',      type: 'text',     value: 'Playwright Test Co',                            field: 'account_name',         settingKey: 'cp_account_name' },
    { selector: '#cp-account-number',    type: 'text',     value: '000123456789',                                  field: 'account_number',       settingKey: 'cp_account_number' },
    { selector: '#cp-swift-code',        type: 'text',     value: 'BCEYLKLX',                                      field: 'swift_code',           settingKey: 'cp_swift_code' },
    // Report Settings
    { selector: '#sw-print-logo',        type: 'checkbox', value: true,                                            field: 'print_logo',           settingKey: 'cp_print_logo' },
    { selector: '#sw-print-seal',        type: 'checkbox', value: true,                                            field: 'print_seal',           settingKey: 'cp_print_seal' },
    { selector: '#cp-paper-size',        type: 'select',   value: 'A4',                                            field: 'paper_size',           settingKey: 'cp_paper_size' },
    { selector: '#cp-report-footer',     type: 'text',     value: 'Generated by Playwright test suite — safe to ignore', field: 'report_footer',   settingKey: 'cp_report_footer' },
  ],
}
