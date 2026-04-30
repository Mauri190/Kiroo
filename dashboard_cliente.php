<?php
require_once 'config.php';

// Verificar autenticación y tipo de usuario
if (!isLoggedIn()) {
    header('Location: login.html');
    exit;
}

if (getCurrentUserType() !== 'cliente') {
    header('Location: dashboard_mecanico.php');
    exit;
}

$user_id = getCurrentUserId();
$full_name = getCurrentUserFullName();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiroo - Panel Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-red: #d32f2f; --bg-dark: #121212; --bg-card: #1e1e1e; }
        body { background-color: var(--bg-dark); color: white; font-family: 'Segoe UI', sans-serif; }
        .navbar-kiroo { background: linear-gradient(135deg, var(--primary-red) 0%, #a12626 100%); padding: 0.8rem 2rem; }
        .content-card { background: var(--bg-card); border-radius: 20px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 25px; overflow: hidden; }
        .card-header-custom { background: rgba(0,0,0,0.3); padding: 1rem 1.5rem; border-bottom: 1px solid #2c2c2c; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .btn-red { background-color: var(--primary-red); border: none; color: white; border-radius: 30px; padding: 8px 20px; transition: 0.2s; }
        .btn-red:hover { background-color: #b71c1c; }
        .diagnostic-card, .appointment-card { background: #2a2a2a; border-radius: 16px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid var(--primary-red); }
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .status-completado { background: #4caf50; }
        .status-pendiente { background: #ff9800; color: #000; }
        .status-confirmada { background: #2196f3; }
        .tab-btn { background: transparent; border: none; color: #aaa; padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; transition: 0.2s; }
        .tab-btn.active { color: white; border-bottom-color: var(--primary-red); }
        .chat-container { height: 300px; overflow-y: auto; padding: 15px; background: #1a1a1a; border-radius: 15px; margin-bottom: 15px; }
        .chat-bubble { padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; max-width: 80%; clear: both; }
        .chat-sent { background: var(--primary-red); float: right; }
        .chat-received { background: #333; float: left; }
        .modal-content { background-color: var(--bg-card); color: white; }
        .form-control, .form-select { background-color: #2c2c2c; border-color: #444; color: white; }
        .form-control:focus, .form-select:focus { background-color: #333; border-color: var(--primary-red); color: white; box-shadow: none; }
        .rating-stars { color: #ffc107; cursor: pointer; font-size: 1.5rem; }
        .vehicle-badge { background: #333; border-radius: 10px; padding: 3px 10px; font-size: 0.75rem; }
        .toast-notification { position: fixed; bottom: 20px; right: 20px; z-index: 9999; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-kiroo">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-car"></i> Kiroo Cliente</a>
        <div class="d-flex align-items-center gap-2">
            <span class="me-2"><i class="fa-regular fa-user"></i> <span id="userNameDisplay"><?php echo htmlspecialchars($full_name); ?></span></span>
            <button class="btn btn-sm btn-outline-light" onclick="logout()"><i class="fa-solid fa-sign-out-alt"></i> Salir</button>
        </div>
    </div>
</nav>

<div class="container my-4">
    <ul class="nav nav-tabs border-0 mb-4" id="dashboardTabs">
        <li class="nav-item"><button class="tab-btn active" onclick="switchTab('diagnostics')"><i class="fa-solid fa-clipboard-check me-1"></i>Diagnósticos</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('appointments')"><i class="fa-solid fa-calendar-check me-1"></i>Citas</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('vehicles')"><i class="fa-solid fa-car me-1"></i>Vehículos</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('agenda')"><i class="fa-solid fa-calendar me-1"></i>Agenda</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('chat')"><i class="fa-solid fa-comments me-1"></i>Chat</button></li>
    </ul>

    <!-- TAB: DIAGNÓSTICOS -->
    <div id="tab-diagnostics" class="tab-content-panel">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-clipboard-check"></i> Diagnósticos recibidos</span>
                <button class="btn btn-red btn-sm" onclick="loadDiagnostics()"><i class="fa-solid fa-refresh"></i> Actualizar</button>
            </div>
            <div class="p-3" id="diagnosticsList">
                <div class="text-center text-muted p-4">Cargando diagnósticos...</div>
            </div>
        </div>
    </div>

    <!-- TAB: CITAS -->
    <div id="tab-appointments" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-calendar-check"></i> Mis Citas</span>
                <button class="btn btn-red btn-sm" onclick="showNewAppointmentModal()"><i class="fa-solid fa-plus"></i> Solicitar Cita</button>
            </div>
            <div class="p-3" id="appointmentsList">
                <div class="text-center text-muted p-4">Cargando citas...</div>
            </div>
        </div>
    </div>

    <!-- TAB: VEHÍCULOS -->
    <div id="tab-vehicles" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-car"></i> Mis Vehículos</span>
                <button class="btn btn-red btn-sm" onclick="showVehicleModal()"><i class="fa-solid fa-plus"></i> Agregar Vehículo</button>
            </div>
            <div class="p-3" id="vehiclesList">
                <div class="text-center text-muted p-4">Cargando vehículos...</div>
            </div>
        </div>
    </div>

    <!-- TAB: AGENDA -->
    <div id="tab-agenda" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-calendar"></i> Mi Agenda</span>
                <button class="btn btn-red btn-sm" onclick="showEventModal()"><i class="fa-solid fa-plus"></i> Agregar Evento</button>
            </div>
            <div class="p-3" id="eventsList">
                <div class="text-center text-muted p-4">Cargando eventos...</div>
            </div>
        </div>
    </div>

    <!-- TAB: CHAT -->
    <div id="tab-chat" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-comments"></i> Chat con Mecánicos</span>
                <select id="mechanicSelect" class="form-select form-select-sm" style="width: auto;" onchange="loadChatMessages()">
                    <option value="">Seleccionar mecánico...</option>
                </select>
            </div>
            <div class="p-3">
                <div class="chat-container" id="chatMessages">
                    <div class="text-center text-muted">Selecciona un mecánico para comenzar</div>
                </div>
                <div class="input-group mt-2">
                    <input type="text" class="form-control" id="chatInput" placeholder="Escribe un mensaje...">
                    <button class="btn btn-red" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Vehículo -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleModalTitle">Agregar Vehículo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="vehicleId">
                <div class="mb-3">
                    <label class="form-label">Marca *</label>
                    <input type="text" class="form-control" id="vehicleBrand" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Modelo *</label>
                    <input type="text" class="form-control" id="vehicleModel" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Placa *</label>
                    <input type="text" class="form-control" id="vehiclePlate" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Año</label>
                    <input type="number" class="form-control" id="vehicleYear">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kilometraje</label>
                    <input type="number" class="form-control" id="vehicleMileage">
                </div>
                <div class="mb-3">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control" id="vehicleColor">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" id="vehicleNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-red" onclick="saveVehicle()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Cita -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Solicitar Cita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="appointmentId">
                <div class="mb-3">
                    <label class="form-label">Mecánico *</label>
                    <select class="form-select" id="appointmentMechanic" required>
                        <option value="">Seleccionar mecánico...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Vehículo</label>
                    <select class="form-select" id="appointmentVehicle">
                        <option value="">Seleccionar vehículo (opcional)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha *</label>
                    <input type="date" class="form-control" id="appointmentDate" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hora *</label>
                    <input type="time" class="form-control" id="appointmentTime" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas / Motivo</label>
                    <textarea class="form-control" id="appointmentNotes" rows="2" placeholder="Describe el motivo de tu cita..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-red" onclick="saveAppointment()">Solicitar Cita</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Evento -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Evento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eventId">
                <div class="mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" id="eventTitle" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha *</label>
                    <input type="date" class="form-control" id="eventDate" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hora *</label>
                    <input type="time" class="form-control" id="eventTime" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="eventType">
                        <option value="mantenimiento">🔧 Mantenimiento</option>
                        <option value="reparacion">⚙️ Reparación</option>
                        <option value="inspeccion">📋 Inspección</option>
                        <option value="lavado">🧼 Lavado</option>
                        <option value="otro">📌 Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" id="eventDescription" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-red" onclick="saveEvent()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Calificación -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Calificar Diagnóstico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>¿Qué te pareció el diagnóstico de <strong id="ratingMechanicName"></strong>?</p>
                <div class="rating-stars" id="ratingStars">
                    <i class="fa-regular fa-star" data-rating="1"></i>
                    <i class="fa-regular fa-star" data-rating="2"></i>
                    <i class="fa-regular fa-star" data-rating="3"></i>
                    <i class="fa-regular fa-star" data-rating="4"></i>
                    <i class="fa-regular fa-star" data-rating="5"></i>
                </div>
                <textarea id="ratingComment" class="form-control mt-3" placeholder="Comentario (opcional)" rows="2"></textarea>
                <input type="hidden" id="ratingDiagnosticId">
                <button class="btn btn-red mt-3 w-100" onclick="submitRating()">Enviar Calificación</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast para notificaciones -->
<div class="toast-notification" id="toastNotification" style="display: none;">
    <div class="bg-success text-white p-3 rounded shadow">
        <i class="fa-solid fa-check-circle me-2"></i><span id="toastMessage"></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let currentMechanicId = null;
    let vehicleModalInstance, appointmentModalInstance, eventModalInstance, ratingModalInstance;
    let refreshInterval = null;

    // ========== UTILIDADES ==========
    function showToast(message, isError = false) {
        const toast = document.getElementById('toastNotification');
        const toastMsg = document.getElementById('toastMessage');
        toastMsg.textContent = message;
        toast.style.backgroundColor = isError ? '#dc3545' : '#28a745';
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
    }

    function showLoading(elementId, message = 'Cargando...') {
        document.getElementById(elementId).innerHTML = `<div class="text-center text-muted p-4"><i class="fa-solid fa-spinner fa-spin me-2"></i>${message}</div>`;
    }

    async function apiCall(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        Object.keys(data).forEach(key => formData.append(key, data[key]));
        
        const response = await fetch('api.php', { method: 'POST', body: formData });
        return await response.json();
    }

    function switchTab(tab) {
        document.querySelectorAll('.tab-content-panel').forEach(t => t.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).style.display = 'block';
        event.target.classList.add('active');
        
        // Cargar datos según la pestaña
        if (tab === 'diagnostics') loadDiagnostics();
        else if (tab === 'appointments') loadAppointments();
        else if (tab === 'vehicles') loadVehicles();
        else if (tab === 'agenda') loadEvents();
        else if (tab === 'chat') loadMechanics();
    }

    // ========== DIAGNÓSTICOS ==========
    async function loadDiagnostics() {
        showLoading('diagnosticsList', 'Cargando diagnósticos...');
        const result = await apiCall('get_diagnostics');
        if (result.success && result.diagnostics.length > 0) {
            renderDiagnostics(result.diagnostics);
        } else {
            document.getElementById('diagnosticsList').innerHTML = '<div class="text-center text-muted p-4"><i class="fa-solid fa-clipboard fa-2x mb-2 d-block"></i>No has recibido ningún diagnóstico aún.</div>';
        }
    }

    function renderDiagnostics(diagnostics) {
        const container = document.getElementById('diagnosticsList');
        container.innerHTML = diagnostics.map(d => {
            const conditionClass = { bueno: 'bg-success', regular: 'bg-warning text-dark', malo: 'bg-danger', critico: 'bg-dark' }[d.vehicle_condition] || 'bg-secondary';
            return `
            <div class="diagnostic-card">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap">
                    <div>
                        <strong>🔧 Diagnóstico #${d.id}</strong>
                        <small class="text-muted d-block">${new Date(d.created_at).toLocaleDateString('es-ES')} por <strong>${d.mechanic_name || 'Mecánico'}</strong></small>
                    </div>
                    <span class="badge-status status-completado">Completado</span>
                </div>
                ${d.vehicle_name ? `<div class="mb-2"><span class="vehicle-badge">🚗 ${d.vehicle_name}</span></div>` : ''}
                <div class="bg-dark rounded p-2 mb-2">
                    <div class="small text-muted">Diagnóstico:</div>
                    <div>${d.diagnosis}</div>
                </div>
                <div class="bg-dark rounded p-2 mb-2">
                    <div class="small text-muted">Recomendaciones:</div>
                    <div>${d.recommendation}</div>
                </div>
                <div class="d-flex gap-2 flex-wrap mt-2">
                    ${d.vehicle_condition ? `<span class="badge ${conditionClass}">Estado: ${d.vehicle_condition}</span>` : ''}
                    ${d.estimated_cost ? `<span class="badge bg-info">💰 $${parseFloat(d.estimated_cost).toFixed(2)}</span>` : ''}
                </div>
                ${!d.rated ? `<button class="btn btn-sm btn-warning mt-2" onclick="openRatingModal(${d.id}, '${d.mechanic_name}')"><i class="fa-solid fa-star"></i> Calificar</button>` : `<span class="badge bg-success mt-2">✓ Calificado ${'⭐'.repeat(d.rating)}</span>`}
            </div>`;
        }).join('');
    }

    function openRatingModal(diagnosticId, mechanicName) {
        document.getElementById('ratingDiagnosticId').value = diagnosticId;
        document.getElementById('ratingMechanicName').innerText = mechanicName;
        document.querySelectorAll('#ratingStars i').forEach(s => s.className = 'fa-regular fa-star');
        document.getElementById('ratingComment').value = '';
        ratingModalInstance.show();
    }

    async function submitRating() {
        const diagnosticId = document.getElementById('ratingDiagnosticId').value;
        const rating = document.querySelectorAll('#ratingStars .fa-solid.fa-star').length;
        const comment = document.getElementById('ratingComment').value;
        
        if (rating === 0) {
            showToast('Selecciona una calificación', true);
            return;
        }
        
        const result = await apiCall('rate_diagnostic', { diagnostic_id: diagnosticId, rating: rating, comment: comment });
        if (result.success) {
            showToast('Calificación enviada, ¡gracias!');
            ratingModalInstance.hide();
            loadDiagnostics();
        } else {
            showToast('Error al enviar calificación', true);
        }
    }

    // ========== CITAS ==========
    async function loadAppointments() {
        showLoading('appointmentsList', 'Cargando citas...');
        const result = await apiCall('get_appointments');
        if (result.success && result.appointments.length > 0) {
            renderAppointments(result.appointments);
        } else {
            document.getElementById('appointmentsList').innerHTML = '<div class="text-center text-muted p-4"><i class="fa-solid fa-calendar fa-2x mb-2 d-block"></i>No tienes citas registradas.<br><button class="btn btn-red btn-sm mt-2" onclick="showNewAppointmentModal()">Solicitar Cita</button></div>';
        }
    }

    function renderAppointments(appointments) {
        const container = document.getElementById('appointmentsList');
        container.innerHTML = appointments.map(a => `
            <div class="appointment-card">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <strong>📅 ${a.appointment_date} — ${a.appointment_time}</strong>
                        <div class="small text-muted">Mecánico: ${a.mechanic_name || 'Mecánico'}</div>
                        ${a.notes ? `<div class="small text-muted mt-1">📝 ${a.notes}</div>` : ''}
                    </div>
                    <span class="badge-status ${a.status === 'completado' ? 'status-completado' : (a.status === 'confirmada' ? 'status-confirmada' : 'status-pendiente')}">${a.status}</span>
                </div>
                ${a.diagnostic_id ? `<div class="mt-2 small text-success"><i class="fa-solid fa-check-circle"></i> Diagnóstico disponible en la pestaña "Diagnósticos"</div>` : ''}
                ${a.status !== 'completado' ? `<button class="btn btn-sm btn-outline-danger mt-2" onclick="cancelAppointment(${a.id})"><i class="fa-solid fa-trash"></i> Cancelar</button>` : ''}
            </div>`).join('');
    }

    async function cancelAppointment(appointmentId) {
        if (confirm('¿Cancelar esta cita?')) {
            const result = await apiCall('delete_appointment', { appointment_id: appointmentId });
            if (result.success) {
                showToast('Cita cancelada');
                loadAppointments();
            }
        }
    }

    async function showNewAppointmentModal() {
        // Cargar mecánicos
        const mechanicsResult = await apiCall('get_mechanics');
        const vehiclesResult = await apiCall('get_vehicles');
        
        const mechanicSelect = document.getElementById('appointmentMechanic');
        mechanicSelect.innerHTML = '<option value="">Seleccionar mecánico...</option>';
        if (mechanicsResult.success && mechanicsResult.mechanics) {
            mechanicsResult.mechanics.forEach(m => {
                mechanicSelect.innerHTML += `<option value="${m.id}">${m.full_name} - ${m.specialty || 'Mecánico'}</option>`;
            });
        }
        
        const vehicleSelect = document.getElementById('appointmentVehicle');
        vehicleSelect.innerHTML = '<option value="">Seleccionar vehículo (opcional)</option>';
        if (vehiclesResult.success && vehiclesResult.vehicles) {
            vehiclesResult.vehicles.forEach(v => {
                vehicleSelect.innerHTML += `<option value="${v.id}">${v.brand} ${v.model} (${v.plate_number})</option>`;
            });
        }
        
        document.getElementById('appointmentId').value = '';
        document.getElementById('appointmentDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('appointmentTime').value = '';
        document.getElementById('appointmentNotes').value = '';
        appointmentModalInstance.show();
    }

    async function saveAppointment() {
        const mechanicId = document.getElementById('appointmentMechanic').value;
        const date = document.getElementById('appointmentDate').value;
        const time = document.getElementById('appointmentTime').value;
        
        if (!mechanicId || !date || !time) {
            showToast('Completa todos los campos obligatorios', true);
            return;
        }
        
        const result = await apiCall('save_appointment', {
            mechanic_id: mechanicId,
            appointment_date: date,
            appointment_time: time,
            notes: document.getElementById('appointmentNotes').value,
            vehicle_id: document.getElementById('appointmentVehicle').value
        });
        
        if (result.success) {
            showToast('Cita solicitada correctamente');
            appointmentModalInstance.hide();
            loadAppointments();
        } else {
            showToast(result.message || 'Error al guardar', true);
        }
    }

    // ========== VEHÍCULOS ==========
    async function loadVehicles() {
        showLoading('vehiclesList', 'Cargando vehículos...');
        const result = await apiCall('get_vehicles');
        if (result.success && result.vehicles.length > 0) {
            renderVehicles(result.vehicles);
        } else {
            document.getElementById('vehiclesList').innerHTML = '<div class="text-center text-muted p-4"><i class="fa-solid fa-car fa-2x mb-2 d-block"></i>No tienes vehículos registrados.<br><button class="btn btn-red btn-sm mt-2" onclick="showVehicleModal()">Agregar Vehículo</button></div>';
        }
    }

    function renderVehicles(vehicles) {
        const container = document.getElementById('vehiclesList');
        container.innerHTML = vehicles.map(v => `
            <div class="diagnostic-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>🚗 ${v.brand} ${v.model}</strong>
                        <div class="small text-muted">Placa: ${v.plate_number} | Año: ${v.year || 'N/A'} | Km: ${v.mileage || 0}</div>
                        ${v.color ? `<div class="small">Color: ${v.color}</div>` : ''}
                        ${v.notes ? `<div class="small text-muted mt-1">${v.notes}</div>` : ''}
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-light" onclick="editVehicle(${v.id})"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteVehicle(${v.id})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>`).join('');
    }

    function showVehicleModal(vehicle = null) {
        document.getElementById('vehicleId').value = vehicle?.id || '';
        document.getElementById('vehicleBrand').value = vehicle?.brand || '';
        document.getElementById('vehicleModel').value = vehicle?.model || '';
        document.getElementById('vehiclePlate').value = vehicle?.plate_number || '';
        document.getElementById('vehicleYear').value = vehicle?.year || '';
        document.getElementById('vehicleMileage').value = vehicle?.mileage || '';
        document.getElementById('vehicleColor').value = vehicle?.color || '';
        document.getElementById('vehicleNotes').value = vehicle?.notes || '';
        document.getElementById('vehicleModalTitle').innerText = vehicle ? 'Editar Vehículo' : 'Agregar Vehículo';
        vehicleModalInstance.show();
    }

    async function saveVehicle() {
        const vehicleData = {
            vehicle_id: document.getElementById('vehicleId').value,
            brand: document.getElementById('vehicleBrand').value,
            model: document.getElementById('vehicleModel').value,
            plate_number: document.getElementById('vehiclePlate').value,
            year: document.getElementById('vehicleYear').value,
            mileage: document.getElementById('vehicleMileage').value,
            color: document.getElementById('vehicleColor').value,
            notes: document.getElementById('vehicleNotes').value
        };
        
        if (!vehicleData.brand || !vehicleData.model || !vehicleData.plate_number) {
            showToast('Marca, modelo y placa son obligatorios', true);
            return;
        }
        
        const result = await apiCall('save_vehicle', vehicleData);
        if (result.success) {
            showToast(vehicleData.vehicle_id ? 'Vehículo actualizado' : 'Vehículo agregado');
            vehicleModalInstance.hide();
            loadVehicles();
        } else {
            showToast(result.message || 'Error al guardar', true);
        }
    }

    async function editVehicle(vehicleId) {
        const result = await apiCall('get_vehicles');
        const vehicle = result.vehicles?.find(v => v.id == vehicleId);
        if (vehicle) showVehicleModal(vehicle);
    }

    async function deleteVehicle(vehicleId) {
        if (confirm('¿Eliminar este vehículo?')) {
            const result = await apiCall('delete_vehicle', { vehicle_id: vehicleId });
            if (result.success) {
                showToast('Vehículo eliminado');
                loadVehicles();
            }
        }
    }

    // ========== AGENDA/EVENTOS ==========
    async function loadEvents() {
        showLoading('eventsList', 'Cargando eventos...');
        const result = await apiCall('get_events');
        if (result.success && result.events.length > 0) {
            renderEvents(result.events);
        } else {
            document.getElementById('eventsList').innerHTML = '<div class="text-center text-muted p-4"><i class="fa-solid fa-calendar fa-2x mb-2 d-block"></i>No hay eventos programados.<br><button class="btn btn-red btn-sm mt-2" onclick="showEventModal()">Agregar Evento</button></div>';
        }
    }

    function renderEvents(events) {
        const container = document.getElementById('eventsList');
        const sorted = [...events].sort((a, b) => a.event_date.localeCompare(b.event_date));
        container.innerHTML = sorted.map(e => `
            <div class="appointment-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>📌 ${e.title}</strong>
                        <div class="small text-muted">📅 ${e.event_date} — ${e.event_time}</div>
                        <div class="small">Tipo: ${e.event_type}</div>
                        ${e.description ? `<div class="small text-muted mt-1">${e.description}</div>` : ''}
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-light" onclick="editEvent(${e.id})"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent(${e.id})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>`).join('');
    }

    function showEventModal(event = null) {
        document.getElementById('eventId').value = event?.id || '';
        document.getElementById('eventTitle').value = event?.title || '';
        document.getElementById('eventDate').value = event?.event_date || new Date().toISOString().split('T')[0];
        document.getElementById('eventTime').value = event?.event_time || '';
        document.getElementById('eventType').value = event?.event_type || 'otro';
        document.getElementById('eventDescription').value = event?.description || '';
        eventModalInstance.show();
    }

    async function saveEvent() {
        const eventData = {
            event_id: document.getElementById('eventId').value,
            title: document.getElementById('eventTitle').value,
            event_date: document.getElementById('eventDate').value,
            event_time: document.getElementById('eventTime').value,
            event_type: document.getElementById('eventType').value,
            description: document.getElementById('eventDescription').value
        };
        
        if (!eventData.title || !eventData.event_date || !eventData.event_time) {
            showToast('Título, fecha y hora son obligatorios', true);
            return;
        }
        
        const result = await apiCall('save_event', eventData);
        if (result.success) {
            showToast(eventData.event_id ? 'Evento actualizado' : 'Evento agregado');
            eventModalInstance.hide();
            loadEvents();
        }
    }

    async function editEvent(eventId) {
        const result = await apiCall('get_events');
        const event = result.events?.find(e => e.id == eventId);
        if (event) showEventModal(event);
    }

    async function deleteEvent(eventId) {
        if (confirm('¿Eliminar este evento?')) {
            const result = await apiCall('delete_event', { event_id: eventId });
            if (result.success) {
                showToast('Evento eliminado');
                loadEvents();
            }
        }
    }

    // ========== CHAT ==========
    async function loadMechanics() {
        const result = await apiCall('get_mechanics');
        const select = document.getElementById('mechanicSelect');
        select.innerHTML = '<option value="">Seleccionar mecánico...</option>';
        if (result.success && result.mechanics) {
            result.mechanics.forEach(m => {
                select.innerHTML += `<option value="${m.id}">${m.full_name} - ${m.specialty || 'Mecánico'}</option>`;
            });
        }
    }

    async function loadChatMessages() {
        const mechanicId = document.getElementById('mechanicSelect').value;
        if (!mechanicId) {
            document.getElementById('chatMessages').innerHTML = '<div class="text-center text-muted">Selecciona un mecánico</div>';
            return;
        }
        currentMechanicId = mechanicId;
        
        const result = await apiCall('get_chat_messages', { other_user_id: mechanicId });
        if (result.success) {
            renderChatMessages(result.messages);
        }
    }

    function renderChatMessages(messages) {
        const container = document.getElementById('chatMessages');
        if (messages.length === 0) {
            container.innerHTML = '<div class="text-center text-muted">No hay mensajes aún. ¡Envía el primero!</div>';
            return;
        }
        container.innerHTML = messages.map(m => `
            <div class="chat-bubble ${m.sender_id == <?php echo $user_id; ?> ? 'chat-sent' : 'chat-received'}">
                <strong>${m.sender_id == <?php echo $user_id; ?> ? 'Tú' : 'Mecánico'}:</strong> ${escapeHtml(m.message)}
                <small class="d-block text-white-50">${new Date(m.created_at).toLocaleTimeString()}</small>
            </div>
        `).join('');
        container.scrollTop = container.scrollHeight;
    }

    async function sendMessage() {
        const message = document.getElementById('chatInput').value.trim();
        if (!message || !currentMechanicId) {
            showToast('Selecciona un mecánico y escribe un mensaje', true);
            return;
        }
        
        const result = await apiCall('send_message', { receiver_id: currentMechanicId, message: message });
        if (result.success) {
            document.getElementById('chatInput').value = '';
            loadChatMessages();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========== LOGOUT ==========
    async function logout() {
        const result = await apiCall('logout');
        if (result.success) window.location.href = 'index.php';
    }

    // ========== INICIALIZACIÓN ==========
    document.addEventListener('DOMContentLoaded', () => {
        vehicleModalInstance = new bootstrap.Modal(document.getElementById('vehicleModal'));
        appointmentModalInstance = new bootstrap.Modal(document.getElementById('appointmentModal'));
        eventModalInstance = new bootstrap.Modal(document.getElementById('eventModal'));
        ratingModalInstance = new bootstrap.Modal(document.getElementById('ratingModal'));
        
        loadDiagnostics();
        
        // Configurar estrellas de calificación
        document.querySelectorAll('#ratingStars i').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                document.querySelectorAll('#ratingStars i').forEach((s, idx) => {
                    s.className = idx < rating ? 'fa-solid fa-star' : 'fa-regular fa-star';
                });
            });
        });
        
        // Auto-refresh cada 10 segundos
        refreshInterval = setInterval(() => {
            const activeTab = document.querySelector('.tab-btn.active')?.innerText.toLowerCase();
            if (activeTab?.includes('diagnósticos')) loadDiagnostics();
            else if (activeTab?.includes('citas')) loadAppointments();
            else if (activeTab?.includes('chat') && currentMechanicId) loadChatMessages();
        }, 10000);
    });
</script>
</body>
</html>