// event_calendar.js — read-only month-grid calendar for Event Planning

const { createApp } = Vue;

createApp({
  data() {
    const today = new Date();
    return {
      systemName: SYSTEM_NAME,
      title: '',
      loading: false,
      error: '',
      year: today.getFullYear(),
      month: today.getMonth(), // 0-based
      events: [],
      weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
      activeDay: null,
    };
  },

  computed: {
    monthLabel() {
      return new Date(this.year, this.month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    },

    eventsByDate() {
      const map = {};
      for (const ev of this.events) {
        (map[ev.date] ??= []).push(ev);
      }
      return map;
    },

    cells() {
      const firstOfMonth = new Date(this.year, this.month, 1);
      const startOffset = firstOfMonth.getDay(); // 0=Sun
      const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
      const daysInPrevMonth = new Date(this.year, this.month, 0).getDate();
      const totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;

      const todayKey = this.dateKey(new Date());
      const out = [];

      for (let i = 0; i < totalCells; i++) {
        const dayNum = i - startOffset + 1;
        let cellDate, inMonth;
        if (dayNum < 1) {
          cellDate = new Date(this.year, this.month - 1, daysInPrevMonth + dayNum);
          inMonth = false;
        } else if (dayNum > daysInMonth) {
          cellDate = new Date(this.year, this.month + 1, dayNum - daysInMonth);
          inMonth = false;
        } else {
          cellDate = new Date(this.year, this.month, dayNum);
          inMonth = true;
        }
        const key = this.dateKey(cellDate);
        out.push({
          key,
          day: cellDate.getDate(),
          inMonth,
          isToday: key === todayKey,
          events: this.eventsByDate[key] || [],
        });
      }
      return out;
    },

    activeDayEvents() {
      if (!this.activeDay) return [];
      return this.eventsByDate[this.activeDay.key] || [];
    },

    activeDayLabel() {
      if (!this.activeDay) return '';
      return new Date(this.activeDay.key).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    },
  },

  mounted() {
    this.title = 'Event Calendar';
    this.fetchEvents();
  },

  methods: {
    dateKey(d) {
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    },

    statusClass(status) {
      return String(status || '').toLowerCase().replace(/\s+/g, '-');
    },

    fetchEvents() {
      this.loading = true;
      this.error = '';
      const from = `${this.year}-${String(this.month + 1).padStart(2, '0')}-01`;
      const toDate = new Date(this.year, this.month + 1, 0);
      const to = this.dateKey(toDate);

      axios.get('/modules/operations/event_planning/event_calendar/event_calendar_data.php?action=list_events&from=' + from + '&to=' + to)
        .then(res => { this.events = res.data; })
        .catch(err => { this.error = 'Failed to load calendar events.'; console.error(err); })
        .finally(() => { this.loading = false; });
    },

    prevMonth() {
      this.month -= 1;
      if (this.month < 0) { this.month = 11; this.year -= 1; }
      this.fetchEvents();
    },
    nextMonth() {
      this.month += 1;
      if (this.month > 11) { this.month = 0; this.year += 1; }
      this.fetchEvents();
    },
    goToday() {
      const t = new Date();
      this.year = t.getFullYear();
      this.month = t.getMonth();
      this.fetchEvents();
    },

    openEvent(ev) {
      window.location.href = ev.url;
    },
    openDay(cell) {
      this.activeDay = cell;
      new bootstrap.Modal(document.getElementById('ecDayModal')).show();
    },
  },
}).mount('#event-calendar-app');
