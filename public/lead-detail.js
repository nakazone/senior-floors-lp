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
            document.getElementById('userName').textContent = data.user.name || data.user.email;
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
    
    // Status badge
    const statusEl = document.getElementById('leadStatus');
    statusEl.textContent = currentLead.status || 'new';
    statusEl.className = 'status-badge';
    statusEl.style.backgroundColor = getStatusColor(currentLead.status);

    // Form fields
    document.getElementById('leadNotes').value = currentLead.notes || '';
    document.getElementById('leadPriority').value = currentLead.priority || 'medium';
    document.getElementById('leadEstimatedValue').value = currentLead.estimated_value || '';
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
    try {
        // Load stages from database or use default
        const stages = [
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
            alert('Lead atualizado com sucesso!');
            loadLead();
        } else {
            alert('Erro ao atualizar: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error saving lead:', error);
        alert('Erro ao salvar');
    }
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
            document.getElementById('qualScore').value = qual.score || '';
            document.getElementById('qualNotes').value = qual.qualification_notes || '';
        }
    } catch (error) {
        // Qualification might not exist yet, that's OK
        console.log('Qualification not found or error:', error);
    }
}

async function saveQualification() {
    const qualification = {
        property_type: document.getElementById('qualPropertyType').value,
        service_type: document.getElementById('qualServiceType').value,
        estimated_area: parseFloat(document.getElementById('qualEstimatedArea').value) || null,
        estimated_budget: parseFloat(document.getElementById('qualEstimatedBudget').value) || null,
        urgency: document.getElementById('qualUrgency').value,
        decision_maker: document.getElementById('qualDecisionMaker').value,
        decision_timeline: document.getElementById('qualDecisionTimeline').value,
        payment_type: document.getElementById('qualPaymentType').value,
        score: parseInt(document.getElementById('qualScore').value) || null,
        qualification_notes: document.getElementById('qualNotes').value
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
            alert('Qualificação salva com sucesso!');
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
            alert('Interação criada com sucesso!');
            loadInteractions();
        } else {
            alert('Erro: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error creating interaction:', error);
        alert('Erro ao criar interação');
    }
}
