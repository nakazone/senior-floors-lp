/**
 * Smart Scheduling Engine - Frontend
 * Interface completa de agendamento inteligente
 */

let currentScheduleView = 'month';
let allSchedules = [];
let allCrews = [];
let currentDate = new Date();

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('schedulePage')) {
        loadScheduleData();
    }
});

// Load Schedule Data
async function loadScheduleData() {
    await Promise.all([
        loadCrews(),
        loadSchedules(),
        loadScheduleDashboard()
    ]);
    
    renderScheduleView();
}

// Load Crews
async function loadCrews() {
    try {
        const response = await fetch('/api/crews?active=true', { credentials: 'include' });
        const data = await response.json();
        
        if (data.success && data.data) {
            allCrews = data.data;
        }
    } catch (error) {
        console.error('Error loading crews:', error);
    }
}

// Load Schedules
async function loadSchedules() {
    try {
        const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).toISOString().split('T')[0];
        const endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 2, 0).toISOString().split('T')[0];
        
        const response = await fetch(`/api/schedules?start_date=${startDate}&end_date=${endDate}`, { 
            credentials: 'include' 
        });
        const data = await response.json();
        
        if (data.success && data.data) {
            allSchedules = data.data;
            renderScheduleView();
        }
    } catch (error) {
        console.error('Error loading schedules:', error);
    }
}

// Load Schedule Dashboard (Forecast)
async function loadScheduleDashboard() {
    try {
        const response = await fetch('/api/schedules', { credentials: 'include' });
        const data = await response.json();
        
        if (data.success && data.data) {
            const schedules = data.data;
            renderForecastDashboard(schedules);
        }
    } catch (error) {
        console.error('Error loading schedule dashboard:', error);
    }
}

// Render Forecast Dashboard
function renderForecastDashboard(schedules) {
    const container = document.getElementById('scheduleForecast');
    if (!container) return;
    
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    // Filtrar agendamentos do mês atual
    const monthSchedules = schedules.filter(s => {
        const scheduleDate = new Date(s.start_date);
        return scheduleDate.getMonth() === currentMonth && scheduleDate.getFullYear() === currentYear;
    });
    
    // Calcular métricas
    const totalSqft = monthSchedules.reduce((sum, s) => sum + (parseFloat(s.total_sqft) || 0), 0);
    const bookedDays = new Set(monthSchedules.map(s => s.start_date)).size;
    const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
    const bookedPercentage = (bookedDays / totalDays) * 100;
    
    const totalRevenue = monthSchedules.reduce((sum, s) => sum + (parseFloat(s.projected_profit) || 0), 0);
    const totalProfit = monthSchedules.reduce((sum, s) => {
        const profit = parseFloat(s.projected_profit) || 0;
        const margin = parseFloat(s.projected_margin) || 0;
        return sum + (profit * (margin / 100));
    }, 0);
    
    const overbookedCount = monthSchedules.filter(s => {
        // Verificar se há sobrecarga (simplificado)
        return false; // Será implementado com dados reais
    }).length;
    
    container.innerHTML = `
        <div class="stat-card">
            <h3>Monthly Capacity</h3>
            <div class="stat-value">${totalSqft.toLocaleString()} sqft</div>
            <div class="stat-details">
                <span>Booked: ${bookedPercentage.toFixed(1)}%</span>
            </div>
        </div>
        <div class="stat-card">
            <h3>Revenue Forecast</h3>
            <div class="stat-value">$${totalRevenue.toLocaleString()}</div>
            <div class="stat-details">
                <span>${monthSchedules.length} projects</span>
            </div>
        </div>
        <div class="stat-card">
            <h3>Profit Forecast</h3>
            <div class="stat-value">$${totalProfit.toLocaleString()}</div>
            <div class="stat-details">
                <span>This month</span>
            </div>
        </div>
        <div class="stat-card">
            <h3>Crew Utilization</h3>
            <div class="stat-value">${bookedPercentage.toFixed(1)}%</div>
            <div class="stat-details">
                <span>${bookedDays}/${totalDays} days</span>
            </div>
        </div>
    `;
}

// Show Schedule View
function showScheduleView(view) {
    currentScheduleView = view;
    
    // Hide all views
    document.getElementById('scheduleMonthView').style.display = 'none';
    document.getElementById('scheduleWeekView').style.display = 'none';
    document.getElementById('scheduleCrewView').style.display = 'none';
    
    // Show selected view
    if (view === 'month') {
        document.getElementById('scheduleMonthView').style.display = 'block';
        renderMonthView();
    } else if (view === 'week') {
        document.getElementById('scheduleWeekView').style.display = 'block';
        renderWeekView();
    } else if (view === 'crew') {
        document.getElementById('scheduleCrewView').style.display = 'block';
        renderCrewTimeline();
    }
}

