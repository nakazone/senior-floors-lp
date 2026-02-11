/**
 * Lead Detail Page JavaScript
 */

let currentLeadId = null;
let currentLead = null;

// Check authentication and get lead ID from URL
window.addEventListener('DOMContentLoaded', () => {
    // Get lead ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    currentLeadId = parseInt(urlParams.get('id'));

    if (!currentLeadId) {
        alert('Lead ID não encontrado na URL');
        window.location.href = 'dashboard.html';
        return;
    }

    // Check session
    fetch('/api/auth/session', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            if (!data.authenticated) {
                window.location.href = '/login.html';
                return;
            }
            const un = document.getElementById('userName');
            if (un) un.textContent = data.user.name || data.user.email;
            loadLead();
        })
        .catch(err => {
            console.error('Session check error:', err);
            window.location.href = '/login.html';
        });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', async () => {
        await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' });
        window.location.href = '/login.html';
    });

    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const tabName = tab.dataset.tab;
            switchTab(tabName);
        });
    });

    // Score automático da qualificação
    attachQualificationScoreListeners();

    // Menu lateral fixo: toggle mobile
    const sidebar = document.getElementById('dashboardSidebar');
    const overlay = document.getElementById('mobileOverlay');
    const menuBtn = document.getElementById('mobileMenuToggle');
    if (menuBtn && sidebar && overlay) {
        menuBtn.addEventListener('click', () => { sidebar.classList.toggle('mobile-open'); overlay.classList.toggle('active'); });
        overlay.addEventListener('click', () => { sidebar.classList.remove('mobile-open'); overlay.classList.remove('active'); });
    }
});

async function loadLead() {
    try {
        const response = await fetch(`/api/leads/${currentLeadId}`, { credentials: 'include' });
        const data = await response.json();
        
        if (data.success) {
            currentLead = data.data;
            renderLead();
            loadPipelineStages();
            loadQualification();
            loadInteractions();
            loadVisits();
            loadProposals();
        } else {
            alert('Erro ao carregar lead: ' + (data.error || 'Desconhecido'));
            window.location.href = 'dashboard.html';
        }
    } catch (error) {
        console.error('Error loading lead:', error);
        alert('Erro ao carregar lead');
    }
}

function renderLead() {
    if (!currentLead) return;

    document.getElementById('leadName').textContent = currentLead.name || 'Sem nome';
    document.getElementById('leadEmail').textContent = currentLead.email || '-';
    document.getElementById('leadPhone').textContent = currentLead.phone || '-';
    document.getElementById('leadSource').textContent = currentLead.source || '-';
    document.getElementById('leadCreated').textContent = currentLead.created_at ? new Date(currentLead.created_at).toLocaleDateString() : '-';

    // Form fields
    document.getElementById('leadNotes').value = currentLead.notes || '';
    document.getElementById('leadPriority').value = currentLead.priority || 'medium';
    document.getElementById('leadEstimatedValue').value = currentLead.estimated_value || '';
    // Status select is filled by loadPipelineStages and synced here
    const statusSelect = document.getElementById('leadStatusSelect');
    if (statusSelect && statusSelect.options.length) {
        const slug = currentLead.status || '';
        for (let i = 0; i < statusSelect.options.length; i++) {
            if (statusSelect.options[i].value === slug) {
                statusSelect.selectedIndex = i;
                break;
            }
        }
    }
}

function getStatusColor(status) {
    const colors = {
        'lead_received': '#3498db',
        'contact_made': '#f39c12',
        'qualified': '#9b59b6',
        'visit_scheduled': '#e67e22',
        'measurement_done': '#16a085',
        'proposal_created': '#34495e',
        'proposal_sent': '#95a5a6',
        'negotiation': '#e74c3c',
        'closed_won': '#27ae60',
        'closed_lost': '#c0392b',
        'production': '#8e44ad'
    };
    return colors[status] || '#95a5a6';
}

