<!-- resources/views/incidents/partials/card.blade.php -->
<div class="bg-white rounded-lg shadow overflow-hidden border-4 
    @if($incident->status === 'open') border-red-500
    @elseif($incident->status === 'in_progress') border-orange-500
    @else border-green-500 @endif" 
    data-id="{{ $incident->id }}">
    
    <img src="{{ $incident->photo_url }}" alt="Incident" class="w-full h-48 object-cover">
    
    <div class="p-4">
        <h3 class="font-semibold text-lg mb-2">{{ $incident->title }}</h3>
        <p class="text-gray-600 text-sm mb-4">{{ $incident->description }}</p>
        
        <div class="flex items-center justify-between">
            <span class="px-2 py-1 text-xs font-semibold rounded-full 
                @if($incident->status === 'open') bg-red-100 text-red-800
                @elseif($incident->status === 'in_progress') bg-orange-100 text-orange-800
                @else bg-green-100 text-green-800 @endif">
                @if($incident->status === 'open')
                    Ouvert
                @elseif($incident->status === 'in_progress')
                    En cours
                @else
                    Résolu
                @endif
            </span>
            
            <select class="status-select text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-incident-id="{{ $incident->id }}">
                <option value="open" {{ $incident->status === 'open' ? 'selected' : '' }}>Ouvert</option>
                <option value="in_progress" {{ $incident->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                <option value="resolved" {{ $incident->status === 'resolved' ? 'selected' : '' }}>Résolu</option>
            </select>
        </div>
    </div>
</div>