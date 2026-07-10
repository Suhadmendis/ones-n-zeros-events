// fleet_tracker.js — vehicle cards are driven by live GPS data (see the map script
// in fleet_tracker.php, which pushes into window.ftApp.orders on every refresh)

const { createApp } = Vue;

const FT_EMPTY_ORDER = {
  id: '', plate: '', status: '', statusVariant: '',
  progress: 0, startTime: '—', remaining: '—', endTime: '—',
  // Trip details (gps_direction + live telemetry)
  origin: '—', destination: '—', avgSpeedKmh: null, waitingMinutes: null,
  currentSpeed: null, distanceKm: null, progressKm: null,
  odometerKm: null, fuelPercentage: null, engineRunning: null, recordedAt: '—',
  // Vehicle details (m_vehicles, matched by plate)
  make: '—', model: '—', vehicleType: '—', fuelType: '—', vehicleStatus: '—',
  year: '—', capacity: '—', mileage: '—',
};

window.ftApp = createApp({
  data() {
    return {
      systemName: SYSTEM_NAME,
      title: '',
      driver: null,
      vehicle: null,
      activeId: null,
      activeTab: 'live', // 'live' | 'backtrack'
      backtrackInfo: null, // { count, from, to } for the currently loaded trail — set by ftBacktrackFetchRange
      backtrackLoading: false, // true while a range fetch is in flight — set by ftBacktrackFetchRange
      orders: [],
    };
  },

  computed: {
    activeOrder() {
      return this.orders.find(o => o.id === this.activeId) || this.orders[0] || FT_EMPTY_ORDER;
    },
  },

  mounted() {
    this.fetchTitle();
    document.addEventListener('ft-driver-selected', (e) => { this.driver = e.detail; });
    document.addEventListener('ft-vehicle-selected', (e) => { this.vehicle = e.detail; });
  },

  watch: {
    // Pans/zooms the map onto the newly picked card (see ftFocusOnUnit in fleet_tracker.php),
    // and resets the Backtrack tab to its idle state for the new vehicle if it's already open
    // (this never fetches anything by itself — see ftPrepareBacktrack).
    activeId(serial) {
      if (serial && window.ftFocusOnUnit) window.ftFocusOnUnit(serial);
      if (serial && this.activeTab === 'backtrack') {
        this.$nextTick(() => window.ftPrepareBacktrack && window.ftPrepareBacktrack(serial));
      }
    },
    // Resets the Backtrack tab to its idle state the first time it's shown for a selected
    // vehicle; if no vehicle is selected yet, the tab just shows its empty state instead.
    activeTab(tab) {
      if (tab === 'backtrack' && this.activeId) {
        this.$nextTick(() => window.ftPrepareBacktrack && window.ftPrepareBacktrack(this.activeId));
      }
    },
  },

  methods: {
    fetchTitle() {
      axios.get('/server/general/module_data.php?system_name=' + this.systemName)
        .then(res => { this.title = res.data.module; })
        .catch(err => console.error('Failed to load module title.', err));
    },
    clearDriver() { this.driver = null; },
    clearVehicle() { this.vehicle = null; },
    // Trip Details → Backtrack button: pins the currently-shown order as the active
    // selection (in case it was only ever a fallback, never explicitly clicked) and
    // switches tabs, so the trail loads for the right vehicle immediately.
    goToBacktrack() {
      this.activeId = this.activeOrder.id;
      this.activeTab = 'backtrack';
    },
  },
}).mount('#fleet-tracker-app');
