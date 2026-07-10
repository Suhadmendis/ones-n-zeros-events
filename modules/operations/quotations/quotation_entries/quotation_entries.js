// quotation_entries.js

const { createApp } = Vue;

const emptyLine = () => ({
  item_name: '',
  description: '',
  image: '',
  qty: 0,
  unit: '',
  unit_price: 0,
  discount: 0,
  tax: 0,
  flora_notes: '',
});

const lineHasData = (l) => (
  l.item_name || l.description || l.qty || l.unit_price
);

const lineAmount = (l) => (
  (Number(l.qty) || 0) * (Number(l.unit_price) || 0) - (Number(l.discount) || 0) + (Number(l.tax) || 0)
);

createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title:      '',
      loading: false,
      saving:  false,
      saved:   false,
      error:   '',
      isExisting: false,
      form: {
        ref: '',
        revision_no: 0,
        customer_ref: '',
        customer_name: '',
        customer_phone: '',
        customer_mobile: '',
        contact_person: '',
        subject: '',
        customer_reference: '',
        quotation_status_ref: '',
        quotation_status_name: '',
        quotation_date: '',
        valid_until: '',
        currency_ref: '',
        currency_name: '',
        salesperson_ref: '',
        salesperson_name: '',
        price_list: '',
        payment_terms: '',
        delivery_period: '',
        notes: '',
        terms_conditions: '',
        internal_notes: '',
        lines: [emptyLine()],
      },
    };
  },

  computed: {
    totals() {
      const lines = this.form.lines.filter(lineHasData);
      const subtotal = lines.reduce((sum, l) => sum + (Number(l.qty) || 0) * (Number(l.unit_price) || 0), 0);
      const discount = lines.reduce((sum, l) => sum + (Number(l.discount) || 0), 0);
      const tax      = lines.reduce((sum, l) => sum + (Number(l.tax) || 0), 0);
      return { subtotal, discount, tax, total_amount: subtotal - discount + tax };
    },

    isDirty() {
      return (
        this.form.customer_ref !== '' ||
        this.form.contact_person !== '' ||
        this.form.subject !== '' ||
        this.form.customer_reference !== '' ||
        this.form.quotation_status_ref !== '' ||
        this.form.quotation_date !== '' ||
        this.form.valid_until !== '' ||
        this.form.currency_ref !== '' ||
        this.form.salesperson_ref !== '' ||
        this.form.price_list !== '' ||
        this.form.payment_terms !== '' ||
        this.form.delivery_period !== '' ||
        this.form.notes !== '' ||
        this.form.terms_conditions !== '' ||
        this.form.internal_notes !== '' ||
        this.form.revision_no !== 0 ||
        this.form.lines.some(lineHasData)
      );
    },
  },

  mounted() {
    this.fetchRefNumber();
    document.addEventListener('quotations-selected', (e) => this.loadRecord(e.detail));
    document.addEventListener('qt-customer-selected', (e) => {
      this.form.customer_ref  = e.detail.ref;
      this.form.customer_name = e.detail.customer_name;
      this.form.customer_phone = e.detail.phone || '';
      this.form.customer_mobile = e.detail.mobile || '';
      this.form.contact_person = e.detail.contact_person || '';
    });
    document.addEventListener('qt-status-selected', (e) => {
      this.form.quotation_status_ref  = e.detail.ref;
      this.form.quotation_status_name = e.detail.name;
    });
    document.addEventListener('qt-currency-selected', (e) => {
      this.form.currency_ref  = e.detail.ref;
      this.form.currency_name = e.detail.currency_code + ' — ' + e.detail.currency_name;
    });
    document.addEventListener('qt-salesperson-selected', (e) => {
      this.form.salesperson_ref  = e.detail.ref;
      this.form.salesperson_name = e.detail.full_name;
    });
    document.addEventListener('qt-terms-selected', (e) => {
      this.form.terms_conditions = e.detail.description || '';
    });
    document.addEventListener('qt-payment-terms-selected', (e) => {
      this.form.payment_terms = e.detail.description || '';
    });
    document.addEventListener('qt-delivery-period-selected', (e) => {
      this.form.delivery_period = e.detail.description || '';
    });
    document.addEventListener('qt-notes-selected', (e) => {
      this.form.notes = e.detail.description || '';
    });
  },

  methods: {
    lineAmount,

    toDatetimeLocal(value) {
      return value ? String(value).slice(0, 16) : '';
    },

    dateTimePicker(value, onChange) {
      return { modelValue: value, onChange, options: { enableTime: true, dateFormat: 'Y-m-d H:i', altInput: true, altFormat: 'd M Y, h:i K', minuteIncrement: 15 } };
    },

    fetchRefNumber() {
      this.loading = true;
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.form.ref = res.data.ref; this.title = res.data.module; })
        .catch(err => { this.error = 'Failed to load reference number.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    clearCustomer()     { this.form.customer_ref = ''; this.form.customer_name = ''; this.form.customer_phone = ''; this.form.customer_mobile = ''; },
    clearStatus()       { this.form.quotation_status_ref = ''; this.form.quotation_status_name = ''; },
    clearCurrency()     { this.form.currency_ref = ''; this.form.currency_name = ''; },
    clearSalesperson()  { this.form.salesperson_ref = ''; this.form.salesperson_name = ''; },

    addLine()     { this.form.lines.push(emptyLine()); },
    removeLine(i) { if (this.form.lines.length > 1) this.form.lines.splice(i, 1); },

    validate() {
      if (!this.form.customer_ref) return 'Customer is required.';
      const activeLines = this.form.lines.filter(lineHasData);
      if (activeLines.length === 0) return 'At least one quotation line is required.';
      for (const l of activeLines) {
        if (!l.item_name) return 'Every line needs an Item / Service.';
        if (!l.qty) return 'Every line needs a Qty.';
        if (!l.unit_price && l.unit_price !== 0) return 'Every line needs a Unit Price.';
      }
      return '';
    },

    onAdd()    { this.onReset(); this.fetchRefNumber(); },
    onPrint() {
      if (!this.form.ref) { this.error = 'No record to print. Search and select a saved record first.'; return; }
      axios.get('/modules/operations/quotations/quotation_entries/quotation_entries_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
        .then(res => {
          if (!res.data.exists) {
            this.error = 'No saved record found for this reference. Please search and select a record to print.';
            return;
          }
          const t = this.totals;
          const params = new URLSearchParams({
            ref: this.form.ref,
            revision_no: String(this.form.revision_no ?? ''),
            customer_name: String(this.form.customer_name ?? ''),
            contact_person: String(this.form.contact_person ?? ''),
            subject: String(this.form.subject ?? ''),
            customer_reference: String(this.form.customer_reference ?? ''),
            quotation_status_name: String(this.form.quotation_status_name ?? ''),
            quotation_date: String(this.form.quotation_date ?? ''),
            valid_until: String(this.form.valid_until ?? ''),
            currency_name: String(this.form.currency_name ?? ''),
            salesperson_name: String(this.form.salesperson_name ?? ''),
            price_list: String(this.form.price_list ?? ''),
            payment_terms: String(this.form.payment_terms ?? ''),
            delivery_period: String(this.form.delivery_period ?? ''),
            notes: String(this.form.notes ?? ''),
            terms_conditions: String(this.form.terms_conditions ?? ''),
            subtotal: t.subtotal.toFixed(2),
            discount: t.discount.toFixed(2),
            tax: t.tax.toFixed(2),
            total_amount: t.total_amount.toFixed(2),
            lines: JSON.stringify(this.form.lines.filter(lineHasData).map(l => ({ ...l, amount: lineAmount(l) }))),
          });
          window.open('/modules/operations/quotations/quotation_entries/quotation_entries_print.php?' + params.toString(), '_blank');
        })
        .catch(err => { this.error = 'Failed to verify record.'; console.error(err); });
    },
    onWhatsApp() {
      if (!this.form.customer_ref) { this.error = 'Select a customer first.'; return; }
      const raw = this.form.customer_mobile || this.form.customer_phone;
      const digits = String(raw || '').replace(/\D/g, '').replace(/^0/, '94');
      if (!digits) { this.error = 'This customer has no phone/mobile number on file.'; return; }

      const t = this.totals;
      const lines = [
        `Quotation ${this.form.ref || ''}`.trim(),
        this.form.subject ? `Subject: ${this.form.subject}` : '',
        `Total: ${t.total_amount.toFixed(2)}`,
      ].filter(Boolean);

      window.open('https://wa.me/' + digits + '?text=' + encodeURIComponent(lines.join('\n')), '_blank');
    },
    onCancel() { this.onReset(); },
    onClose()  { this.onReset(); },

    onReset() {
      this.saved = false;
      this.error = '';
      this.isExisting = false;
      this.form.revision_no = 0;
      this.form.customer_ref = '';
      this.form.customer_name = '';
      this.form.customer_phone = '';
      this.form.customer_mobile = '';
      this.form.contact_person = '';
      this.form.subject = '';
      this.form.customer_reference = '';
      this.form.quotation_status_ref = '';
      this.form.quotation_status_name = '';
      this.form.quotation_date = '';
      this.form.valid_until = '';
      this.form.currency_ref = '';
      this.form.currency_name = '';
      this.form.salesperson_ref = '';
      this.form.salesperson_name = '';
      this.form.price_list = '';
      this.form.payment_terms = '';
      this.form.delivery_period = '';
      this.form.notes = '';
      this.form.terms_conditions = '';
      this.form.internal_notes = '';
      this.form.lines = [emptyLine()];
    },

    onSave() {
      if (!this.isDirty) return;
      const validationError = this.validate();
      if (validationError) { this.error = validationError; return; }

      this.saving = true;
      this.saved  = false;
      this.error  = '';

      const t = this.totals;
      const payload = {
        ...this.form,
        subtotal: t.subtotal,
        discount: t.discount,
        tax: t.tax,
        total_amount: t.total_amount,
        lines: this.form.lines.filter(lineHasData).map(l => ({ ...l, amount: lineAmount(l) })),
      };

      const proceed = (action) => {
        axios.post('/modules/operations/quotations/quotation_entries/quotation_entries_data.php?action=' + action, payload)
          .then(() => {
            this.saved = true;
            this.onReset();
            this.fetchRefNumber();
          })
          .catch(err => { this.error = 'Failed to save.'; console.error(err); })
          .finally(() => { this.saving = false; });
      };

      if (this.isExisting) {
        axios.get('/modules/operations/quotations/quotation_entries/quotation_entries_data.php?action=exists&ref=' + encodeURIComponent(this.form.ref))
          .then(res => {
            if (!res.data.exists) { this.error = 'Record not found. Please search again.'; this.saving = false; return; }
            proceed('update');
          })
          .catch(err => { this.error = 'Failed to verify record.'; console.error(err); this.saving = false; });
      } else {
        proceed('save');
      }
    },

    loadRecord(data) {
      this.form.ref = data.ref;
      this.form.revision_no = data.revision_no ?? 0;
      this.form.customer_ref = data.customer_ref ?? '';
      this.form.customer_name = data.m_customers?.customer_name ?? '';
      this.form.customer_phone = data.m_customers?.phone ?? '';
      this.form.customer_mobile = data.m_customers?.mobile ?? '';
      this.form.contact_person = data.contact_person ?? '';
      this.form.subject = data.subject ?? '';
      this.form.customer_reference = data.customer_reference ?? '';
      this.form.quotation_status_ref = data.quotation_status_ref ?? '';
      this.form.quotation_status_name = data.m_quotation_statuses?.name ?? '';
      this.form.quotation_date = this.toDatetimeLocal(data.quotation_date);
      this.form.valid_until = this.toDatetimeLocal(data.valid_until);
      this.form.currency_ref = data.currency_ref ?? '';
      this.form.currency_name = data.m_currencies ? (data.m_currencies.currency_code + ' — ' + data.m_currencies.currency_name) : '';
      this.form.salesperson_ref = data.salesperson_ref ?? '';
      this.form.salesperson_name = data.m_employees?.full_name ?? '';
      this.form.price_list = data.price_list ?? '';
      this.form.payment_terms = data.payment_terms ?? '';
      this.form.delivery_period = data.delivery_period ?? '';
      this.form.notes = data.notes ?? '';
      this.form.terms_conditions = data.terms_conditions ?? '';
      this.form.internal_notes = data.internal_notes ?? '';
      this.form.lines = (data.lines && data.lines.length)
        ? data.lines.map(l => ({
            item_name: l.item_name ?? '',
            description: l.description ?? '',
            image: l.image ?? '',
            qty: l.qty ?? 0,
            unit: l.unit ?? '',
            unit_price: l.unit_price ?? 0,
            discount: l.discount ?? 0,
            tax: l.tax ?? 0,
            flora_notes: l.flora_notes ?? '',
          }))
        : [emptyLine()];
      this.isExisting = true;
      this.saved = false;
      this.error = '';
    },
  },
}).directive('flatpickr', window.FlatpickrDirective).mount('#quotations-app');
