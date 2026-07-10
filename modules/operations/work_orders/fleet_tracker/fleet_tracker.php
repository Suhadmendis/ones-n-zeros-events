<?php /* modules/operations/work_orders/fleet_tracker/fleet_tracker.php — UI-only mockup, hardcoded data, no table backing */ ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" crossorigin="anonymous" />

<div id="fleet-tracker-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body p-0">

        <div class="fleet-tracker">

          <div class="fleet-tracker__panel fleet-tracker__panel--left">
            <div class="input-group mb-2">
              <input type="text" class="form-control" :value="driver ? (driver.ref + ' — ' + driver.name) : ''" placeholder="Search driver…" readonly />
              <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#ftDriverPickerModal">
                <i class="bi bi-search"></i>
              </button>
              <button class="btn btn-outline-secondary" type="button" @click="clearDriver" v-if="driver">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
            <div class="input-group mb-4">
              <input type="text" class="form-control" :value="vehicle ? vehicle.plate_number : ''" placeholder="Search vehicle…" readonly />
              <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#ftVehiclePickerModal">
                <i class="bi bi-search"></i>
              </button>
              <button class="btn btn-outline-secondary" type="button" @click="clearVehicle" v-if="vehicle">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>

            <div class="fleet-tracker__cards">
              <div
                v-for="order in orders"
                :key="order.id"
                class="ft-card"
                :class="{ 'ft-card--active': order.id === activeId }"
                @click="activeId = order.id"
              >
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="ft-card__title"><strong>{{ order.plate }}</strong></div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="ft-badge" :class="'ft-badge--' + order.statusVariant">{{ order.status }}</span>
                    <i class="bi bi-three-dots-vertical"></i>
                  </div>
                </div>
                <div class="ft-progress mb-3"><div class="ft-progress__bar" :style="{ width: order.progress + '%' }"></div></div>
                <div class="ft-pillars">
                  <div class="ft-pillar">
                    <div class="ft-pillar__label">Start</div>
                    <div class="ft-pillar__value">{{ order.startTime }}</div>
                  </div>
                  <div class="ft-pillar ft-pillar--mid">
                    <div class="ft-pillar__label">Remaining</div>
                    <div class="ft-pillar__value">{{ order.remaining }}</div>
                  </div>
                  <div class="ft-pillar">
                    <div class="ft-pillar__label">Arrival</div>
                    <div class="ft-pillar__value">{{ order.endTime }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="fleet-tracker__panel fleet-tracker__panel--right">
            <div class="mb-3"><strong>{{ activeOrder.plate }}</strong></div>

            <ul class="nav nav-tabs ft-tabs mb-3" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" :class="{ active: activeTab === 'live' }" @click="activeTab = 'live'">Live Tracking</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" :class="{ active: activeTab === 'backtrack' }" @click="activeTab = 'backtrack'">Backtrack</button>
              </li>
            </ul>

            <div v-show="activeTab === 'live'">
              <div id="ftMapWrap" class="ft-map-wrap mb-4">
                <div id="ftMap" class="ft-map"></div>
                <div id="ftMapLoading" class="ft-map-loading">
                  <div class="ft-map-loading__label">Loading live fleet data…</div>
                  <div class="progress ft-map-loading__progress">
                    <div id="ftMapLoadingBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%"></div>
                  </div>
                </div>
                <button id="ftMapFullscreenBtn" type="button" class="btn btn-sm btn-light ft-map-fullscreen-btn" title="Toggle fullscreen">
                  <i class="bi bi-arrows-fullscreen"></i>
                </button>
              </div>

              <h6 class="fw-bold mb-3">Main info</h6>
              <div class="ft-maininfo">

                <div class="ft-info-section mb-4">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="ft-info-section__title mb-0">Trip Details</div>
                    <button class="btn btn-sm btn-outline-primary" type="button" @click="goToBacktrack">
                      <i class="bi bi-clock-history"></i> Backtrack
                    </button>
                  </div>
                  <div class="ft-info-grid">
                    <div class="ft-info-item"><span class="ft-info-label">Status</span><span class="ft-info-value">{{ activeOrder.status || '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Origin</span><span class="ft-info-value">{{ activeOrder.origin }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Destination</span><span class="ft-info-value">{{ activeOrder.destination }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Start Time</span><span class="ft-info-value">{{ activeOrder.startTime }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">ETA</span><span class="ft-info-value">{{ activeOrder.endTime }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Remaining</span><span class="ft-info-value">{{ activeOrder.remaining }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Distance Covered</span><span class="ft-info-value">{{ activeOrder.progressKm != null ? activeOrder.progressKm.toFixed(1) + ' / ' + activeOrder.distanceKm.toFixed(1) + ' km' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Current Speed</span><span class="ft-info-value">{{ activeOrder.currentSpeed != null ? activeOrder.currentSpeed.toFixed(1) + ' km/h' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Average Speed</span><span class="ft-info-value">{{ activeOrder.avgSpeedKmh != null ? activeOrder.avgSpeedKmh.toFixed(1) + ' km/h' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Waiting Time</span><span class="ft-info-value">{{ activeOrder.waitingMinutes != null ? activeOrder.waitingMinutes + ' min' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Engine</span><span class="ft-info-value">{{ activeOrder.engineRunning === true ? 'Running' : (activeOrder.engineRunning === false ? 'Off' : '—') }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Fuel Level</span><span class="ft-info-value">{{ activeOrder.fuelPercentage != null ? activeOrder.fuelPercentage.toFixed(1) + '%' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Odometer</span><span class="ft-info-value">{{ activeOrder.odometerKm != null ? activeOrder.odometerKm.toFixed(1) + ' km' : '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Last Updated</span><span class="ft-info-value">{{ activeOrder.recordedAt }}</span></div>
                  </div>
                </div>

                <div class="ft-info-section">
                  <div class="ft-info-section__title">Vehicle Details</div>
                  <div class="ft-info-grid">
                    <div class="ft-info-item"><span class="ft-info-label">Plate Number</span><span class="ft-info-value">{{ activeOrder.plate || '—' }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Make</span><span class="ft-info-value">{{ activeOrder.make }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Model</span><span class="ft-info-value">{{ activeOrder.model }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Type</span><span class="ft-info-value">{{ activeOrder.vehicleType }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Fuel Type</span><span class="ft-info-value">{{ activeOrder.fuelType }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Status</span><span class="ft-info-value">{{ activeOrder.vehicleStatus }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Year</span><span class="ft-info-value">{{ activeOrder.year }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Capacity</span><span class="ft-info-value">{{ activeOrder.capacity }}</span></div>
                    <div class="ft-info-item"><span class="ft-info-label">Mileage</span><span class="ft-info-value">{{ activeOrder.mileage }}</span></div>
                  </div>
                </div>

              </div>
            </div>

            <div v-show="activeTab === 'backtrack'">
              <div v-if="activeId" class="ft-backtrack-range mb-3">
                <div class="ft-backtrack-range__row">
                  <label class="ft-backtrack-range__label" for="ftBacktrackFrom">From</label>
                  <input id="ftBacktrackFrom" type="date" class="form-control form-control-sm ft-backtrack-range__input">
                  <label class="ft-backtrack-range__label" for="ftBacktrackTo">To</label>
                  <input id="ftBacktrackTo" type="date" class="form-control form-control-sm ft-backtrack-range__input">
                  <button id="ftBacktrackRunBtn" type="button" class="btn btn-primary btn-sm">Load Trail</button>
                  <div id="ftBacktrackQuickRange" class="ft-backtrack-range__quick btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" data-quick-days="1">Last Day</button>
                    <button type="button" class="btn btn-outline-secondary" data-quick-days="3">Last 3 Days</button>
                    <button type="button" class="btn btn-outline-secondary" data-quick-days="7">Last Week</button>
                  </div>
                </div>
                <div id="ftBacktrackRangeError" class="ft-backtrack-range__error"></div>
              </div>

              <div v-if="activeId" class="ft-backtrack-meta mb-3">
                <div class="ft-backtrack-meta__vehicle"><strong>{{ activeOrder.plate }}</strong></div>
                <div v-if="backtrackInfo && backtrackInfo.count" class="ft-backtrack-meta__range">
                  Showing {{ backtrackInfo.count }} points — {{ backtrackInfo.from }} to {{ backtrackInfo.to }}
                </div>
              </div>

              <div id="ftBacktrackMapWrap" class="ft-map-wrap ft-map-wrap--backtrack">
                <div id="ftBacktrackMap" class="ft-map"></div>

                <button id="ftBacktrackFullscreenBtn" type="button" class="btn btn-sm btn-light ft-map-fullscreen-btn ft-map-fullscreen-btn--left" title="Toggle fullscreen">
                  <i class="bi bi-arrows-fullscreen"></i>
                </button>

                <div v-if="activeId && backtrackInfo && backtrackInfo.count" class="ft-backtrack-clock">
                  <div id="ftBacktrackClockDate" class="ft-backtrack-clock__date">—</div>
                  <div id="ftBacktrackClockTime" class="ft-backtrack-clock__time">—</div>
                </div>

                <div v-if="!activeId" class="ft-backtrack-empty">
                  <i class="bi bi-clock-history"></i>
                  <div>Select a vehicle to view its backtrack.</div>
                </div>
                <div v-else-if="backtrackLoading" class="ft-backtrack-empty">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                  <div>Loading backtrack…</div>
                </div>
                <div v-else-if="!backtrackInfo" class="ft-backtrack-empty">
                  <i class="bi bi-calendar-range"></i>
                  <div>Pick a date range above and press Load Trail.</div>
                </div>
                <div v-else-if="!backtrackInfo.count" class="ft-backtrack-empty">
                  <i class="bi bi-slash-circle"></i>
                  <div>No trail recorded in that range.</div>
                </div>

                <div v-if="backtrackInfo && backtrackInfo.count" class="ft-backtrack-controls">
                  <button id="ftBacktrackPlayBtn" type="button" class="btn btn-primary btn-sm">
                    <i class="bi bi-pause-fill"></i>
                  </button>
                  <input id="ftBacktrackSeek" type="range" class="form-range ft-backtrack-seek" min="0" max="0" value="0" step="1">
                  <div id="ftBacktrackSpeeds" class="ft-backtrack-speeds btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary" data-speed="1">1x</button>
                    <button type="button" class="btn btn-outline-secondary" data-speed="2">2x</button>
                    <button type="button" class="btn btn-outline-secondary" data-speed="5">5x</button>
                    <button type="button" class="btn btn-outline-secondary" data-speed="10">10x</button>
                    <button type="button" class="btn btn-outline-secondary" data-speed="25">25x</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  </div>

  <!-- Driver picker modal -->
  <div class="modal fade" id="ftDriverPickerModal" tabindex="-1" aria-labelledby="ftDriverPickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ftDriverPickerLabel">Select Driver</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="ftDriverPickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
              <thead class="table-light"><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Status</th></tr></thead>
            </table>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>

  <!-- Vehicle picker modal -->
  <div class="modal fade" id="ftVehiclePickerModal" tabindex="-1" aria-labelledby="ftVehiclePickerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-fullscreen-sm-down">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ftVehiclePickerLabel">Select Vehicle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table id="ftVehiclePickerTable" class="table table-bordered table-hover align-middle" style="width:100%;cursor:pointer">
              <thead class="table-light"><tr><th>Ref</th><th>Plate</th><th>Make</th><th>Model</th><th>Type</th><th>Status</th></tr></thead>
            </table>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>

</div>

<style>
.fleet-tracker { display: flex; overflow: hidden; border-radius: 0 0 calc(0.375rem - 1px) calc(0.375rem - 1px); min-height: 720px; }
.fleet-tracker__panel--left { flex: 0 0 380px; background: #eef0f4; padding: 1.5rem; overflow-y: auto; }
.fleet-tracker__panel--right { flex: 1 1 auto; padding: 1.5rem; }

.ft-card { background: #fff; border-radius: .9rem; padding: 1rem; margin-bottom: 1rem; cursor: pointer; transition: background-color .15s, color .15s; }
.ft-card--active { background: #2f5bff; color: #fff; }
.ft-card--active .ft-pillar__label { color: rgba(255,255,255,.75); }
.ft-card--active .ft-pillar__value { color: #fff; }
.ft-card--active .ft-progress { background: rgba(255,255,255,.25); }

.ft-badge { font-size: .72rem; font-weight: 600; padding: .3rem .7rem; border-radius: 999px; }
.ft-badge--transit { background: #22c55e; color: #fff; }
.ft-badge--checking { background: #fde68a; color: #92620a; }
.ft-badge--delivered { background: #0d6efd; color: #fff; }
.ft-card--active .ft-badge--transit,
.ft-card--active .ft-badge--delivered { background: rgba(255,255,255,.25); }

.ft-progress { height: 6px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
.ft-progress__bar { height: 100%; background: #0d6efd; }
.ft-card--active .ft-progress__bar { background: #fff; }

.ft-pillars { display: flex; align-items: stretch; text-align: center; }
.ft-pillar { flex: 1 1 0; padding: 0 .25rem; min-width: 0; }
.ft-pillar--mid { border-left: 1px solid rgba(0,0,0,.08); border-right: 1px solid rgba(0,0,0,.08); }
.ft-card--active .ft-pillar--mid { border-color: rgba(255,255,255,.25); }
.ft-pillar__label { font-size: .65rem; text-transform: uppercase; letter-spacing: .03em; color: #9ca3af; margin-bottom: .15rem; }
.ft-pillar__value { font-size: .8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.ft-map-wrap { position: relative; height: 600px; border-radius: 1rem; overflow: hidden; }
.ft-map { height: 100%; background: #e9ebef; }

.ft-map-wrap:fullscreen { width: 100vw; height: 100vh; border-radius: 0; background: #e9ebef; }

.ft-map-fullscreen-btn {
  position: absolute; top: 1rem; right: 1rem; z-index: 4;
  width: 2.1rem; height: 2.1rem; padding: 0; display: flex; align-items: center; justify-content: center;
  box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.ft-map-fullscreen-btn--left { right: auto; left: 1rem; }

.ft-map-loading {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .75rem;
  background: #e9ebef; border-radius: 1rem;
  transition: opacity .35s ease;
}
.ft-map-loading--done { opacity: 0; pointer-events: none; }
.ft-map-loading__label { font-size: .85rem; color: #6b7280; }
.ft-map-loading__progress { height: 8px; width: 240px; max-width: 60%; }

.ft-tabs .nav-link { cursor: pointer; }

.ft-backtrack-range { padding: .75rem 1rem; background: #f7f8fa; border: 1px solid #eceef2; border-radius: .75rem; }
.ft-backtrack-range__row { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.ft-backtrack-range__label { font-size: .78rem; font-weight: 600; color: #6b7280; margin: 0; }
.ft-backtrack-range__input { width: auto; }
.ft-backtrack-range__quick { margin-left: auto; }
.ft-backtrack-range__error { font-size: .8rem; color: #dc3545; margin-top: .4rem; min-height: 1.1em; }

.ft-backtrack-meta { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; padding: .6rem 1rem; background: #f7f8fa; border: 1px solid #eceef2; border-radius: .75rem; font-size: .85rem; }
.ft-backtrack-meta__vehicle { color: #1f2937; }
.ft-backtrack-meta__range { color: #6b7280; }

.ft-backtrack-controls {
  position: absolute; left: 1rem; right: 1rem; bottom: 1rem; z-index: 4;
  display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
  padding: .6rem 1rem; background: rgba(255,255,255,.92); border-radius: .75rem;
  box-shadow: 0 2px 10px rgba(0,0,0,.1);
}
.ft-backtrack-seek { flex: 1 1 200px; }
.ft-backtrack-speeds .btn.active { background: #2f5bff; color: #fff; border-color: #2f5bff; }

.ft-map-wrap--backtrack { height: 760px; }

.ft-backtrack-clock {
  position: absolute; top: 1rem; right: 1rem; z-index: 3;
  background: rgba(255,255,255,.92); border-radius: .75rem; padding: .6rem 1.1rem; text-align: right;
  box-shadow: 0 2px 10px rgba(0,0,0,.1);
}
.ft-backtrack-clock__date { font-size: 1.15rem; font-weight: 700; color: #1f2937; line-height: 1.2; white-space: nowrap; }
.ft-backtrack-clock__time { font-size: .85rem; color: #6b7280; white-space: nowrap; }

.ft-backtrack-empty {
  position: absolute; inset: 0; z-index: 2;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem;
  background: #e9ebef; border-radius: 1rem; color: #9ca3af; font-size: .9rem;
}
.ft-backtrack-empty i { font-size: 1.75rem; }

.ft-maininfo { border-radius: 1rem; background: #f7f8fa; border: 1px solid #eceef2; padding: 1.25rem 1.5rem; }
.ft-info-section__title { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: .9rem; }
.ft-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem 1.5rem; }
.ft-info-item { display: flex; flex-direction: column; gap: .2rem; min-width: 0; }
.ft-info-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #9ca3af; }
.ft-info-value { font-size: .88rem; font-weight: 600; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

@media (max-width: 1199.98px) {
  .ft-info-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 575.98px) {
  .ft-info-grid { grid-template-columns: 1fr; }
}

@media (max-width: 991.98px) {
  .fleet-tracker { flex-direction: column; }
  .fleet-tracker__panel--left { flex: none; width: 100%; }
}
</style>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/operations/work_orders/fleet_tracker/fleet_tracker.js"></script>

<script>
var ftMap = null;
var ftMarkers = {};         // serial -> google.maps.Marker
var ftMarkerHeadings = {};  // serial -> last-drawn heading, for animating rotation too
var ftAnimations = {};      // serial -> requestAnimationFrame id of an in-flight move

var FT_REFRESH_INTERVAL = 15000; // matches the ~10s simulator ping rate with some slack
var FT_MIN_ZOOM = 12; // below this, ~100-300m of movement per refresh is sub-pixel and invisible
var FT_MAX_ZOOM = 17; // caps how far users can zoom in — street-level is enough, no need to go closer

var FT_MAP_STYLES = [
  { featureType: 'poi', stylers: [{ visibility: 'off' }] },
  { featureType: 'transit', stylers: [{ visibility: 'off' }] },
  { featureType: 'administrative.land_parcel', stylers: [{ visibility: 'off' }] },
  { featureType: 'administrative.neighborhood', stylers: [{ visibility: 'off' }] },
  { featureType: 'road', elementType: 'labels', stylers: [{ visibility: 'off' }] },
];

// Toggles the browser Fullscreen API on a map's wrapper div — shared by both the
// live map and the backtrack map. Google Maps doesn't notice its container being
// resized on its own, so the map is re-centred after a 'resize' trigger on every
// fullscreenchange (entering AND exiting).
function ftWireMapFullscreen(btnId, wrapId, map) {
  var btn = document.getElementById(btnId);
  var wrap = document.getElementById(wrapId);
  if (!btn || !wrap) return;

  btn.addEventListener('click', function () {
    if (document.fullscreenElement === wrap) {
      document.exitFullscreen();
    } else if (wrap.requestFullscreen) {
      wrap.requestFullscreen();
    }
  });

  document.addEventListener('fullscreenchange', function () {
    var isFullscreen = document.fullscreenElement === wrap;
    btn.innerHTML = isFullscreen ? '<i class="bi bi-fullscreen-exit"></i>' : '<i class="bi bi-arrows-fullscreen"></i>';

    var center = map.getCenter();
    google.maps.event.trigger(map, 'resize');
    if (center) map.setCenter(center);
  });
}

// ── Initial-load progress bar ───────────────────────────────────────────────
// The map area stays covered by a progress bar until every unit's first
// position has loaded, so users see load progress instead of a blank/laggy map.
var ftInitialLoadDone = false;
var ftTotalUnits = 0;
var ftLoadedUnits = 0;

function ftSetLoadingProgress(percent) {
  var bar = document.getElementById('ftMapLoadingBar');
  if (bar) bar.style.width = Math.min(Math.max(percent, 0), 100) + '%';
}

function ftMarkUnitLoaded() {
  if (ftInitialLoadDone) return;
  ftLoadedUnits++;
  ftSetLoadingProgress(ftTotalUnits ? (ftLoadedUnits / ftTotalUnits) * 100 : 100);
  if (ftLoadedUnits >= ftTotalUnits) ftCompleteInitialLoad();
}

function ftCompleteInitialLoad() {
  if (ftInitialLoadDone) return;
  ftInitialLoadDone = true;

  // One-time framing of the fleet as it stands right now — never runs again after
  // this, so it won't fight the user's own panning/zooming on later refreshes.
  var serials = Object.keys(ftMarkers);
  if (serials.length) {
    var bounds = new google.maps.LatLngBounds();
    serials.forEach(function (serial) { bounds.extend(ftMarkers[serial].getPosition()); });

    // A spread-out fleet can make fitBounds zoom out so far that per-refresh movement
    // becomes sub-pixel and invisible — floor it so whatever's on screen visibly moves,
    // even if that means not every truck fits in the initial view.
    google.maps.event.addListenerOnce(ftMap, 'bounds_changed', function () {
      if (ftMap.getZoom() < FT_MIN_ZOOM) ftMap.setZoom(FT_MIN_ZOOM);
    });
    ftMap.fitBounds(bounds);
  }

  var overlay = document.getElementById('ftMapLoading');
  if (overlay) overlay.classList.add('ft-map-loading--done');
}

var FT_FOCUS_ZOOM = 16; // close enough to make out the one vehicle and its immediate roads

// Called (from fleet_tracker.js) whenever the user picks a different card in the left
// panel — pans/zooms the map onto that vehicle so it doesn't get lost among the rest
// of the fleet. Only zooms in, never out, so re-clicking the same/nearby unit doesn't
// yank the view back out if the user already zoomed in further themselves.
function ftFocusOnUnit(serial) {
  var marker = ftMarkers[serial];
  if (!ftMap || !marker) return;
  ftMap.panTo(marker.getPosition());
  if (ftMap.getZoom() < FT_FOCUS_ZOOM) ftMap.setZoom(FT_FOCUS_ZOOM);
}

// Vehicle icons are always uploaded bonnet-right / rear-left (horizontal), i.e. drawn
// facing compass east — so that's the heading that needs zero extra rotation.
var FT_ICON_FORWARD_HEADING = 90;
var FT_TRUCK_W = 95;
var FT_TRUCK_H = 27;
var FT_TRUCK_CANVAS = 100; // square, big enough to hold the icon at any rotation without clipping

var ftTruckImage = new Image();
var ftTruckImageReady = new Promise(function (resolve) {
  ftTruckImage.onload = resolve;
  ftTruckImage.src = '/assets/trucks/map/truck_map_icon.png';
});

function ftRotatedTruckIconUrl(heading) {
  var canvas = document.createElement('canvas');
  canvas.width = FT_TRUCK_CANVAS;
  canvas.height = FT_TRUCK_CANVAS;
  var ctx = canvas.getContext('2d');
  ctx.translate(FT_TRUCK_CANVAS / 2, FT_TRUCK_CANVAS / 2);
  ctx.rotate((heading - FT_ICON_FORWARD_HEADING) * Math.PI / 180);
  ctx.drawImage(ftTruckImage, -FT_TRUCK_W / 2, -FT_TRUCK_H / 2, FT_TRUCK_W, FT_TRUCK_H);
  return canvas.toDataURL();
}

var ftInfoWindow = null;
var ftOpenInfoWindowSerial = null; // serial whose InfoWindow is currently open, so we can live-refresh it
var ftMarkerSpeeds = {}; // serial -> last known speed (km/h), shown in the click InfoWindow

function initFleetTrackerMap() {
  ftMap = new google.maps.Map(document.getElementById('ftMap'), {
    center: { lat: 6.9271, lng: 79.8612 }, // Colombo, Sri Lanka — fallback until live positions load
    zoom: 9, // low zoom (e.g. 8) makes a truck's ~100-300m/15s movement sub-pixel and invisible
    maxZoom: FT_MAX_ZOOM, // caps how far in a user can scroll/pinch-zoom, to limit tile load
    disableDefaultUI: true,
    styles: FT_MAP_STYLES,
  });

  ftInfoWindow = new google.maps.InfoWindow();
  ftInfoWindow.addListener('closeclick', function () { ftOpenInfoWindowSerial = null; });

  ftWireMapFullscreen('ftMapFullscreenBtn', 'ftMapWrap', ftMap);

  ftLoadPlateMap().then(function () {
    refreshFleetTrackerPositions();
    setInterval(refreshFleetTrackerPositions, FT_REFRESH_INTERVAL);
  });
}

// ── GPS serial → vehicle plate number, and plate → full vehicle record ─────
// The GPS device/vehicle link lives in the ERP DB (t_vehicle_gps), not the GPS
// simulation DB, so it's fetched once from the existing Vehicle GPS module
// rather than duplicating that join into a new endpoint. Full vehicle details
// (make/model/etc.) come from the Vehicle Master File list, joined here by
// plate number, for the "more info" panel's Vehicle Details section.
var ftPlateBySerial = {};
var ftVehicleByPlate = {};

function ftLoadPlateMap() {
  var gpsLinks = fetch('/modules/operations/fleet_maintenance/vehicle_gps/vehicle_gps_data.php?action=list')
    .then(function (res) { return res.json(); })
    .then(function (rows) {
      (rows || []).forEach(function (row) {
        var serial = row.m_gps_devices && row.m_gps_devices.serial_number;
        var plate = row.m_vehicles && row.m_vehicles.plate_number;
        if (serial && plate) ftPlateBySerial[serial] = plate;
      });
    })
    .catch(function (err) { console.error('Failed to load GPS device → vehicle mapping.', err); });

  var vehicles = fetch('/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list')
    .then(function (res) { return res.json(); })
    .then(function (rows) {
      (rows || []).forEach(function (row) {
        if (row.plate_number) ftVehicleByPlate[row.plate_number] = row;
      });
    })
    .catch(function (err) { console.error('Failed to load vehicle master file.', err); });

  return Promise.all([gpsLinks, vehicles]);
}

function ftInfoWindowContent(serial) {
  var speed = ftMarkerSpeeds[serial];
  var speedText = speed != null ? speed.toFixed(1) + ' km/h' : '—';
  return '<div style="font-size:.85rem"><strong>' + serial + '</strong><br>Speed: ' + speedText + '</div>';
}

// Compass bearing (initial azimuth) from point 1 → point 2, 0–360°.
function ftBearing(lat1, lon1, lat2, lon2) {
  var toRad = function (d) { return d * Math.PI / 180; };
  var toDeg = function (r) { return r * 180 / Math.PI; };
  var φ1 = toRad(lat1), φ2 = toRad(lat2);
  var Δλ = toRad(lon2 - lon1);
  var y = Math.sin(Δλ) * Math.cos(φ2);
  var x = Math.cos(φ1) * Math.sin(φ2) - Math.sin(φ1) * Math.cos(φ2) * Math.cos(Δλ);
  return (toDeg(Math.atan2(y, x)) + 360) % 360;
}

// The recorded `heading` on a single ping can be noisy, so derive it ourselves from
// two consecutive real points instead — much more accurate for which way to face the icon.
// Skipped while near-stationary (speed ~0), since the bearing between two almost-identical
// points is just noise; the vehicle's last known heading is kept instead.
// Fallback only — used when the unit has no route geometry (see ftPositionAndHeadingFromPath).
function ftComputeHeading(rows, targetIndex, fallbackHeading) {
  var to = rows[targetIndex];
  var from = rows[targetIndex + 1];
  var speed = parseFloat(to.speed) || 0;

  if (from && speed > 1) {
    var lat1 = parseFloat(from.latitude), lon1 = parseFloat(from.longitude);
    var lat2 = parseFloat(to.latitude), lon2 = parseFloat(to.longitude);
    if (lat1 !== lat2 || lon1 !== lon2) {
      return ftBearing(lat1, lon1, lat2, lon2);
    }
  }
  return fallbackHeading != null ? fallbackHeading : (parseFloat(to.heading) || 0);
}

// ── Route geometry (road-snapped position + heading) ───────────────────────
// Raw GPS pings wobble off the road and their point-to-point bearing can be wrong
// at curves/bridges. gps_path holds the OSRM-built road geometry for each unit's
// current trip; combined with route_progress_m from a ping, we can snap the marker
// exactly onto that road and face it along the road's true tangent direction.

var ftPaths = {};        // serial -> cached array of {seq, latitude, longitude, cumulative_distance_m}
var ftPathFetches = {};  // serial -> in-flight fetch Promise, so concurrent calls share one request
var ftLastProgress = {}; // serial -> last seen route_progress_m, to detect a new trip (path reset)

// serial -> cached {from_location, to_location, waiting_time_minutes, average_speed_kmh}.
// Fetched and refreshed alongside the path, since a new trip changes both together.
var ftTripInfo = {};

function ftEnsurePath(serial, routeProgressM) {
  var lastProgress = ftLastProgress[serial];
  var isNewTrip = lastProgress != null && routeProgressM < lastProgress - 50; // tolerance for GPS jitter
  ftLastProgress[serial] = routeProgressM;

  if (ftPaths[serial] && !isNewTrip) return Promise.resolve(ftPaths[serial]);
  if (ftPathFetches[serial] && !isNewTrip) return ftPathFetches[serial];

  var request = Promise.all([
    fetch('/server/gps/api/path.php?serial=' + encodeURIComponent(serial)).then(function (res) { return res.json(); }),
    fetch('/server/gps/api/trip.php?serial=' + encodeURIComponent(serial)).then(function (res) { return res.json(); }),
  ])
    .then(function (results) {
      var points = results[0], trip = results[1];
      ftPaths[serial] = Array.isArray(points) ? points : [];
      if (trip && !trip.error) ftTripInfo[serial] = trip;
      delete ftPathFetches[serial];
      return ftPaths[serial];
    })
    .catch(function (err) {
      console.error('Failed to load route path/trip info for ' + serial, err);
      delete ftPathFetches[serial];
      return null;
    });

  ftPathFetches[serial] = request;
  return request;
}

// Binary search on cumulative_distance_m for the two path points bracketing progressM.
function ftFindPathSegment(points, progressM) {
  if (!points || points.length < 2) return null;

  var last = points.length - 1;
  if (progressM <= parseFloat(points[0].cumulative_distance_m)) return [points[0], points[1]];
  if (progressM >= parseFloat(points[last].cumulative_distance_m)) return [points[last - 1], points[last]];

  var lo = 0, hi = last;
  while (hi - lo > 1) {
    var mid = Math.floor((lo + hi) / 2);
    if (parseFloat(points[mid].cumulative_distance_m) <= progressM) lo = mid; else hi = mid;
  }
  return [points[lo], points[hi]];
}

// Interpolated road-snapped position + road-tangent heading at progressM, or null
// if there's no usable route geometry (caller should fall back to raw ping data).
function ftPositionAndHeadingFromPath(points, progressM) {
  var segment = ftFindPathSegment(points, progressM);
  if (!segment) return null;

  var a = segment[0], b = segment[1];
  var distA = parseFloat(a.cumulative_distance_m), distB = parseFloat(b.cumulative_distance_m);
  var t = distB > distA ? Math.min(Math.max((progressM - distA) / (distB - distA), 0), 1) : 0;

  var latA = parseFloat(a.latitude), lonA = parseFloat(a.longitude);
  var latB = parseFloat(b.latitude), lonB = parseFloat(b.longitude);

  return {
    position: { lat: latA + (latB - latA) * t, lng: lonA + (lonB - lonA) * t },
    heading: ftBearing(latA, lonA, latB, lonB),
  };
}

// ── Left-panel vehicle cards ─────────────────────────────────────────────────
// Progress, status and the start/remaining/arrival pillars are all derived from
// the same route geometry (path.php) and live ping (data.php) already fetched
// for the map marker — no separate data source.

function ftFormatClockTime(date) {
  var h = date.getHours();
  var m = date.getMinutes();
  var ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12; if (h === 0) h = 12;
  return h + ':' + (m < 10 ? '0' + m : m) + ' ' + ampm;
}

function ftFormatDuration(seconds) {
  if (!isFinite(seconds) || seconds < 0) return '—';
  var totalMin = Math.round(seconds / 60);
  var h = Math.floor(totalMin / 60);
  var m = totalMin % 60;
  return h > 0 ? (h + 'h ' + m + 'm') : (m + 'm');
}

function ftUpdateOrderCard(serial, points, routeProgressM, speed, ping) {
  if (!window.ftApp) return;

  var totalLength = (points && points.length) ? parseFloat(points[points.length - 1].cumulative_distance_m) : 0;
  var progress = totalLength > 0 ? Math.min(100, (routeProgressM / totalLength) * 100) : 0;
  var delivered = totalLength > 0 && progress >= 99.5;

  var status, statusVariant, startTime, remaining, endTime;
  var now = new Date();

  if (delivered) {
    progress = 100;
    status = 'Delivered'; statusVariant = 'delivered';
    startTime = '—'; remaining = 'Arrived'; endTime = '—';
  } else if (speed > 1) {
    var speedMs = speed / 3.6;
    var remainingDistance = Math.max(totalLength - routeProgressM, 0);
    var remainingSeconds = remainingDistance / speedMs;
    var elapsedSeconds = routeProgressM / speedMs;
    status = 'In Transit'; statusVariant = 'transit';
    startTime = ftFormatClockTime(new Date(now.getTime() - elapsedSeconds * 1000));
    endTime = ftFormatClockTime(new Date(now.getTime() + remainingSeconds * 1000));
    remaining = ftFormatDuration(remainingSeconds);
  } else {
    status = 'Parked'; statusVariant = 'checking';
    startTime = '—'; remaining = 'Waiting'; endTime = '—';
  }

  var plate = ftPlateBySerial[serial] || 'Unassigned';
  var vehicle = ftVehicleByPlate[plate] || {};
  var trip = ftTripInfo[serial] || {};

  var card = {
    id: serial,
    plate: plate,
    status: status,
    statusVariant: statusVariant,
    progress: progress,
    startTime: startTime,
    remaining: remaining,
    endTime: endTime,

    // Trip details
    origin: trip.from_location || '—',
    destination: trip.to_location || '—',
    avgSpeedKmh: trip.average_speed_kmh != null ? parseFloat(trip.average_speed_kmh) : null,
    waitingMinutes: trip.waiting_time_minutes != null ? parseInt(trip.waiting_time_minutes, 10) : null,
    currentSpeed: speed,
    distanceKm: totalLength > 0 ? totalLength / 1000 : null,
    progressKm: totalLength > 0 ? routeProgressM / 1000 : null,
    odometerKm: ping && ping.odometer_km != null ? parseFloat(ping.odometer_km) : null,
    fuelPercentage: ping && ping.fuel_percentage != null ? parseFloat(ping.fuel_percentage) : null,
    engineRunning: ping ? !!parseInt(ping.engine_running, 10) : null,
    recordedAt: (ping && ping.recorded_at) || '—',

    // Vehicle details
    make: vehicle.make || '—',
    model: vehicle.model || '—',
    vehicleType: vehicle.type || '—',
    fuelType: vehicle.fuel_type || '—',
    vehicleStatus: vehicle.status || '—',
    year: vehicle.year || '—',
    capacity: vehicle.capacity || '—',
    mileage: vehicle.mileage || '—',
  };

  var orders = window.ftApp.orders;
  var idx = orders.findIndex(function (o) { return o.id === serial; });
  if (idx === -1) orders.push(card); else orders[idx] = card;
}

function refreshFleetTrackerPositions() {
  fetch('/server/gps/api/units.php')
    .then(function (res) { return res.json(); })
    .then(function (units) {
      console.log('[fleet-tracker] units.php →', units);

      if (!ftInitialLoadDone) {
        ftTotalUnits = units.length;
        ftLoadedUnits = 0;
        ftSetLoadingProgress(0);
        if (ftTotalUnits === 0) ftCompleteInitialLoad();
      }

      units.forEach(function (serial) {
        // limit=3, and we deliberately target the *previous* ping (index 1) rather than
        // the very latest — it buffers movement by ~one ping so the glide animation stays
        // smooth. That trades a small amount of real-time lag, which is fine here.
        // type=all (rather than 'location') so the main-info panel can also show
        // odometer/fuel/engine-running/recorded-at from the same ping.
        fetch('/server/gps/api/data.php?serial=' + encodeURIComponent(serial) + '&limit=3&type=all')
          .then(function (res) { return res.json(); })
          .then(function (rows) {
            console.log('[fleet-tracker] data.php (' + serial + ') →', rows);
            if (!rows.length) { ftMarkUnitLoaded(); return; }
            var targetIndex = rows.length > 1 ? 1 : 0;
            var target = rows[targetIndex];
            var routeProgressM = parseFloat(target.route_progress_m) || 0;
            var speed = parseFloat(target.speed) || 0;

            ftMarkerSpeeds[serial] = speed;
            if (ftOpenInfoWindowSerial === serial) {
              ftInfoWindow.setContent(ftInfoWindowContent(serial));
            }

            ftEnsurePath(serial, routeProgressM).then(function (points) {
              ftUpdateOrderCard(serial, points, routeProgressM, speed, target);

              var fromPath = ftPositionAndHeadingFromPath(points, routeProgressM);
              var position, heading;

              if (fromPath) {
                position = fromPath.position;
                heading = fromPath.heading;
              } else {
                position = { lat: parseFloat(target.latitude), lng: parseFloat(target.longitude) };
                heading = ftComputeHeading(rows, targetIndex, ftMarkerHeadings[serial]);
              }
              placeFleetTrackerMarker(serial, position, heading).then(ftMarkUnitLoaded);
            });
          })
          .catch(function (err) { console.error('Failed to load position for ' + serial, err); ftMarkUnitLoaded(); });
      });
    })
    .catch(function (err) { console.error('Failed to load GPS units.', err); ftCompleteInitialLoad(); });
}

function ftBuildIcon(heading) {
  return {
    url: ftRotatedTruckIconUrl(heading),
    scaledSize: new google.maps.Size(FT_TRUCK_CANVAS, FT_TRUCK_CANVAS),
    anchor: new google.maps.Point(FT_TRUCK_CANVAS / 2, FT_TRUCK_CANVAS / 2),
  };
}

// Shortest signed angular distance from `from` to `to`, so a turn from 350° to 10°
// animates forward through 360/0 instead of spinning the long way round.
function ftHeadingDelta(from, to) {
  return ((to - from + 540) % 360) - 180;
}

function placeFleetTrackerMarker(serial, position, heading) {
  return ftTruckImageReady.then(function () {
    var existing = ftMarkers[serial];

    if (!existing) {
      var marker = new google.maps.Marker({
        position: position,
        map: ftMap,
        title: serial,
        icon: ftBuildIcon(heading),
      });
      marker.addListener('click', function () {
        ftOpenInfoWindowSerial = serial;
        ftInfoWindow.setContent(ftInfoWindowContent(serial));
        ftInfoWindow.open(ftMap, marker);
      });
      ftMarkers[serial] = marker;
      ftMarkerHeadings[serial] = heading;
      return;
    }

    if (ftAnimations[serial]) cancelAnimationFrame(ftAnimations[serial]);

    var fromLatLng = existing.getPosition();
    var fromPos = { lat: fromLatLng.lat(), lng: fromLatLng.lng() };
    var fromHeading = ftMarkerHeadings[serial] != null ? ftMarkerHeadings[serial] : heading;
    var headingDelta = ftHeadingDelta(fromHeading, heading);
    var duration = FT_REFRESH_INTERVAL;
    var start = performance.now();

    var step = function (now) {
      var t = Math.min((now - start) / duration, 1);
      existing.setPosition({
        lat: fromPos.lat + (position.lat - fromPos.lat) * t,
        lng: fromPos.lng + (position.lng - fromPos.lng) * t,
      });
      existing.setIcon(ftBuildIcon(fromHeading + headingDelta * t));

      if (t < 1) {
        ftAnimations[serial] = requestAnimationFrame(step);
      } else {
        delete ftAnimations[serial];
        ftMarkerHeadings[serial] = heading;
      }
    };
    ftAnimations[serial] = requestAnimationFrame(step);
  });
}

// ── Backtrack map (single-vehicle historical trail, animated playback) ─────
// Separate map instance from the live fleet map, created lazily the first time
// the Backtrack tab is actually shown for a selected vehicle (see fleet_tracker.js,
// which calls ftPrepareBacktrack on tab/vehicle changes). Nothing is fetched from
// the DB just by opening the tab — the user picks a date range (max 7 days) and
// presses Load Trail, or one of the quick-range shortcuts, before anything loads.
//
// Once loaded, the trail isn't drawn all at once: the truck marker (same rotated
// icon as the live map, via ftBuildIcon) rides along the route while the line
// grows behind it, at a rate controlled by the play/pause + speed controls.

var ftBacktrackMap = null;
var ftBacktrackPolyline = null;
var ftBacktrackMarker = null;
var ftBacktrackSerial = null; // vehicle the Backtrack tab is currently set up for

var ftBacktrackPoints = [];  // chronological [{lat, lng, recorded_at}] for the loaded range
var ftBacktrackIndex = 0;    // last point drawn/placed
var ftBacktrackPlaying = false;
var ftBacktrackSpeed = 1;    // points advanced per tick
var ftBacktrackTimer = null;
var FT_BACKTRACK_TICK_MS = 60;
var FT_BACKTRACK_MAX_RANGE_DAYS = 7;

function ftEnsureBacktrackMap() {
  if (ftBacktrackMap) return ftBacktrackMap;
  ftBacktrackMap = new google.maps.Map(document.getElementById('ftBacktrackMap'), {
    center: { lat: 6.9271, lng: 79.8612 }, // Colombo, Sri Lanka — fallback until a trail loads
    zoom: 9,
    maxZoom: FT_MAX_ZOOM,
    disableDefaultUI: true,
    styles: FT_MAP_STYLES,
  });
  ftWireMapFullscreen('ftBacktrackFullscreenBtn', 'ftBacktrackMapWrap', ftBacktrackMap);
  return ftBacktrackMap;
}

// ── Big date/time readout (top-right of the map) ────────────────────────────
// Uses Intl.PluralRules' ordinal category to build "5th"/"1st"/"2nd"/"3rd" and
// Intl.DateTimeFormat for the month/year and a 12-hour time with AM/PM, rather
// than hand-rolling date string formatting.
var ftOrdinalRules = new Intl.PluralRules('en-US', { type: 'ordinal' });
var FT_ORDINAL_SUFFIXES = { one: 'st', two: 'nd', few: 'rd', other: 'th' };
var ftBacktrackMonthYearFormat = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
var ftBacktrackTimeFormat = new Intl.DateTimeFormat('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

function ftOrdinalSuffix(day) {
  return FT_ORDINAL_SUFFIXES[ftOrdinalRules.select(day)] || 'th';
}

// recorded_at comes back from MySQL as "2026-07-05 12:56:31" — swap the space for
// a "T" so the browser parses it as local time reliably across browsers.
function ftFormatBacktrackClock(recordedAt) {
  if (!recordedAt) return { date: '—', time: '' };
  var date = new Date(recordedAt.replace(' ', 'T'));
  if (isNaN(date.getTime())) return { date: recordedAt, time: '' };

  var day = date.getDate();
  return {
    date: day + ftOrdinalSuffix(day) + ' ' + ftBacktrackMonthYearFormat.format(date),
    time: ftBacktrackTimeFormat.format(date),
  };
}

// ── Date-range controls (From / To / Load Trail / quick ranges) ────────────

function ftBacktrackRangeEls() {
  return {
    from: document.getElementById('ftBacktrackFrom'),
    to: document.getElementById('ftBacktrackTo'),
    error: document.getElementById('ftBacktrackRangeError'),
    runBtn: document.getElementById('ftBacktrackRunBtn'),
    quickRange: document.getElementById('ftBacktrackQuickRange'),
  };
}

function ftBacktrackFormatDateInput(date) {
  var y = date.getFullYear();
  var m = String(date.getMonth() + 1).padStart(2, '0');
  var d = String(date.getDate()).padStart(2, '0');
  return y + '-' + m + '-' + d;
}

function ftBacktrackSetDefaultRange() {
  var els = ftBacktrackRangeEls();
  if (!els.from) return;
  var to = new Date();
  var from = new Date();
  from.setDate(from.getDate() - 1); // "Last Day" as a ready-to-go default, not auto-fetched
  els.to.value = ftBacktrackFormatDateInput(to);
  els.from.value = ftBacktrackFormatDateInput(from);
  if (els.error) els.error.textContent = '';
}

// Returns an error message, or null if the range is valid. Enforced here (not
// just server-side) so the user gets immediate feedback before any fetch.
function ftBacktrackValidateRange(fromStr, toStr) {
  if (!fromStr || !toStr) return 'Pick both a from and to date.';
  var from = new Date(fromStr + 'T00:00:00');
  var to = new Date(toStr + 'T00:00:00');
  if (isNaN(from.getTime()) || isNaN(to.getTime())) return 'Dates must look like YYYY-MM-DD.';
  if (from > to) return 'From date must be on or before the to date.';
  var diffDays = (to - from) / (1000 * 60 * 60 * 24);
  if (diffDays > FT_BACKTRACK_MAX_RANGE_DAYS) return 'Date range cannot be more than ' + FT_BACKTRACK_MAX_RANGE_DAYS + ' days.';
  return null;
}

function ftBacktrackRunRange(fromStr, toStr) {
  var els = ftBacktrackRangeEls();
  var error = ftBacktrackValidateRange(fromStr, toStr);
  if (els.error) els.error.textContent = error || '';
  if (error || !ftBacktrackSerial) return;
  ftBacktrackFetchRange(ftBacktrackSerial, fromStr, toStr);
}

// Wired once ever — the range controls live inside `v-if="activeId"`, which (in
// practice) only mounts/unmounts once, the first time any vehicle is picked.
var ftBacktrackRangeWired = false;
function ftBacktrackWireRangeControls() {
  if (ftBacktrackRangeWired) return;
  var els = ftBacktrackRangeEls();
  if (!els.runBtn) return;
  ftBacktrackRangeWired = true;

  els.runBtn.addEventListener('click', function () {
    ftBacktrackRunRange(els.from.value, els.to.value);
  });

  els.quickRange.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-quick-days]');
    if (!btn) return;
    var days = parseInt(btn.dataset.quickDays, 10);
    var to = new Date();
    var from = new Date();
    from.setDate(from.getDate() - days);
    els.from.value = ftBacktrackFormatDateInput(from);
    els.to.value = ftBacktrackFormatDateInput(to);
    ftBacktrackRunRange(els.from.value, els.to.value); // quick ranges fetch immediately
  });
}

// ── Playback controls (play/pause, seek, speed) ─────────────────────────────

function ftBacktrackControlEls() {
  return {
    playBtn: document.getElementById('ftBacktrackPlayBtn'),
    seek: document.getElementById('ftBacktrackSeek'),
    speeds: document.getElementById('ftBacktrackSpeeds'),
    clockDate: document.getElementById('ftBacktrackClockDate'),
    clockTime: document.getElementById('ftBacktrackClockTime'),
  };
}

function ftBacktrackSyncControls() {
  var els = ftBacktrackControlEls();

  var clock = ftFormatBacktrackClock(ftBacktrackPoints[ftBacktrackIndex] ? ftBacktrackPoints[ftBacktrackIndex].recorded_at : null);
  if (els.clockDate) els.clockDate.textContent = clock.date;
  if (els.clockTime) els.clockTime.textContent = clock.time;

  if (!els.playBtn) return; // playback controls only exist once a trail has loaded (v-if)

  els.playBtn.innerHTML = ftBacktrackPlaying ? '<i class="bi bi-pause-fill"></i>' : '<i class="bi bi-play-fill"></i>';
  els.seek.max = Math.max(ftBacktrackPoints.length - 1, 0);
  els.seek.value = ftBacktrackIndex;

  Array.prototype.forEach.call(els.speeds.querySelectorAll('button'), function (btn) {
    btn.classList.toggle('active', parseFloat(btn.dataset.speed) === ftBacktrackSpeed);
  });
}

// Reveals the polyline up to `index` and moves the truck marker to that point,
// facing along the bearing from the previous point (same math as the live map).
function ftBacktrackDrawUpTo(index) {
  if (!ftBacktrackPoints.length) return;
  index = Math.min(Math.max(index, 0), ftBacktrackPoints.length - 1);
  ftBacktrackIndex = index;

  var path = ftBacktrackPoints.slice(0, index + 1).map(function (p) { return { lat: p.lat, lng: p.lng }; });
  if (ftBacktrackPolyline) ftBacktrackPolyline.setPath(path);

  var current = ftBacktrackPoints[index];
  var prev = ftBacktrackPoints[index - 1];
  var heading = prev ? ftBearing(prev.lat, prev.lng, current.lat, current.lng) : 0;

  ftTruckImageReady.then(function () {
    if (!ftBacktrackMarker) {
      ftBacktrackMarker = new google.maps.Marker({ position: current, map: ftBacktrackMap, icon: ftBuildIcon(heading) });
    } else {
      ftBacktrackMarker.setPosition(current);
      ftBacktrackMarker.setIcon(ftBuildIcon(heading));
    }
  });

  ftBacktrackSyncControls();
}

function ftBacktrackStep() {
  if (ftBacktrackIndex >= ftBacktrackPoints.length - 1) { ftBacktrackPause(); return; }
  ftBacktrackDrawUpTo(ftBacktrackIndex + ftBacktrackSpeed);
}

function ftBacktrackPlay() {
  if (!ftBacktrackPoints.length || ftBacktrackPlaying) return;
  if (ftBacktrackIndex >= ftBacktrackPoints.length - 1) ftBacktrackIndex = 0; // replay from the start
  ftBacktrackPlaying = true;
  ftBacktrackTimer = setInterval(ftBacktrackStep, FT_BACKTRACK_TICK_MS);
  ftBacktrackSyncControls();
}

function ftBacktrackPause() {
  ftBacktrackPlaying = false;
  if (ftBacktrackTimer) { clearInterval(ftBacktrackTimer); ftBacktrackTimer = null; }
  ftBacktrackSyncControls();
}

// Wires the playback control bar's listeners; needs re-wiring on every load
// because that bar only enters the DOM once a trail has actually loaded
// (`v-if="backtrackInfo && backtrackInfo.count"` in fleet_tracker.php).
var ftBacktrackControlsWired = false;
function ftBacktrackWireControls() {
  if (ftBacktrackControlsWired) return;
  var els = ftBacktrackControlEls();
  if (!els.playBtn) return;
  ftBacktrackControlsWired = true;

  els.playBtn.addEventListener('click', function () {
    if (ftBacktrackPlaying) ftBacktrackPause(); else ftBacktrackPlay();
  });
  els.seek.addEventListener('input', function () {
    ftBacktrackPause();
    ftBacktrackDrawUpTo(parseInt(els.seek.value, 10));
  });
  els.speeds.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-speed]');
    if (btn) { ftBacktrackSpeed = parseFloat(btn.dataset.speed); ftBacktrackSyncControls(); }
  });
}

// Called (from fleet_tracker.js) whenever the Backtrack tab is shown or the
// selected vehicle changes. Resets to the idle state and defaults the date
// pickers — it deliberately does NOT fetch anything; that only happens once
// the user presses Load Trail or a quick-range button.
function ftPrepareBacktrack(serial) {
  if (typeof google === 'undefined' || !google.maps) return; // Maps script hasn't loaded yet

  var map = ftEnsureBacktrackMap();
  google.maps.event.trigger(map, 'resize');

  if (ftBacktrackSerial === serial) return; // already set up for this vehicle
  ftBacktrackSerial = serial;

  ftBacktrackPause();
  ftBacktrackPoints = [];
  ftBacktrackIndex = 0;
  ftBacktrackControlsWired = false;
  if (ftBacktrackPolyline) { ftBacktrackPolyline.setMap(null); ftBacktrackPolyline = null; }
  if (ftBacktrackMarker) { ftBacktrackMarker.setMap(null); ftBacktrackMarker = null; }

  if (window.ftApp) {
    window.ftApp.backtrackInfo = null;
    window.ftApp.backtrackLoading = false;
    window.ftApp.$nextTick(function () {
      ftBacktrackWireRangeControls();
      ftBacktrackSetDefaultRange();
    });
  }
}

// Fetches and plays one date range for one vehicle — triggered only by Load
// Trail or a quick-range button, never automatically.
function ftBacktrackFetchRange(serial, fromStr, toStr) {
  if (typeof google === 'undefined' || !google.maps) return;
  var map = ftEnsureBacktrackMap();

  ftBacktrackPause();
  ftBacktrackPoints = [];
  ftBacktrackIndex = 0;
  ftBacktrackControlsWired = false;
  if (ftBacktrackPolyline) { ftBacktrackPolyline.setMap(null); ftBacktrackPolyline = null; }
  if (ftBacktrackMarker) { ftBacktrackMarker.setMap(null); ftBacktrackMarker = null; }

  if (window.ftApp) {
    window.ftApp.backtrackInfo = null;
    window.ftApp.backtrackLoading = true;
  }

  // Include the whole "to" day, since the date input only carries a calendar date.
  var url = '/server/gps/api/range.php?serial=' + encodeURIComponent(serial)
    + '&from=' + encodeURIComponent(fromStr)
    + '&to=' + encodeURIComponent(toStr + ' 23:59:59');

  fetch(url)
    .then(function (res) { return res.json(); })
    .then(function (rows) {
      if (ftBacktrackSerial !== serial) return; // user switched vehicles while this was in flight
      if (window.ftApp) window.ftApp.backtrackLoading = false;

      if (!Array.isArray(rows) || !rows.length) {
        if (window.ftApp) window.ftApp.backtrackInfo = { count: 0, from: null, to: null };
        return;
      }

      // rows are newest-first (see range.php); reverse into chronological order
      // so playback runs oldest → newest, and the "range" label reads naturally.
      ftBacktrackPoints = rows.slice().reverse().map(function (r) {
        return { lat: parseFloat(r.latitude), lng: parseFloat(r.longitude), recorded_at: r.recorded_at };
      });

      if (window.ftApp) {
        window.ftApp.backtrackInfo = { count: rows.length, from: rows[rows.length - 1].recorded_at, to: rows[0].recorded_at };
      }

      // Frame the whole trail up front so the animation doesn't pan/zoom mid-playback.
      var bounds = new google.maps.LatLngBounds();
      ftBacktrackPoints.forEach(function (p) { bounds.extend(p); });
      map.fitBounds(bounds);

      ftBacktrackPolyline = new google.maps.Polyline({ path: [], map: map, strokeColor: '#2f5bff', strokeOpacity: 0.9, strokeWeight: 3 });

      // The playback controls only enter the DOM once backtrackInfo is set
      // (v-if in the template), so wire them up on next tick, then autoplay.
      if (window.ftApp) {
        window.ftApp.$nextTick(function () {
          ftBacktrackWireControls();
          ftBacktrackDrawUpTo(0);
          ftBacktrackPlay();
        });
      }
    })
    .catch(function (err) {
      console.error('Failed to load backtrack for ' + serial, err);
      if (window.ftApp) window.ftApp.backtrackLoading = false;
    });
}

window.ftPrepareBacktrack = ftPrepareBacktrack;
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars(GOOGLE_MAPS_API_KEY) ?>&callback=initFleetTrackerMap" async defer></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js" crossorigin="anonymous"></script>
<script>
$(document).ready(function () {

  const statusBadge = function (data) {
    const c = { active: 'success', inactive: 'danger' };
    return '<span class="badge text-bg-' + (c[data] || 'secondary') + '">' + data + '</span>';
  };

  // ── Driver picker ────────────────────────────────────────────────────────
  const driverPicker = $('#ftDriverPickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/driver_master_file/driver_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'name' },
      { data: 'phone' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#ftDriverPickerTable tbody').on('click', 'tr', function () {
    const data = driverPicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('ftDriverPickerModal')).hide();
    document.dispatchEvent(new CustomEvent('ft-driver-selected', { detail: data }));
  });

  $('#ftDriverPickerModal').on('shown.bs.modal', function () {
    driverPicker.ajax.reload(null, false);
    driverPicker.columns.adjust();
  });

  // ── Vehicle picker ───────────────────────────────────────────────────────
  const vehiclePicker = $('#ftVehiclePickerTable').DataTable({
    ajax: { url: '/modules/master_files/staff_fleet/vehicle_master_file/vehicle_master_file_data.php?action=list', dataSrc: '' },
    columns: [
      { data: 'ref' },
      { data: 'plate_number' },
      { data: 'make' },
      { data: 'model' },
      { data: 'type' },
      { data: 'status', render: statusBadge },
    ],
    pageLength: 10,
    searching: true,
    dom: '<"d-flex justify-content-between align-items-center mb-2"f>rtip',
    language: { search: 'Filter:', info: 'Showing _START_ to _END_ of _TOTAL_' },
  });

  $('#ftVehiclePickerTable tbody').on('click', 'tr', function () {
    const data = vehiclePicker.row(this).data();
    if (!data) return;
    bootstrap.Modal.getInstance(document.getElementById('ftVehiclePickerModal')).hide();
    document.dispatchEvent(new CustomEvent('ft-vehicle-selected', { detail: data }));
  });

  $('#ftVehiclePickerModal').on('shown.bs.modal', function () {
    vehiclePicker.ajax.reload(null, false);
    vehiclePicker.columns.adjust();
  });

});
</script>
