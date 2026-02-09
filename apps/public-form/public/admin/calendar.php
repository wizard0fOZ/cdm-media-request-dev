<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_auth();

$pageTitle = "Event Calendar | CDM Admin";
include __DIR__ . "/partials/header.php";
?>

<!-- FullCalendar CSS + JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<style>
  /* FullCalendar dark mode overrides */
  .dark .fc {
    --fc-border-color: rgb(51 65 85);
    --fc-page-bg-color: rgb(30 41 59);
    --fc-neutral-bg-color: rgb(15 23 42);
    --fc-list-event-hover-bg-color: rgb(51 65 85);
    --fc-today-bg-color: rgba(59, 130, 246, 0.08);
    --fc-neutral-text-color: rgb(148 163 184);
  }
  .dark .fc .fc-col-header-cell-cushion,
  .dark .fc .fc-daygrid-day-number,
  .dark .fc .fc-list-day-text,
  .dark .fc .fc-list-day-side-text {
    color: rgb(226 232 240);
  }
  .dark .fc .fc-button-primary {
    background-color: rgb(51 65 85);
    border-color: rgb(71 85 105);
    color: rgb(226 232 240);
  }
  .dark .fc .fc-button-primary:hover {
    background-color: rgb(71 85 105);
  }
  .dark .fc .fc-button-primary:not(:disabled).fc-button-active,
  .dark .fc .fc-button-primary:not(:disabled):active {
    background-color: rgb(30 58 138);
    border-color: rgb(59 130 246);
  }
  .dark .fc .fc-toolbar-title {
    color: rgb(248 250 252);
  }
  .dark .fc .fc-list-event-title a {
    color: rgb(226 232 240);
  }
  .fc .fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-size: 0.8rem;
    padding: 1px 4px;
  }
  .fc .fc-daygrid-event-dot {
    display: none;
  }
  /* Tooltip */
  .cal-tooltip {
    position: absolute;
    z-index: 9999;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    font-size: 0.8125rem;
    max-width: 280px;
    pointer-events: none;
  }
  .dark .cal-tooltip {
    background: rgb(30 41 59);
    border-color: rgb(51 65 85);
    color: rgb(226 232 240);
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
  }
</style>

<main class="mx-auto max-w-7xl px-4 py-8"
      x-data="calendarApp()"
      x-init="initCalendar()">

  <!-- Page Header -->
  <div class="mb-6">
    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">Event Calendar</h1>
    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
      View upcoming events from all media requests
    </p>
  </div>

  <!-- Filters -->
  <div class="mb-6 rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div class="flex flex-wrap items-center gap-4">
      <!-- Status -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Status</label>
        <select x-model="filterStatus" @change="refetch()"
                class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>

      <!-- Service -->
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Service</label>
        <select x-model="filterService" @change="refetch()"
                class="rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm text-slate-900 dark:text-slate-100 focus:border-slate-900 dark:focus:border-slate-100 focus:outline-none focus:ring-1 focus:ring-slate-900 dark:focus:ring-slate-100">
          <option value="">All Services</option>
          <option value="av">AV</option>
          <option value="media">Media / Design</option>
          <option value="photo">Photography</option>
        </select>
      </div>

      <!-- Late Only -->
      <div class="flex items-end">
        <label class="flex items-center gap-2 cursor-pointer py-2 mt-4">
          <input type="checkbox" x-model="filterLate" @change="refetch()"
                 class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-amber-600 focus:ring-amber-500">
          <span class="text-sm text-slate-700 dark:text-slate-300">Late only</span>
        </label>
      </div>

      <!-- Legend -->
      <div class="ml-auto flex flex-wrap items-center gap-3 text-xs">
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#f59e0b"></span> Pending</span>
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#22c55e"></span> Approved</span>
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#3b82f6"></span> In Progress</span>
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#10b981"></span> Completed</span>
        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded" style="background:#ef4444"></span> Rejected</span>
      </div>
    </div>
  </div>

  <!-- Calendar -->
  <div class="rounded-xl bg-white dark:bg-slate-800 p-4 shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
    <div id="calendar"></div>
  </div>

</main>

<script>
function calendarApp() {
  return {
    calendar: null,
    filterStatus: '',
    filterService: '',
    filterLate: false,
    tooltip: null,

    initCalendar() {
      const self = this;
      const calendarEl = document.getElementById('calendar');

      this.calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
          today: 'Today',
          month: 'Month',
          week: 'Week',
          list: 'List'
        },
        height: 'auto',
        navLinks: true,
        nowIndicator: true,
        dayMaxEvents: 4,
        eventTimeFormat: {
          hour: 'numeric',
          minute: '2-digit',
          meridiem: 'short'
        },

        events: function(info, successCallback, failureCallback) {
          const params = new URLSearchParams({
            start: info.startStr,
            end: info.endStr,
          });
          if (self.filterStatus) params.set('status', self.filterStatus);
          if (self.filterService) params.set('service', self.filterService);
          if (self.filterLate) params.set('late', '1');

          fetch('api/calendar_events.php?' + params.toString())
            .then(r => r.json())
            .then(data => successCallback(data))
            .catch(err => {
              console.error('Calendar fetch error:', err);
              failureCallback(err);
            });
        },

        eventClick: function(info) {
          info.jsEvent.preventDefault();
          if (info.event.url) {
            window.location = info.event.url;
          }
        },

        eventMouseEnter: function(info) {
          const props = info.event.extendedProps;
          const svcLabels = { av: 'AV', media: 'Media', photo: 'Photo' };
          const statusLabels = {
            pending: 'Pending', approved: 'Approved', rejected: 'Rejected',
            in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled'
          };

          const svcs = (props.services || []).map(s => svcLabels[s] || s).join(', ');
          const status = statusLabels[props.status] || props.status;
          const late = props.isLate ? ' <span style="color:#f59e0b;font-weight:600">LATE</span>' : '';

          const el = document.createElement('div');
          el.className = 'cal-tooltip';
          el.innerHTML = `
            <div style="font-weight:600;margin-bottom:4px">${info.event.title}</div>
            <div>Status: <strong>${status}</strong>${late}</div>
            ${svcs ? '<div>Services: ' + svcs + '</div>' : ''}
            ${info.event.start ? '<div>Time: ' + info.event.start.toLocaleTimeString([], {hour:'numeric',minute:'2-digit'}) + (info.event.end ? ' - ' + info.event.end.toLocaleTimeString([], {hour:'numeric',minute:'2-digit'}) : '') + '</div>' : ''}
          `;

          document.body.appendChild(el);
          self.tooltip = el;

          const rect = info.el.getBoundingClientRect();
          el.style.top = (rect.bottom + window.scrollY + 8) + 'px';
          el.style.left = Math.min(rect.left + window.scrollX, window.innerWidth - 300) + 'px';
        },

        eventMouseLeave: function() {
          if (self.tooltip) {
            self.tooltip.remove();
            self.tooltip = null;
          }
        },

        windowResize: function(arg) {
          if (window.innerWidth < 768) {
            self.calendar.changeView('listWeek');
          }
        }
      });

      this.calendar.render();
    },

    refetch() {
      if (this.calendar) {
        this.calendar.refetchEvents();
      }
    }
  };
}
</script>

<?php include __DIR__ . "/partials/footer.php"; ?>