async function loadPipelineStages() {
    let stages = [];
    try {
        const res = await fetch('/api/pipeline-stages', { credentials: 'include' });
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
            stages = data.data.map(s => ({ id: s.id, name: s.name, slug: s.slug || s.name }));
        }
    } catch (e) { /* ignore */ }
    if (stages.length === 0) {
        stages = [
            { id: 1, name: 'Lead Recebido', slug: 'lead_received' },
            { id: 2, name: 'Contato Realizado', slug: 'contact_made' },
            { id: 3, name: 'Qualificado', slug: 'qualified' },
            { id: 4, name: 'Visita Agendada', slug: 'visit_scheduled' },
            { id: 5, name: 'Medição Realizada', slug: 'measurement_done' },
            { id: 6, name: 'Proposta Criada', slug: 'proposal_created' },
            { id: 7, name: 'Proposta Enviada', slug: 'proposal_sent' },
            { id: 8, name: 'Em Negociação', slug: 'negotiation' },
            { id: 9, name: 'Fechado - Ganhou', slug: 'closed_won' },
            { id: 10, name: 'Fechado - Perdido', slug: 'closed_lost' },
            { id: 11, name: 'Produção / Obra', slug: 'production' }
        ];
    }

    try {
        const select = document.getElementById('leadStatusSelect');
        select.innerHTML = '<option value="">Selecione...</option>';
        stages.forEach(stage => {
            const option = document.createElement('option');
            option.value = stage.slug;
            option.textContent = stage.name;
            if (currentLead && currentLead.status === stage.slug) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        // Save status when user changes dropdown (header)
        select.addEventListener('change', function onStatusChange() {
            const newStatus = select.value;
            if (!newStatus || !currentLeadId) return;
            fetch(`/api/leads/${currentLeadId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ status: newStatus })
            }).then(r => r.json()).then(data => {
                if (data.success) currentLead.status = newStatus;
            }).catch(() => {});
        });
    } catch (error) {
        console.error('Error loading pipeline stages:', error);
    }
}

async function saveLead() {
    const updates = {
        notes: document.getElementById('leadNotes').value,
        priority: document.getElementById('leadPriority').value,
        estimated_value: parseFloat(document.getElementById('leadEstimatedValue').value) || null,
        status: document.getElementById('leadStatusSelect').value || currentLead.status
    };

    try {
        const response = await fetch(`/api/leads/${currentLeadId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(updates)
        });

        const data = await response.json();
        if (data.success) {
            loadLead();
        } else {
            alert('Erro ao atualizar: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error saving lead:', error);
        alert('Erro ao salvar');
    }
}

/**
 * Calcula score de qualificação (0-100) com base em: tipo, serviço, área, orçamento, urgência.
 * Só considera pontos quando os campos obrigatórios estão preenchidos.
 */
function calculateQualificationScore() {
    const propertyType = (document.getElementById('qualPropertyType')?.value || '').trim();
    const serviceType = (document.getElementById('qualServiceType')?.value || '').trim();
    const area = parseFloat(document.getElementById('qualEstimatedArea')?.value) || 0;
    const budget = parseFloat(document.getElementById('qualEstimatedBudget')?.value) || 0;
    const urgency = (document.getElementById('qualUrgency')?.value || 'medium').trim();

    let pts = 0;
    // Tipo de propriedade (até 20)
    const propertyScores = { house: 20, apartment: 17, commercial: 12, other: 8 };
    pts += propertyScores[propertyType] || 0;
    // Tipo de serviço (até 20)
    const serviceScores = { installation: 20, renovation: 17, repair: 12, other: 8 };
    pts += serviceScores[serviceType] || 0;
    // Área estimada em sqft (até 20) — só conta se preenchido
    if (area > 0) {
        if (area <= 250) pts += 5;
        else if (area <= 500) pts += 10;
        else if (area <= 1000) pts += 14;
        else if (area <= 2000) pts += 18;
        else pts += 20;
    }
    // Orçamento (até 20) — só conta se preenchido
    if (budget > 0) {
        if (budget < 5000) pts += 5;
        else if (budget < 15000) pts += 10;
        else if (budget < 30000) pts += 15;
        else pts += 20;
    }
    // Urgência (até 20)
    const urgencyScores = { low: 8, medium: 12, high: 17, urgent: 20 };
    pts += urgencyScores[urgency] || 12;

    return Math.min(100, Math.round(pts));
}

function updateQualificationScoreDisplay() {
    const el = document.getElementById('qualScore');
    if (el) el.value = calculateQualificationScore();
}

function attachQualificationScoreListeners() {
    const ids = ['qualPropertyType', 'qualServiceType', 'qualEstimatedArea', 'qualEstimatedBudget', 'qualUrgency'];
    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', updateQualificationScoreDisplay);
            el.addEventListener('change', updateQualificationScoreDisplay);
        }
    });
}

async function loadQualification() {
    try {
        const response = await fetch(`/api/leads/${currentLeadId}/qualification`, { credentials: 'include' });
        const data = await response.json();
        
        if (data.success && data.data) {
            const qual = data.data;
            document.getElementById('qualPropertyType').value = qual.property_type || '';
            document.getElementById('qualServiceType').value = qual.service_type || '';
            document.getElementById('qualEstimatedArea').value = qual.estimated_area || '';
            document.getElementById('qualEstimatedBudget').value = qual.estimated_budget || '';
            document.getElementById('qualUrgency').value = qual.urgency || 'medium';
            document.getElementById('qualDecisionMaker').value = qual.decision_maker || '';
            document.getElementById('qualDecisionTimeline').value = qual.decision_timeline || '';
            document.getElementById('qualPaymentType').value = qual.payment_type || '';
            document.getElementById('qualNotes').value = qual.qualification_notes || '';
            updateQualificationScoreDisplay();
        } else {
            updateQualificationScoreDisplay();
        }
    } catch (error) {
        console.log('Qualification not found or error:', error);
        updateQualificationScoreDisplay();
    }
}

async function saveQualification() {
    const propertyType = document.getElementById('qualPropertyType').value?.trim();
    const serviceType = document.getElementById('qualServiceType').value?.trim();
    const estimatedArea = document.getElementById('qualEstimatedArea').value?.trim();
    const estimatedBudget = document.getElementById('qualEstimatedBudget').value?.trim();
    const urgency = document.getElementById('qualUrgency').value?.trim();

    if (!propertyType) {
        alert('Selecione o Tipo de Propriedade.');
        return;
    }
    if (!serviceType) {
        alert('Selecione o Tipo de Serviço.');
        return;
    }
    if (!estimatedArea || parseFloat(estimatedArea) <= 0) {
        alert('Informe a Área estimada (sqft).');
        return;
    }
    if (!estimatedBudget || parseFloat(estimatedBudget) <= 0) {
        alert('Informe o Orçamento estimado.');
        return;
    }
    if (!urgency) {
        alert('Selecione a Urgência.');
        return;
    }

    const score = calculateQualificationScore();
    const qualification = {
        property_type: propertyType,
        service_type: serviceType,
        estimated_area: parseFloat(estimatedArea) || null,
        estimated_budget: parseFloat(estimatedBudget) || null,
        urgency: urgency,
        decision_maker: document.getElementById('qualDecisionMaker').value?.trim() || null,
        decision_timeline: document.getElementById('qualDecisionTimeline').value?.trim() || null,
        payment_type: document.getElementById('qualPaymentType').value?.trim() || null,
        score: score,
        qualification_notes: document.getElementById('qualNotes').value?.trim() || null
    };

    try {
        const response = await fetch(`/api/leads/${currentLeadId}/qualification`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(qualification)
        });

        const data = await response.json();
        if (data.success) {
            loadQualification();
        } else {
            alert('Erro ao salvar: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error saving qualification:', error);
        alert('Erro ao salvar');
    }
}

async function loadInteractions() {
    try {
        const response = await fetch(`/api/leads/${currentLeadId}/interactions`, { credentials: 'include' });
        const data = await response.json();
        
        const list = document.getElementById('interactionsList');
        if (data.success && data.data && data.data.length > 0) {
            list.innerHTML = data.data.map(interaction => `
                <li class="timeline-item">
                    <div class="timeline-item-header">
                        <span class="timeline-item-title">${getInteractionTypeLabel(interaction.type)}</span>
                        <span class="timeline-item-date">${new Date(interaction.created_at).toLocaleString()}</span>
                    </div>
                    <div class="timeline-item-content">
                        ${interaction.subject ? `<strong>${interaction.subject}</strong><br>` : ''}
                        ${interaction.notes || ''}
                        ${interaction.user_name ? `<br><small>Por: ${interaction.user_name}</small>` : ''}
                    </div>
                </li>
            `).join('');
        } else {
            list.innerHTML = '<li class="empty-state">Nenhuma interação registrada ainda.</li>';
        }
    } catch (error) {
        console.error('Error loading interactions:', error);
    }
}

function getInteractionTypeLabel(type) {
    const labels = {
        'call': '📞 Chamada',
        'whatsapp': '💬 WhatsApp',
        'email': '📧 Email',
        'visit': '🏠 Visita',
        'meeting': '🤝 Reunião'
    };
    return labels[type] || type;
}

async function loadVisits() {
    try {
        const response = await fetch(`/api/visits?lead_id=${currentLeadId}`, { credentials: 'include' });
        const data = await response.json();
        
        const container = document.getElementById('visitsList');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(visit => `
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                    <h3>${new Date(visit.scheduled_at).toLocaleString()}</h3>
                    <p><strong>Endereço:</strong> ${visit.address || '-'}</p>
                    <p><strong>Status:</strong> ${visit.status || 'scheduled'}</p>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">Nenhuma visita agendada ainda.</div>';
        }
    } catch (error) {
        console.error('Error loading visits:', error);
    }
}

async function loadProposals() {
    try {
        const response = await fetch(`/api/leads/${currentLeadId}/proposals`, { credentials: 'include' });
        const data = await response.json();
        
        const container = document.getElementById('proposalsList');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.map(proposal => `
                <div style="padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px;">
                    <h3>${proposal.proposal_number || `Proposta #${proposal.id}`}</h3>
                    <p><strong>Valor:</strong> $${parseFloat(proposal.total_value || 0).toLocaleString()}</p>
                    <p><strong>Status:</strong> ${proposal.status || 'draft'}</p>
                    <p><strong>Criada em:</strong> ${new Date(proposal.created_at).toLocaleDateString()}</p>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="empty-state">Nenhuma proposta criada ainda.</div>';
        }
    } catch (error) {
        console.error('Error loading proposals:', error);
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    document.getElementById(`${tabName}Tab`).classList.add('active');
}

function showNewInteractionModal() {
    const type = prompt('Tipo de interação:\n1. call\n2. whatsapp\n3. email\n4. visit\n5. meeting');
    const notes = prompt('Notas:');
    
    if (type && notes) {
        createInteraction({ type, notes });
    }
}

function showNewVisitModal() {
    alert('Funcionalidade de agendar visita em desenvolvimento');
}

function showNewProposalModal() {
    alert('Funcionalidade de criar proposta em desenvolvimento');
}

async function createInteraction(interaction) {
    try {
        const response = await fetch(`/api/leads/${currentLeadId}/interactions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(interaction)
        });

        const data = await response.json();
        if (data.success) {
            loadInteractions();
        } else {
            alert('Erro: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error creating interaction:', error);
        alert('Erro ao criar interação');
    }
}
