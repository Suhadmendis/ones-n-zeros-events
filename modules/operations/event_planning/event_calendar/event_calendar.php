<?php /* modules/operations/event_planning/event_calendar/event_calendar.php — read-only calendar view */ ?>

<div id="event-calendar-app" class="row g-4">
  <div class="col-12">
    <div class="card card-primary card-outline mb-4">
      <div class="card-header"><div class="card-title">{{ title }}</div></div>
      <div class="card-body">

        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary" @click="prevMonth"><i class="bi bi-chevron-left"></i></button>
            <button type="button" class="btn btn-outline-secondary" @click="goToday">Today</button>
            <button type="button" class="btn btn-outline-secondary" @click="nextMonth"><i class="bi bi-chevron-right"></i></button>
          </div>
          <h5 class="mb-0 ms-2">{{ monthLabel }}</h5>
          <span v-if="loading" class="spinner-border spinner-border-sm ms-2"></span>
          <button type="button" class="btn btn-outline-secondary btn-sm ms-auto module-help-btn" title="Help">
            <i class="bi bi-question-circle me-1"></i>Help
          </button>
        </div>

        <span v-if="error" class="text-danger small d-block mb-3">{{ error }}</span>

        <div class="ec-grid">
          <div class="ec-weekday" v-for="wd in weekdays" :key="wd">{{ wd }}</div>
          <div
            class="ec-day"
            v-for="cell in cells"
            :key="cell.key"
            :class="{ 'ec-day--outside': !cell.inMonth, 'ec-day--today': cell.isToday }"
          >
            <div class="ec-day__number">{{ cell.day }}</div>
            <div class="ec-day__events">
              <div
                v-for="ev in cell.events.slice(0, 3)"
                :key="ev.source + ev.ref"
                class="ec-event"
                :class="'ec-event--' + statusClass(ev.status)"
                :title="ev.title + (ev.time ? ' @ ' + ev.time : '')"
                @click="openEvent(ev)"
              >
                {{ ev.title }}
              </div>
              <div v-if="cell.events.length > 3" class="ec-event ec-event--more" @click="openDay(cell)">
                +{{ cell.events.length - 3 }} more
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Day detail modal -->
<div class="modal fade" id="ecDayModal" tabindex="-1" aria-labelledby="ecDayModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ecDayModalLabel">{{ activeDayLabel }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div v-if="!activeDayEvents.length" class="text-muted">No events on this day.</div>
        <ul class="list-group" v-else>
          <li class="list-group-item d-flex justify-content-between align-items-center" v-for="ev in activeDayEvents" :key="ev.source + ev.ref">
            <div>
              <div class="fw-semibold">{{ ev.title }}</div>
              <div class="small text-muted" v-if="ev.time">{{ ev.time }}</div>
            </div>
            <a :href="ev.url" class="btn btn-sm btn-outline-primary">Open</a>
          </li>
        </ul>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<style>
.ec-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: var(--bs-border-color); border: 1px solid var(--bs-border-color); border-radius: .375rem; overflow: hidden; }
.ec-weekday { background: var(--bs-tertiary-bg); padding: .5rem; text-align: center; font-weight: 600; font-size: .8rem; }
.ec-day { background: var(--bs-body-bg); min-height: 100px; padding: .35rem; display: flex; flex-direction: column; gap: .25rem; }
.ec-day--outside { background: var(--bs-tertiary-bg); opacity: .55; }
.ec-day--today .ec-day__number { background: var(--bs-primary); color: #fff; border-radius: 50%; width: 1.5rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center; }
.ec-day__number { font-size: .8rem; font-weight: 600; }
.ec-day__events { display: flex; flex-direction: column; gap: 2px; }
.ec-event { font-size: .72rem; padding: 1px 5px; border-radius: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer; color: #fff; }
.ec-event--scheduled { background: var(--bs-primary); }
.ec-event--completed { background: var(--bs-success); }
.ec-event--cancelled { background: var(--bs-secondary); }
.ec-event--more { background: transparent; color: var(--bs-body-color); text-decoration: underline; }
</style>

<script>const SYSTEM_NAME = '<?= htmlspecialchars($page) ?>';</script>
<script src="/modules/operations/event_planning/event_calendar/event_calendar.js"></script>
