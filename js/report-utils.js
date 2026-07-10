(() => {
  'use strict';
  window.ReportUtils = {
    fmt(v) {
      return parseFloat(v || 0).toLocaleString('en-LK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
    currentMonthRange() {
      const now = new Date();
      const y = now.getFullYear();
      const m = String(now.getMonth() + 1).padStart(2, '0');
      const lastDay = String(new Date(y, now.getMonth() + 1, 0).getDate()).padStart(2, '0');
      return { from: `${y}-${m}-01`, to: `${y}-${m}-${lastDay}` };
    },
    buildMonths() {
      const now = new Date();
      const r = [];
      for (let i = 0; i <= now.getMonth(); i++) {
        r.push({
          label: new Date(now.getFullYear(), i, 1).toLocaleString('en-US', { month: 'short' }),
          year: now.getFullYear(),
          month: i,
        });
      }
      return r;
    },
    monthRange(year, month) {
      const m = String(month + 1).padStart(2, '0');
      const lastDay = String(new Date(year, month + 1, 0).getDate()).padStart(2, '0');
      return { from: `${year}-${m}-01`, to: `${year}-${m}-${lastDay}` };
    },
    monthKey(year, month) {
      return `${year}-${String(month + 1).padStart(2, '0')}`;
    },
    createReport({ dataUrl, mountId, extraData, renderChart }) {
      const { createApp } = Vue;
      createApp({
        data() {
          const { from, to } = ReportUtils.currentMonthRange();
          return {
            from,
            to,
            rows: [],
            loading: false,
            error: null,
            ran: false,
            chart: null,
            months: ReportUtils.buildMonths(),
            ...(extraData || {}),
          };
        },
        methods: {
          fmt: ReportUtils.fmt,
          selectMonth(year, month) {
            const { from, to } = ReportUtils.monthRange(year, month);
            this.from = from;
            this.to   = to;
            this.load();
          },
          async load() {
            this.loading = true;
            this.error   = null;
            this.ran     = false;
            try {
              const params = new URLSearchParams({ action: 'report', from: this.from, to: this.to });
              if (this.limit != null) params.set('limit', this.limit);
              const res  = await fetch(`${dataUrl}?${params}`);
              const data = await res.json();
              if (data.error) { this.error = data.error; this.rows = []; }
              else { this.rows = data; }
              this.ran = true;
              if (this.rows.length && renderChart) {
                await this.$nextTick();
                this.renderChart();
              }
            } catch (e) {
              this.error = 'Failed to load data: ' + e.message;
            } finally {
              this.loading = false;
            }
          },
          ...(renderChart ? { renderChart } : {}),
        },
        mounted() {
          this.load();
        },
      }).mount(mountId);
    },
    printReport() {
      if (typeof ActivityLogger !== 'undefined') {
        const heading = document.querySelector('.app-content-header h3');
        const label   = heading ? heading.textContent.trim() : null;
        const page    = new URLSearchParams(location.search).get('page') || null;
        ActivityLogger.logExport('pdf', {
          module:         page,
          module_label:   label,
          description:    `Printed PDF: ${label || page}`,
        });
      }
      window.print();
    },
    exportExcel() {
      if (typeof XLSX === 'undefined') return;
      let table = null;
      for (const t of document.querySelectorAll('.app-content table.table')) {
        if (!t.closest('.modal')) { table = t; break; }
      }
      if (!table) {
        alert('Run the report first to generate data for export.');
        return;
      }
      const heading  = document.querySelector('.app-content-header h3');
      const name     = heading ? heading.textContent.trim() : 'report';
      const filename = name.replace(/[^a-z0-9 ]/gi, '').replace(/\s+/g, '_') || 'report';
      const rowCount = table.querySelectorAll('tbody tr').length;
      const page     = new URLSearchParams(location.search).get('page') || null;
      if (typeof ActivityLogger !== 'undefined') {
        ActivityLogger.logExport('excel', {
          module:       page,
          module_label: name,
          description:  `Exported Excel: ${name}`,
          row_count:    rowCount,
        });
      }
      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.table_to_sheet(table);
      XLSX.utils.book_append_sheet(wb, ws, name.substring(0, 31));
      XLSX.writeFile(wb, filename + '.xlsx');
    },
  };
})();
