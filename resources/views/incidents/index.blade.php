@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
        {{ session('error') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Nouveau signalement</h2>
            
            <form id="incidentForm" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Titre</label>
                    <input type="text" name="title" id="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                    <input type="file" name="photo" id="photo" accept="image/*" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="fileSize" class="text-sm text-gray-500 mt-1"></p>
                </div>

                <div id="preview" class="mb-4 hidden">
                    <img id="imagePreview" src="" alt="Aperçu" class="w-full h-32 object-cover rounded-md">
                </div>

                <button type="submit" id="submitBtn" 
                        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition disabled:opacity-50">
                    Envoyer le signalement
                </button>
            </form>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="mb-6 flex gap-2">
            <div class="flex-1 relative">
                <input type="text" id="searchInput" placeholder="Rechercher par titre ou description..."
                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            
            <button id="viewAllBtn" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <span class="hidden sm:inline">Voir tous</span>
            </button>
        </div>

        <div id="resultsCount" class="mb-4 text-sm text-gray-600">
            {{ $incidents->count() }} incident(s) au total
        </div>

        <div id="incidentsList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($incidents as $incident)
                @include('incidents.partials.card', ['incident' => $incident])
            @endforeach
        </div>

        <div id="noResults" class="hidden text-center py-8 text-gray-500">
            Aucun signalement trouvé
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    .notification-toast {
        transition: opacity 0.5s, transform 0.5s;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
   const routes = {
    store: '{{ route("incidents.store") }}',
    search: '{{ route("incidents.search") }}',
    all: '/incidents/all', 
    status: '{{ route("incidents.status", ["incident" => ":id"]) }}'
};

    // Éléments DOM
    const photoInput = document.getElementById('photo');
    const fileSize = document.getElementById('fileSize');
    const preview = document.getElementById('preview');
    const imagePreview = document.getElementById('imagePreview');
    const incidentForm = document.getElementById('incidentForm');
    const submitBtn = document.getElementById('submitBtn');
    const searchInput = document.getElementById('searchInput');
    const viewAllBtn = document.getElementById('viewAllBtn');
    const incidentsList = document.getElementById('incidentsList');
    const noResults = document.getElementById('noResults');
    const resultsCount = document.getElementById('resultsCount');

    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            fileSize.textContent = `Taille: ${sizeInMB} Mo`;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    });

    incidentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';
        
        try {
            const response = await fetch(routes.store, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Le serveur a renvoyé une réponse invalide');
            }
            
            const data = await response.json();
            
            if (data.success) {
                const newCard = createIncidentCard(data.incident);
                incidentsList.insertAdjacentHTML('afterbegin', newCard);
                
                this.reset();
                fileSize.textContent = '';
                preview.classList.add('hidden');
                noResults.classList.add('hidden');
                
                updateResultsCount();
                
                showNotification('Signalement créé avec succès!', 'success');
            } else {
                showNotification(data.message || 'Erreur lors de la création', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur de connexion au serveur', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
  

    let searchTimeout;
    searchInput.addEventListener('keyup', function(e) {
        clearTimeout(searchTimeout);
        
        if (e.key === 'Escape') {
            this.value = '';
            viewAllIncidents();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(this.value);
        }, 300);
    });

    async function performSearch(query) {
        if (!query.trim()) {
            viewAllIncidents();
            return;
        }
        
        try {
            const response = await fetch(`${routes.search}?query=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse non-JSON reçue');
            }
            
            const incidents = await response.json();
            
            incidentsList.innerHTML = '';
            
            if (incidents.length === 0) {
                noResults.classList.remove('hidden');
                resultsCount.textContent = 'Aucun résultat trouvé';
            } else {
                noResults.classList.add('hidden');
                resultsCount.textContent = `${incidents.length} résultat(s) trouvé(s)`;
                
                incidents.forEach(incident => {
                    incidentsList.innerHTML += createIncidentCard(incident);
                });
            }
        } catch (error) {
            console.error('Search error:', error);
            showNotification('Erreur lors de la recherche', 'error');
        }
    }

    async function viewAllIncidents() {
        try {
            const originalContent = viewAllBtn.innerHTML;
            
            viewAllBtn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Chargement...';
            viewAllBtn.disabled = true;
            
            const response = await fetch(routes.all, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse non-JSON reçue');
            }
            
            const data = await response.json();
            
            if (data.success) {
                incidentsList.innerHTML = '';
                searchInput.value = '';
                
                if (data.incidents.length === 0) {
                    noResults.classList.remove('hidden');
                    resultsCount.textContent = 'Aucun incident';
                } else {
                    noResults.classList.add('hidden');
                    resultsCount.textContent = `${data.count} incident(s) au total`;
                    
                    data.incidents.forEach(incident => {
                        incidentsList.innerHTML += createIncidentCard(incident);
                    });
                }
                
                showNotification(`Liste rafraîchie (${data.count} incident(s))`, 'success');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showNotification('Erreur lors du chargement', 'error');
        } finally {
            viewAllBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg> Voir tous';
            viewAllBtn.disabled = false;
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('status-select')) {
            const select = e.target;
            const incidentId = select.dataset.incidentId;
            const newStatus = select.value;
            const card = select.closest('.bg-white');
            
            updateStatus(incidentId, newStatus, card);
        }
    });

    async function updateStatus(id, status, card) {
        try {
            const response = await fetch(routes.status.replace(':id', id), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse non-JSON reçue');
            }
            
            const data = await response.json();
            
            if (data.success) {
                card.className = card.className.replace(/border-\w+-500/g, '');
                card.classList.add(data.incident.border_class);
                
                const badge = card.querySelector('.px-2.py-1');
                badge.className = `px-2 py-1 text-xs font-semibold rounded-full ${data.incident.badge_class}`;
                badge.textContent = data.incident.status_label;
                
                showNotification('Statut mis à jour!', 'success');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('Erreur lors de la mise à jour', 'error');
        }
    }

    function createIncidentCard(incident) {
        const defaultImage = 'https://via.placeholder.com/300x200?text=Incident';
        
        return `
            <div class="bg-white rounded-lg shadow overflow-hidden border-4 ${incident.border_class || 'border-gray-300'}" data-id="${incident.id}">
                <div class="relative h-48 overflow-hidden bg-gray-100">
                    <img src="${incident.photo_url || defaultImage}" 
                         alt="${escapeHtml(incident.title)}" 
                         class="w-full h-full object-cover"
                         onerror="this.src='${defaultImage}'">
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2">${escapeHtml(incident.title)}</h3>
                    <p class="text-gray-600 text-sm mb-4">${escapeHtml(incident.description)}</p>
                    <div class="text-xs text-gray-400 mb-2">
                        ${incident.formatted_date || 'Date inconnue'}
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${incident.badge_class || 'bg-gray-100 text-gray-800'}">
                            ${incident.status_label || incident.status}
                        </span>
                        <select class="status-select text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                data-incident-id="${incident.id}">
                            <option value="open" ${incident.status === 'open' ? 'selected' : ''}>Ouvert</option>
                            <option value="in_progress" ${incident.status === 'in_progress' ? 'selected' : ''}>En cours</option>
                            <option value="resolved" ${incident.status === 'resolved' ? 'selected' : ''}>Résolu</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
    }

    function updateResultsCount() {
        const cards = document.querySelectorAll('#incidentsList > div');
        resultsCount.textContent = `${cards.length} incident(s) au total`;
    }

    function showNotification(message, type) {
        const oldNotifications = document.querySelectorAll('.notification-toast');
        oldNotifications.forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = `notification-toast fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

  
    viewAllBtn.addEventListener('click', viewAllIncidents);
    
    updateResultsCount();
});
</script>
@endpush