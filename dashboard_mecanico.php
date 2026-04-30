<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header('Location: login.html');
    exit;
}

if (getCurrentUserType() !== 'mecanico') {
    header('Location: dashboard_cliente.php');
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
    <title>Kiroo - Panel Mecánico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --primary-red: #d32f2f; --bg-dark: #121212; --bg-card: #1e1e1e; --mech-orange: #ff9800; }
        body { background-color: var(--bg-dark); color: white; font-family: 'Segoe UI', sans-serif; }
        .navbar-kiroo { background: linear-gradient(135deg, var(--mech-orange) 0%, #e65100 100%); padding: 0.8rem 2rem; }
        .content-card { background: var(--bg-card); border-radius: 20px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 25px; overflow: hidden; }
        .card-header-custom { background: rgba(0,0,0,0.3); padding: 1rem 1.5rem; border-bottom: 1px solid #2c2c2c; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .btn-orange { background-color: var(--mech-orange); border: none; color: white; border-radius: 30px; padding: 8px 20px; transition: 0.2s; }
        .btn-orange:hover { background-color: #e65100; }
        .appointment-card, .diagnostic-card { background: #2a2a2a; border-radius: 16px; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid var(--mech-orange); }
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; }
        .status-completado { background: #4caf50; }
        .status-pendiente { background: #ff9800; color: #000; }
        .status-confirmada { background: #2196f3; }
        .tab-btn { background: transparent; border: none; color: #aaa; padding: 10px 20px; cursor: pointer; border-bottom: 2px solid transparent; transition: 0.2s; }
        .tab-btn.active { color: white; border-bottom-color: var(--mech-orange); }
        .chat-container { height: 300px; overflow-y: auto; padding: 15px; background: #1a1a1a; border-radius: 15px; margin-bottom: 15px; }
        .chat-bubble { padding: 10px 15px; border-radius: 20px; margin-bottom: 10px; max-width: 80%; clear: both; }
        .chat-sent { background: var(--mech-orange); float: right; }
        .chat-received { background: #333; float: left; }
        .modal-content { background-color: var(--bg-card); color: white; }
        .form-control, .form-select { background-color: #2c2c2c; border-color: #444; color: white; }
        .form-control:focus, .form-select:focus { background-color: #333; border-color: var(--mech-orange); color: white; box-shadow: none; }
        .system-check { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #333; }
        .condition-btn { background: #333; border: 2px solid transparent; color: #ccc; border-radius: 10px; padding: 8px 16px; cursor: pointer; transition: 0.2s; }
        .condition-btn.selected-bueno { background: #1b5e20; border-color: #4caf50; }
        .condition-btn.selected-regular { background: #bf360c; border-color: #ff7043; }
        .condition-btn.selected-malo { background: #7f0000; border-color: #ef5350; }
        .condition-btn.selected-critico { background: #4a148c; border-color: #ce93d8; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-kiroo">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="fa-solid fa-wrench"></i> Kiroo Mecánico</a>
        <div class="d-flex align-items-center gap-2">
            <span class="me-2"><i class="fa-regular fa-user"></i> <span id="userNameDisplay"><?php echo htmlspecialchars($full_name); ?></span></span>
            <button class="btn btn-sm btn-outline-light" onclick="logout()"><i class="fa-solid fa-sign-out-alt"></i> Salir</button>
        </div>
    </div>
</nav>

<div class="container my-4">
    <ul class="nav nav-tabs border-0 mb-4" id="dashboardTabs">
        <li class="nav-item"><button class="tab-btn active" onclick="switchTab('appointments')"><i class="fa-solid fa-calendar-check me-1"></i>Citas</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('new-diagnostic')"><i class="fa-solid fa-stethoscope me-1"></i>Nuevo Diagnóstico</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('diagnostics')"><i class="fa-solid fa-clipboard-check me-1"></i>Diagnósticos</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('clients')"><i class="fa-solid fa-users me-1"></i>Clientes</button></li>
        <li class="nav-item"><button class="tab-btn" onclick="switchTab('chat')"><i class="fa-solid fa-comments me-1"></i>Chat</button></li>
    </ul>

    <!-- TAB: CITAS -->
    <div id="tab-appointments" class="tab-content-panel">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-calendar-check"></i> Citas Asignadas</span>
                <button class="btn btn-orange btn-sm" onclick="showNewAppointmentModal()"><i class="fa-solid fa-plus"></i> Nueva Cita</button>
            </div>
            <div class="p-3" id="appointmentsList">
                <div class="text-center text-muted p-4">Cargando citas...</div>
            </div>
        </div>
    </div>

    <!-- TAB: NUEVO DIAGNÓSTICO -->
    <div id="tab-new-diagnostic" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-stethoscope"></i> Nuevo Diagnóstico</span>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Cliente *</label>
                        <select class="form-select" id="diagnosticClientId" required onchange="loadClientVehicles()">
                            <option value="">Seleccionar cliente...</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vehículo</label>
                        <select class="form-select" id="diagnosticVehicleId">
                            <option value="">Seleccionar vehículo (opcional)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cita asociada (opcional)</label>
                        <select class="form-select" id="diagnosticAppointmentId">
                            <option value="">Sin cita asociada</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kilometraje</label>
                        <input type="number" class="form-control" id="diagnosticMileage">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Síntomas reportados</label>
                        <textarea class="form-control" id="diagnosticSymptoms" rows="2"></textarea>
                    </div>
                    
                    <!-- Estado del vehículo -->
                    <div class="col-12">
                        <label class="form-label">Estado general del vehículo</label>
                        <div class="d-flex gap-2 flex-wrap" id="conditionBtns">
                            <button type="button" class="condition-btn" data-condition="excelente" onclick="selectCondition('excelente')">✅ Excelente</button>
                            <button type="button" class="condition-btn" data-condition="bueno" onclick="selectCondition('bueno')">👍 Bueno</button>
                            <button type="button" class="condition-btn" data-condition="regular" onclick="selectCondition('regular')">⚠️ Regular</button>
                            <button type="button" class="condition-btn" data-condition="malo" onclick="selectCondition('malo')">❌ Malo</button>
                            <button type="button" class="condition-btn" data-condition="critico" onclick="selectCondition('critico')">🚨 Crítico</button>
                        </div>
                        <input type="hidden" id="diagnosticCondition" value="">
                    </div>
                    
                    <!-- Diagnóstico y recomendaciones -->
                    <div class="col-12">
                        <label class="form-label">Diagnóstico detallado *</label>
                        <textarea class="form-control" id="diagnosisText" rows="4" required></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Recomendaciones *</label>
                        <textarea class="form-control" id="recommendationText" rows="3" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Piezas necesarias</label>
                        <input type="text" class="form-control" id="partsNeeded" placeholder="Ej: Pastillas de freno, filtro...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Costo estimado ($)</label>
                        <input type="number" class="form-control" id="estimatedCost" step="0.01">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notas adicionales</label>
                        <textarea class="form-control" id="additionalNotes" rows="2"></textarea>
                    </div>
                </div>
                <button class="btn btn-orange w-100 mt-4" onclick="submitDiagnostic()"><i class="fa-solid fa-paper-plane me-2"></i> Enviar Diagnóstico</button>
            </div>
        </div>
    </div>

    <!-- TAB: DIAGNÓSTICOS REALIZADOS -->
    <div id="tab-diagnostics" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-clipboard-check"></i> Diagnósticos Realizados</span>
                <button class="btn btn-orange btn-sm" onclick="loadDiagnostics()"><i class="fa-solid fa-refresh"></i> Actualizar</button>
            </div>
            <div class="p-3" id="myDiagnosticsList">
                <div class="text-center text-muted p-4">Cargando diagnósticos...</div>
            </div>
        </div>
    </div>

    <!-- TAB: CLIENTES -->
    <div id="tab-clients" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-users"></i> Mis Clientes</span>
            </div>
            <div class="p-3" id="clientsList">
                <div class="text-center text-muted p-4">Cargando clientes...</div>
            </div>
        </div>
    </div>

    <!-- TAB: CHAT -->
    <div id="tab-chat" class="tab-content-panel" style="display:none;">
        <div class="content-card">
            <div class="card-header-custom">
                <span><i class="fa-solid fa-comments"></i> Chat con Clientes</span>
                <select id="clientSelect" class="form-select form-select-sm" style="width: auto;" onchange="loadChatMessages()">
                    <option value="">Seleccionar cliente...</option>
                </select>
            </div>
            <div class="p-3">
                <div class="chat-container" id="chatMessages">
                    <div class="text-center text-muted">Selecciona un cliente para comenzar</div>
                </div>
                <div class="input-group mt-2">
                    <input type="text" class="form-control" id="chatInput" placeholder="Escribe un mensaje...">
                    <button class="btn btn-orange" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Cita -->
<div class="modal fade" id="appointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Cliente *</label>
                    <select class="form-select" id="newAppointmentClientId" required>
                        <option value="">Seleccionar cliente...</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Vehículo</label>
                    <select class="form-select" id="newAppointmentVehicleId">
                        <option value="">Seleccionar vehículo (opcional)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Fecha *</label>
                    <input type="date" class="form-control" id="newAppointmentDate" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hora *</label>
                    <input type="time" class="form-control" id="newAppointmentTime" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas</label>
                    <textarea class="form-control" id="newAppointmentNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-orange" onclick="createAppointment()">Crear Cita</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let appointmentModalInstance;
    let currentClientId = null;
    let refreshInterval = null;

    function showToast(message, isError = false) {
        const toast = document.createElement('div');
        toast.className = `position-fixed bottom-0 end-0 p-3 m-3 rounded shadow text-white ${isError ? 'bg-danger' : 'bg-success'}`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `<i class="fa-solid ${isError ? 'fa-circle-exclamation' : 'fa-check-circle'} me-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
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
        
        if (tab === 'appointments') loadAppointments();
        else if (tab === 'diagnostics') loadDiagnostics();
        else if (tab === 'clients') loadClients();
        else if (tab === 'new-diagnostic') loadClientsForDiagnostic();
    }

    // ========== CITAS ==========
    async function loadAppointments() {
        const result = await apiCall('get_appointments');
        const container = document.getElementById('appointmentsList');
        if (result.success && result.appointments.length > 0) {
            container.innerHTML = result.appointments.map(a => `
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <strong>📅 ${a.appointment_date} — ${a.appointment_time}</strong>
                            <div class="small">Cliente: ${a.client_name || 'Cliente'}</div>
                            ${a.notes ? `<div class="small text-muted mt-1">${a.notes}</div>` : ''}
                        </div>
                        <span class="badge-status ${a.status === 'completado' ? 'status-completado' : (a.status === 'confirmada' ? 'status-confirmada' : 'status-pendiente')}">${a.status}</span>
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        ${a.status !== 'completado' ? `<button class="btn btn-sm btn-outline-light" onclick="confirmAppointment(${a.id})"><i class="fa-solid fa-check"></i> Confirmar</button>` : ''}
                        ${a.status !== 'completado' ? `<button class="btn btn-sm btn-orange" onclick="goToDiagnostic(${a.id}, ${a.client_id})"><i class="fa-solid fa-stethoscope"></i> Diagnosticar</button>` : ''}
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteAppointment(${a.id})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>`).join('');
        } else {
            container.innerHTML = '<div class="text-center text-muted p-4"><i class="fa-solid fa-calendar fa-2x mb-2 d-block"></i>No hay citas asignadas.</div>';
        }
    }

    async function confirmAppointment(appointmentId) {
        const result = await apiCall('update_appointment_status', { appointment_id: appointmentId, status: 'confirmada' });
        if (result.success) {
            showToast('Cita confirmada');
            loadAppointments();
        }
    }

    async function deleteAppointment(appointmentId) {
        if (confirm('¿Eliminar esta cita?')) {
            const result = await apiCall('delete_appointment', { appointment_id: appointmentId });
            if (result.success) {
                showToast('Cita eliminada');
                loadAppointments();
            }
        }
    }

    async function showNewAppointmentModal() {
        const clientsResult = await apiCall('get_my_clients');
        const clientSelect = document.getElementById('newAppointmentClientId');
        clientSelect.innerHTML = '<option value="">Seleccionar cliente...</option>';
        if (clientsResult.success && clientsResult.clients) {
            clientsResult.clients.forEach(c => {
                clientSelect.innerHTML += `<option value="${c.id}">${c.full_name}</option>`;
            });
        }
        document.getElementById('newAppointmentDate').value = new Date().toISOString().split('T')[0];
        document.getElementById('newAppointmentTime').value = '';
        document.getElementById('newAppointmentNotes').value = '';
        appointmentModalInstance.show();
    }

    async function createAppointment() {
        const clientId = document.getElementById('newAppointmentClientId').value;
        const date = document.getElementById('newAppointmentDate').value;
        const time = document.getElementById('newAppointmentTime').value;
        
        if (!clientId || !date || !time) {
            showToast('Cliente, fecha y hora son obligatorios', true);
            return;
        }
        
        const result = await apiCall('save_appointment', {
            client_id: clientId,
            appointment_date: date,
            appointment_time: time,
            notes: document.getElementById('newAppointmentNotes').value,
            vehicle_id: document.getElementById('newAppointmentVehicleId').value
        });
        
        if (result.success) {
            showToast('Cita creada');
            appointmentModalInstance.hide();
            loadAppointments();
        }
    }

    // ========== DIAGNÓSTICOS ==========
    async function loadClientsForDiagnostic() {
        const clientsResult = await apiCall('get_my_clients');
        const clientSelect = document.getElementById('diagnosticClientId');
        clientSelect.innerHTML = '<option value="">Seleccionar cliente...</option>';
        if (clientsResult.success && clientsResult.clients) {
            clientsResult.clients.forEach(c => {
                clientSelect.innerHTML += `<option value="${c.id}">${c.full_name}</option>`;
            });
        }
    }

    async function loadClientVehicles() {
        const clientId = document.getElementById('diagnosticClientId').value;
        if (!clientId) return;
        
        const appointmentsResult = await apiCall('get_appointments');
        const appointmentSelect = document.getElementById('diagnosticAppointmentId');
        appointmentSelect.innerHTML = '<option value="">Sin cita asociada</option>';
        if (appointmentsResult.success && appointmentsResult.appointments) {
            appointmentsResult.appointments.filter(a => a.client_id == clientId && a.status !== 'completado').forEach(a => {
                appointmentSelect.innerHTML += `<option value="${a.id}">${a.appointment_date} - ${a.appointment_time}</option>`;
            });
        }
        
        document.getElementById('diagnosticSymptoms').value = '';
        document.getElementById('diagnosticMileage').value = '';
    }

    function selectCondition(condition) {
        document.getElementById('diagnosticCondition').value = condition;
        const btnMap = { excelente: 'selected-bueno', bueno: 'selected-bueno', regular: 'selected-regular', malo: 'selected-malo', critico: 'selected-critico' };
        document.querySelectorAll('.condition-btn').forEach(btn => {
            btn.className = 'condition-btn';
            if (btn.dataset.condition === condition) btn.classList.add(btnMap[condition] || 'selected-regular');
        });
    }

    async function submitDiagnostic() {
        const clientId = document.getElementById('diagnosticClientId').value;
        const diagnosis = document.getElementById('diagnosisText').value.trim();
        const recommendation = document.getElementById('recommendationText').value.trim();
        
        if (!clientId || !diagnosis || !recommendation) {
            showToast('Cliente, diagnóstico y recomendaciones son obligatorios', true);
            return;
        }
        
        const result = await apiCall('save_diagnostic', {
            client_id: clientId,
            appointment_id: document.getElementById('diagnosticAppointmentId').value,
            vehicle_name: document.getElementById('diagnosticVehicleId').options[document.getElementById('diagnosticVehicleId').selectedIndex]?.text,
            mileage: document.getElementById('diagnosticMileage').value,
            symptoms: document.getElementById('diagnosticSymptoms').value,
            diagnosis: diagnosis,
            recommendation: recommendation,
            vehicle_condition: document.getElementById('diagnosticCondition').value,
            parts_needed: document.getElementById('partsNeeded').value,
            estimated_cost: document.getElementById('estimatedCost').value,
            additional_notes: document.getElementById('additionalNotes').value
        });
        
        if (result.success) {
            showToast('Diagnóstico enviado correctamente');
            document.getElementById('diagnosisText').value = '';
            document.getElementById('recommendationText').value = '';
            document.getElementById('diagnosticCondition').value = '';
            document.querySelectorAll('.condition-btn').forEach(btn => btn.className = 'condition-btn');
            switchTab('diagnostics');
        } else {
            showToast(result.message || 'Error al enviar diagnóstico', true);
        }
    }

    async function loadDiagnostics() {
        const result = await apiCall('get_diagnostics');
        const container = document.getElementById('myDiagnosticsList');
        if (result.success && result.diagnostics.length > 0) {
            container.innerHTML = result.diagnostics.map(d => `
                <div class="diagnostic-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong>Cliente: ${d.client_name}</strong>
                            <div class="small text-muted">${new Date(d.created_at).toLocaleDateString()}</div>
                        </div>
                        <span class="badge-status status-completado">Completado</span>
                    </div>
                    <div class="mt-2 small"><strong>Diagnóstico:</strong> ${d.diagnosis.substring(0, 100)}${d.diagnosis.length > 100 ? '...' : ''}</div>
                    ${d.rated ? `<div class="mt-1 small">⭐ Calificación del cliente: ${d.rating}/5</div>` : '<div class="mt-1 small text-muted">⏳ Esperando calificación del cliente</div>'}
                </div>`).join('');
        } else {
            container.innerHTML = '<div class="text-center text-muted p-4">No has realizado diagnósticos aún.</div>';
        }
    }

    function goToDiagnostic(appointmentId, clientId) {
        document.getElementById('diagnosticClientId').value = clientId;
        loadClientVehicles();
        setTimeout(() => {
            document.getElementById('diagnosticAppointmentId').value = appointmentId;
            switchTab('new-diagnostic');
        }, 500);
    }

    // ========== CLIENTES ==========
    async function loadClients() {
        const result = await apiCall('get_my_clients');
        const container = document.getElementById('clientsList');
        if (result.success && result.clients.length > 0) {
            container.innerHTML = result.clients.map(c => `
                <div class="appointment-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><i class="fa-regular fa-user me-2"></i>${c.full_name}</strong>
                            <div class="small text-muted">📧 ${c.email} | 📞 ${c.phone || 'N/A'}</div>
                        </div>
                        <button class="btn btn-sm btn-orange" onclick="startChatWithClient(${c.id}, '${c.full_name}')"><i class="fa-solid fa-comment"></i> Chat</button>
                    </div>
                </div>`).join('');
        } else {
            container.innerHTML = '<div class="text-center text-muted p-4">No tienes clientes aún.</div>';
        }
    }

    function startChatWithClient(clientId, clientName) {
        const clientSelect = document.getElementById('clientSelect');
        for (let i = 0; i < clientSelect.options.length; i++) {
            if (clientSelect.options[i].value == clientId) {
                clientSelect.selectedIndex = i;
                break;
            }
        }
        switchTab('chat');
        loadChatMessages();
    }

    // ========== CHAT ==========
    async function loadClientsForChat() {
        const result = await apiCall('get_my_clients');
        const select = document.getElementById('clientSelect');
        select.innerHTML = '<option value="">Seleccionar cliente...</option>';
        if (result.success && result.clients) {
            result.clients.forEach(c => {
                select.innerHTML += `<option value="${c.id}">${c.full_name}</option>`;
            });
        }
    }

    async function loadChatMessages() {
        const clientId = document.getElementById('clientSelect').value;
        if (!clientId) {
            document.getElementById('chatMessages').innerHTML = '<div class="text-center text-muted">Selecciona un cliente</div>';
            return;
        }
        currentClientId = clientId;
        
        const result = await apiCall('get_chat_messages', { other_user_id: clientId });
        if (result.success) {
            const container = document.getElementById('chatMessages');
            if (result.messages.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">No hay mensajes aún. ¡Envía el primero!</div>';
                return;
            }
            container.innerHTML = result.messages.map(m => `
                <div class="chat-bubble ${m.sender_id == <?php echo $user_id; ?> ? 'chat-sent' : 'chat-received'}">
                    <strong>${m.sender_id == <?php echo $user_id; ?> ? 'Tú' : 'Cliente'}:</strong> ${escapeHtml(m.message)}
                    <small class="d-block text-white-50">${new Date(m.created_at).toLocaleTimeString()}</small>
                </div>
            `).join('');
            container.scrollTop = container.scrollHeight;
        }
    }

    async function sendMessage() {
        const message = document.getElementById('chatInput').value.trim();
        if (!message || !currentClientId) {
            showToast('Selecciona un cliente y escribe un mensaje', true);
            return;
        }
        
        const result = await apiCall('send_message', { receiver_id: currentClientId, message: message });
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
        appointmentModalInstance = new bootstrap.Modal(document.getElementById('appointmentModal'));
        loadAppointments();
        loadClientsForChat();
        
        // Auto-refresh
        refreshInterval = setInterval(() => {
            const activeTab = document.querySelector('.tab-btn.active')?.innerText.toLowerCase();
            if (activeTab?.includes('citas')) loadAppointments();
            else if (activeTab?.includes('diagnósticos')) loadDiagnostics();
            else if (activeTab?.includes('chat') && currentClientId) loadChatMessages();
        }, 10000);
    });
</script>
</body>
</html>