// Render Month View
function renderMonthView() {
    const container = document.getElementById('scheduleCalendar');
    if (!container) return;
    
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
            <button class="btn btn-secondary" onclick="changeScheduleMonth(-1)">← Previous</button>
            <h3>${firstDay.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</h3>
            <button class="btn btn-secondary" onclick="changeScheduleMonth(1)">Next →</button>
        </div>
        <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;">
    `;
    
    // Day headers
    const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayHeaders.forEach(day => {
        html += `<div style="padding: 8px; text-align: center; font-weight: 600; background: var(--bg-light);">${day}</div>`;
    });
    
    // Empty cells for days before month starts
    for (let i = 0; i < startingDayOfWeek; i++) {
        html += '<div style="min-height: 80px; background: var(--bg-light);"></div>';
    }
    
    // Days of month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const daySchedules = allSchedules.filter(s => {
            const start = new Date(s.start_date);
            const end = new Date(s.end_date);
            const current = new Date(dateStr);
            return current >= start && current <= end;
        });
        
        const isToday = dateStr === new Date().toISOString().split('T')[0];
        
        html += `
            <div style="min-height: 80px; border: 1px solid var(--border-color); padding: 4px; background: ${isToday ? '#e3f2fd' : 'var(--bg-white)'};">
                <div style="font-weight: 600; margin-bottom: 4px;">${day}</div>
                ${daySchedules.map(schedule => `
                    <div class="schedule-item" 
                         data-schedule-id="${schedule.id}"
                         style="background: ${getScheduleColor(schedule)}; 
                                padding: 2px 4px; 
                                margin: 2px 0; 
                                border-radius: 4px; 
                                font-size: 0.75rem; 
                                cursor: pointer;
                                border-left: 3px solid ${getPriorityColor(schedule.priority)};"
                         onclick="viewSchedule(${schedule.id})"
                         title="${schedule.crew_name} - ${schedule.project_name || schedule.project_number}">
                        ${schedule.crew_name.substring(0, 8)}...
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// Render Week View
function renderWeekView() {
    const container = document.getElementById('scheduleWeekCalendar');
    if (!container) return;
    
    // Get current week
    const weekStart = new Date(currentDate);
    weekStart.setDate(currentDate.getDate() - currentDate.getDay());
    
    let html = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
            <button class="btn btn-secondary" onclick="changeScheduleWeek(-1)">← Previous</button>
            <h3>Week of ${weekStart.toLocaleDateString()}</h3>
            <button class="btn btn-secondary" onclick="changeScheduleWeek(1)">Next →</button>
        </div>
        <div style="display: grid; grid-template-columns: 100px repeat(7, 1fr); gap: 4px;">
    `;
    
    // Time slots (simplified - 8am to 6pm)
    const timeSlots = [];
    for (let hour = 8; hour <= 18; hour++) {
        timeSlots.push(`${hour}:00`);
    }
    
    // Day headers
    html += '<div></div>';
    for (let i = 0; i < 7; i++) {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + i);
        html += `<div style="padding: 8px; text-align: center; font-weight: 600; background: var(--bg-light);">${day.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' })}</div>`;
    }
    
    // Render schedule blocks (simplified)
    html += '</div>';
    container.innerHTML = html;
}

// Render Crew Timeline View
function renderCrewTimeline() {
    const container = document.getElementById('scheduleCrewTimeline');
    if (!container) return;
    
    let html = `
        <div style="margin-bottom: var(--spacing-md);">
            <h3>Crew Timeline View</h3>
        </div>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">Crew</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">Project</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">Start</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">End</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">Status</th>
                        <th style="padding: 8px; text-align: left; border-bottom: 2px solid var(--border-color);">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    allSchedules.forEach(schedule => {
        html += `
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">${schedule.crew_name}</td>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">${schedule.project_name || schedule.project_number || 'N/A'}</td>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">${new Date(schedule.start_date).toLocaleDateString()}</td>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">${new Date(schedule.end_date).toLocaleDateString()}</td>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">
                    <span class="badge badge-${schedule.status}">${schedule.status}</span>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid var(--border-color);">
                    <button class="btn btn-sm" onclick="viewSchedule(${schedule.id})">View</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Helper Functions
function getScheduleColor(schedule) {
    const statusColors = {
        scheduled: '#e3f2fd',
        in_progress: '#fff3e0',
        completed: '#e8f5e9',
        delayed: '#ffebee',
        cancelled: '#f3e5f5'
    };
    return statusColors[schedule.status] || '#f5f5f5';
}

function getPriorityColor(priority) {
    const colors = {
        high: '#f44336',
        normal: '#2196f3',
        low: '#4caf50'
    };
    return colors[priority] || '#2196f3';
}

function changeScheduleMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    loadSchedules();
}

function changeScheduleWeek(delta) {
    currentDate.setDate(currentDate.getDate() + (delta * 7));
    renderWeekView();
}

function viewSchedule(scheduleId) {
    alert(`Schedule ID: ${scheduleId}\nFeature: View schedule details (to be implemented)`);
}

function showNewScheduleModal() {
    alert('Feature: New schedule modal (to be implemented)');
}

// Make functions globally available
if (typeof window !== 'undefined') {
    window.showScheduleView = showScheduleView;
    window.changeScheduleMonth = changeScheduleMonth;
    window.changeScheduleWeek = changeScheduleWeek;
    window.viewSchedule = viewSchedule;
    window.showNewScheduleModal = showNewScheduleModal;
    window.loadScheduleDashboard = loadScheduleDashboard;
    window.loadScheduleData = loadScheduleData;
}
