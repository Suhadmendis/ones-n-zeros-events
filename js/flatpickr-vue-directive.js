// flatpickr-vue-directive.js — shared Vue 3 directive wiring Flatpickr to a data value
// without fighting v-model (flatpickr owns the DOM value; Vue owns the source of truth).
//
// Usage in a module's Vue app:
//   createApp({ ... }).directive('flatpickr', window.FlatpickrDirective).mount('#app-id')
//
// Usage in a template:
//   <input type="text" v-flatpickr="{
//     modelValue: form.planned_start,
//     onChange: (v) => form.planned_start = v,
//     options: { enableTime: true, dateFormat: 'Y-m-d H:i', altInput: true, altFormat: 'd M Y, h:i K' },
//   }" />
//
// For date-only fields, omit enableTime and use dateFormat: 'Y-m-d' / altFormat: 'd M Y'.
window.FlatpickrDirective = {
  mounted(el, binding) {
    const cfg = binding.value || {};
    const fp = flatpickr(el, {
      allowInput: true,
      ...(cfg.options || {}),
      onChange(selectedDates, dateStr) {
        if (cfg.onChange) cfg.onChange(dateStr, selectedDates);
      },
    });
    el._fpModelValue = cfg.modelValue ?? '';
    if (cfg.modelValue) fp.setDate(cfg.modelValue, false);
  },

  updated(el, binding) {
    const fp = el._flatpickr;
    if (!fp) return;
    const newVal = binding.value?.modelValue ?? '';
    if (newVal !== el._fpModelValue) {
      el._fpModelValue = newVal;
      fp.setDate(newVal || null, false);
    }
  },

  unmounted(el) {
    if (el._flatpickr) el._flatpickr.destroy();
  },
};
