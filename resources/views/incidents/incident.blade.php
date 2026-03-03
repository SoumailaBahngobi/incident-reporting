@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Nouveau signalement</h2>
            
            <form id="incidentForm" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Titre</label>
                    <input type="text" name="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                    <input type="file" name="photo" id="photo" accept="image/*" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p id="fileSize" class="text-sm text-gray-500 mt-1"></p>
                </div>

                <button type="submit" 
                        class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 transition">
                    Envoyer le signalement
                </button>
            </form>
        </div>
    </div>

    
    <div class="lg:col-span-2">
        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="Rechercher par titre..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div id="incidentsList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($incidents as $incident)
                @include('incidents.partials.card', ['incident' => $incident])
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
        document.getElementById('fileSize').textContent = `Taille: ${sizeInMB} Mo`;
    }
});

document.getElementById('incidentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("incidents.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
           
            const incidentsList = document.getElementById('incidentsList');
            const newCard = createIncidentCard(data.incident);
            incidentsList.insertAdjacentHTML('afterbegin', newCard);
            
         
            this.reset();
            document.getElementById('fileSize').textContent = '';
            
            
            attachStatusEvents();
        }
    })
    .catch(error => console.error('Error:', error));
});

document.getElementById('searchInput').addEventListener('keyup', function() {
    const query = this.value;
    
    fetch(`{{ route("incidents.search") }}?query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(incidents => {
            const incidentsList = document.getElementById('incidentsList');
            incidentsList.innerHTML = '';
            
            incidents.forEach(incident => {
                incidentsList.innerHTML += createIncidentCard(incident);
            });
            
            attachStatusEvents();
        });
});

function createIncidentCard(incident) {
    const statusColors = {
        'open': 'border-red-500',
        'in_progress': 'border-orange-500',
        'resolved': 'border-green-500'
    };
    
    return `
        <div class="bg-white rounded-lg shadow overflow-hidden border-4 ${statusColors[incident.status]}">
            <img src="${incident.photo_url}" alt="Incident" class="w-full h-48 object-cover">
            <div class="p-4">
                <h3 class="font-semibold text-lg mb-2">${incident.title}</h3>
                <p class="text-gray-600 text-sm mb-4">${incident.description}</p>
                <div class="flex items-center justify-between">
                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                        ${incident.status === 'open' ? 'bg-red-100 text-red-800' : 
                          incident.status === 'in_progress' ? 'bg-orange-100 text-orange-800' : 
                          'bg-green-100 text-green-800'}">
                        ${incident.status === 'open' ? 'Ouvert' : 
                          incident.status === 'in_progress' ? 'En cours' : 'Résolu'}
                    </span>
                    <select onchange="updateStatus(${incident.id}, this.value)" 
                            class="text-sm border border-gray-300 rounded-md px-2 py-1">
                        <option value="open" ${incident.status === 'open' ? 'selected' : ''}>Ouvert</option>
                        <option value="in_progress" ${incident.status === 'in_progress' ? 'selected' : ''}>En cours</option>
                        <option value="resolved" ${incident.status === 'resolved' ? 'selected' : ''}>Résolu</option>
                    </select>
                </div>
            </div>
        </div>
    `;
}

function updateStatus(id, status) {
    fetch(`/incidents/${id}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = event.target.closest('.bg-white');
            const statusColors = {
                'open': 'border-red-500',
                'in_progress': 'border-orange-500',
                'resolved': 'border-green-500'
            };
            
            card.className = card.className.replace(/border-\w+-500/g, '');
            card.classList.add(statusColors[data.incident.status]);
            
            const badge = card.querySelector('.px-2.py-1');
            badge.className = badge.className.replace(/bg-\w+-100/g, '');
            badge.className = badge.className.replace(/text-\w+-800/g, '');
            
            if (data.incident.status === 'open') {
                badge.classList.add('bg-red-100', 'text-red-800');
                badge.textContent = 'Ouvert';
            } else if (data.incident.status === 'in_progress') {
                badge.classList.add('bg-orange-100', 'text-orange-800');
                badge.textContent = 'En cours';
            } else {
                badge.classList.add('bg-green-100', 'text-green-800');
                badge.textContent = 'Résolu';
            }
        }
    });
}

</script>
@endpush