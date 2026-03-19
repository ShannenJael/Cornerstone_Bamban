// Cornerstone Baptist Church Calendar Display
// Church events = Teal (#14AFB1)
// Academy events = Red (#D32F2F)

(function() {
    var calendarEvents = [];
    var currentMonth = new Date().getMonth();
    var currentYear = new Date().getFullYear();

    // Load calendar events from JSON
    function loadCalendarEvents(callback) {
        var jsonPath = window.location.pathname.includes('/pages/')
            ? '../data/calendar-events.json'
            : 'data/calendar-events.json';

        fetch(jsonPath + '?t=' + Date.now())
            .then(function(response) {
                if (!response.ok) throw new Error('Failed to load calendar');
                return response.json();
            })
            .then(function(data) {
                calendarEvents = data.events || [];
                if (callback) callback(calendarEvents);
            })
            .catch(function(error) {
                console.log('Calendar load error:', error);
                calendarEvents = [];
                if (callback) callback([]);
            });
    }

    // Parse date string to Date object
    function parseEventDate(dateStr) {
        if (!dateStr) return null;
        // Handle both "2026-02-15" and "2026-02-15T10:00:00" formats
        if (dateStr.includes('T')) {
            return new Date(dateStr);
        }
        // For date-only strings, parse as local date
        var parts = dateStr.split('-');
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }

    // Format time from date string
    function formatTime(dateStr) {
        if (!dateStr || !dateStr.includes('T')) return '';
        var date = new Date(dateStr);
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        var minStr = minutes < 10 ? '0' + minutes : minutes;
        return hours + ':' + minStr + ' ' + ampm;
    }

    // Format date for display
    function formatDate(dateStr) {
        var date = parseEventDate(dateStr);
        if (!date) return '';
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[date.getMonth()] + ' ' + date.getDate();
    }

    // Get events for a specific date
    function getEventsForDate(year, month, day) {
        var targetDate = new Date(year, month, day);
        var events = [];

        calendarEvents.forEach(function(event) {
            var startDate = parseEventDate(event.start);
            var endDate = event.end ? parseEventDate(event.end) : startDate;

            if (!startDate) return;

            // Check if target date falls within event range
            var targetTime = targetDate.getTime();
            var startTime = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate()).getTime();
            var endTime = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate()).getTime();

            if (targetTime >= startTime && targetTime <= endTime) {
                events.push(event);
            }
        });

        return events;
    }

    // Get upcoming events (from today onwards)
    function getUpcomingEvents(count) {
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var upcoming = calendarEvents.filter(function(event) {
            var eventDate = parseEventDate(event.start);
            return eventDate && eventDate >= today;
        });

        // Sort by date
        upcoming.sort(function(a, b) {
            return parseEventDate(a.start) - parseEventDate(b.start);
        });

        return upcoming.slice(0, count);
    }

    // Get events for a specific month
    function getEventsForMonth(year, month) {
        return calendarEvents.filter(function(event) {
            var startDate = parseEventDate(event.start);
            if (!startDate) return false;
            return startDate.getFullYear() === year && startDate.getMonth() === month;
        });
    }

    // Render upcoming events list (for home page)
    function renderUpcomingEvents(containerId, count) {
        var container = document.getElementById(containerId);
        if (!container) return;

        loadCalendarEvents(function(events) {
            var upcoming = getUpcomingEvents(count || 6);

            if (upcoming.length === 0) {
                container.innerHTML = '<p class="no-events">No upcoming events scheduled.</p>';
                return;
            }

            var html = '<div class="upcoming-events-list">';

            upcoming.forEach(function(event) {
                var date = parseEventDate(event.start);
                var dayNum = date.getDate();
                var monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                var monthName = monthNames[date.getMonth()];
                var timeStr = formatTime(event.start);
                var colorClass = event.source === 'academy' ? 'academy' : 'church';

                html += '<div class="upcoming-event-card ' + colorClass + '">';
                html += '<div class="event-date-badge">';
                html += '<span class="event-month">' + monthName + '</span>';
                html += '<span class="event-day">' + dayNum + '</span>';
                html += '</div>';
                html += '<div class="event-details">';
                html += '<h4 class="event-title">' + event.title + '</h4>';
                if (timeStr) {
                    html += '<p class="event-time"><i class="fas fa-clock"></i> ' + timeStr + '</p>';
                } else if (event.allDay) {
                    html += '<p class="event-time"><i class="fas fa-calendar-day"></i> All Day</p>';
                }
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
            html += '<div class="calendar-legend">';
            html += '<span class="legend-item church">Cornerstone Baptist Church</span>';
            html += '<span class="legend-item academy">Harvest Christian Academy</span>';
            html += '</div>';

            container.innerHTML = html;
        });
    }

    // Render monthly calendar grid (for events page)
    function renderMonthCalendar(containerId, listContainerId) {
        var container = document.getElementById(containerId);
        var listContainer = listContainerId ? document.getElementById(listContainerId) : null;
        if (!container) return;

        loadCalendarEvents(function(events) {
            renderCalendarGrid(container, listContainer);
        });
    }

    function renderCalendarGrid(container, listContainer) {
        var year = currentYear;
        var month = currentMonth;

        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
        var dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var today = new Date();

        var html = '<div class="calendar-header">';
        html += '<button class="cal-nav-btn" onclick="CalendarApp.prevMonth()"><i class="fas fa-chevron-left"></i></button>';
        html += '<h3 class="cal-month-title">' + monthNames[month] + ' ' + year + '</h3>';
        html += '<button class="cal-nav-btn" onclick="CalendarApp.nextMonth()"><i class="fas fa-chevron-right"></i></button>';
        html += '</div>';

        html += '<div class="calendar-legend">';
        html += '<span class="legend-item church">Cornerstone Baptist Church</span>';
        html += '<span class="legend-item academy">Harvest Christian Academy</span>';
        html += '</div>';

        html += '<div class="calendar-scroll">';
        html += '<div class="calendar-grid">';

        // Day headers
        dayNames.forEach(function(day) {
            html += '<div class="cal-day-header">' + day + '</div>';
        });

        // Empty cells before first day
        for (var i = 0; i < firstDay; i++) {
            html += '<div class="cal-day empty"></div>';
        }

        // Days of month
        for (var day = 1; day <= daysInMonth; day++) {
            var isToday = (today.getDate() === day && today.getMonth() === month && today.getFullYear() === year);
            var dayEvents = getEventsForDate(year, month, day);

            var classes = 'cal-day';
            if (isToday) classes += ' today';
            if (dayEvents.length > 0) classes += ' has-events';

            html += '<div class="' + classes + '" data-date="' + year + '-' + (month + 1) + '-' + day + '">';
            html += '<span class="day-number">' + day + '</span>';

            if (dayEvents.length > 0) {
                html += '<div class="day-events">';
                dayEvents.slice(0, 4).forEach(function(event) {
                    var colorClass = event.source === 'academy' ? 'academy' : 'church';
                    var truncated = event.title.length > 18 ? event.title.substring(0, 18) + 'â€¦' : event.title;
                    html += '<div class="event-text ' + colorClass + '" title="' + event.title + '">' + truncated + '</div>';
                });
                if (dayEvents.length > 4) {
                    html += '<div class="event-more">+' + (dayEvents.length - 4) + ' more</div>';
                }
                html += '</div>';
            }

            html += '</div>';
        }

        html += '</div>';
        html += '</div>';

        container.innerHTML = html;

        // Render events list for the month
        if (listContainer) {
            renderMonthEventsList(listContainer, year, month);
        }
    }

    function renderMonthEventsList(container, year, month) {
        var monthEvents = getEventsForMonth(year, month);

        // Sort by date
        monthEvents.sort(function(a, b) {
            return parseEventDate(a.start) - parseEventDate(b.start);
        });

        if (monthEvents.length === 0) {
            container.innerHTML = '<p class="no-events">No events scheduled for this month.</p>';
            return;
        }

        var html = '<div class="month-events-list">';

        monthEvents.forEach(function(event) {
            var date = parseEventDate(event.start);
            var dayNum = date.getDate();
            var dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            var dayName = dayNames[date.getDay()];
            var timeStr = formatTime(event.start);
            var colorClass = event.source === 'academy' ? 'academy' : 'church';

            html += '<div class="month-event-item ' + colorClass + '">';
            html += '<div class="event-date-col">';
            html += '<span class="event-day-num">' + dayNum + '</span>';
            html += '<span class="event-day-name">' + dayName + '</span>';
            html += '</div>';
            html += '<div class="event-info-col">';
            html += '<h4>' + event.title + '</h4>';
            if (timeStr) {
                html += '<p><i class="fas fa-clock"></i> ' + timeStr + '</p>';
            } else if (event.allDay) {
                html += '<p><i class="fas fa-calendar-day"></i> All Day</p>';
            }
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        container.innerHTML = html;
    }

    // Navigation functions
    function prevMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        var container = document.getElementById('calendarGrid');
        var listContainer = document.getElementById('monthEventsList');
        if (container) {
            renderCalendarGrid(container, listContainer);
        }
    }

    function nextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        var container = document.getElementById('calendarGrid');
        var listContainer = document.getElementById('monthEventsList');
        if (container) {
            renderCalendarGrid(container, listContainer);
        }
    }

    // Expose functions globally
    window.CalendarApp = {
        loadEvents: loadCalendarEvents,
        renderUpcoming: renderUpcomingEvents,
        renderMonth: renderMonthCalendar,
        prevMonth: prevMonth,
        nextMonth: nextMonth
    };
})();


