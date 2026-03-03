<?php


namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class IncidentController extends Controller
{
  
    public function index()
    {
        $incidents = Incident::latest()->get();
        return view('incidents.index', compact('incidents'));
    }

   
    public function getAll()
    {
        try {
            $incidents = Incident::latest()->get();
            
            return response()->json([
                'success' => true,
                'count' => $incidents->count(),
                'incidents' => $incidents->map(function($incident) {
                    return [
                        'id' => $incident->id,
                        'title' => $incident->title,
                        'description' => $incident->description,
                        'status' => $incident->status,
                        'photo_url' => $incident->photo_url,
                        'border_class' => $incident->border_class,
                        'badge_class' => $incident->badge_class,
                        'status_label' => $incident->status_label,
                        'formatted_date' => $incident->formatted_date
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur getAll: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des incidents'
            ], 500);
        }
    }

  
    public function store(Request $request)
    {
        try {
          
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            if (!Storage::disk('public')->exists('incidents')) {
                Storage::disk('public')->makeDirectory('incidents');
            }

          
            $fileName = time() . '_' . uniqid() . '.' . $request->file('photo')->extension();
          
            $photoPath = $request->file('photo')->storeAs('incidents', $fileName, 'public');

            if (!$photoPath) {
                throw new \Exception("Erreur lors de la sauvegarde de l'image");
            }

            $incident = Incident::create([
                'title' => $request->title,
                'description' => $request->description,
                'photo_path' => $photoPath,
                'status' => 'open'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Signalement créé avec succès!',
                'incident' => [
                    'id' => $incident->id,
                    'title' => $incident->title,
                    'description' => $incident->description,
                    'status' => $incident->status,
                    'photo_url' => $incident->photo_url,
                    'border_class' => $incident->border_class,
                    'badge_class' => $incident->badge_class,
                    'status_label' => $incident->status_label,
                    'formatted_date' => $incident->formatted_date
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Erreur validation: ' . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Erreur store: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], 500);
        }
    }

  
    public function search(Request $request)
    {
        try {
            $query = $request->get('query', '');
            
            $incidents = Incident::where('title', 'LIKE', "%{$query}%")
                ->orWhere('description', 'LIKE', "%{$query}%")
                ->latest()
                ->get();

            return response()->json($incidents->map(function($incident) {
                return [
                    'id' => $incident->id,
                    'title' => $incident->title,
                    'description' => $incident->description,
                    'status' => $incident->status,
                    'photo_url' => $incident->photo_url,
                    'border_class' => $incident->border_class,
                    'badge_class' => $incident->badge_class,
                    'status_label' => $incident->status_label,
                    'formatted_date' => $incident->formatted_date
                ];
            }));

        } catch (\Exception $e) {
            Log::error('Erreur search: ' . $e->getMessage());
            return response()->json(['error' => 'Erreur de recherche'], 500);
        }
    }

    
    public function updateStatus(Request $request, Incident $incident)
    {
        try {
            $request->validate([
                'status' => 'required|in:open,in_progress,resolved'
            ]);

            $incident->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'incident' => [
                    'id' => $incident->id,
                    'status' => $incident->status,
                    'border_class' => $incident->border_class,
                    'badge_class' => $incident->badge_class,
                    'status_label' => $incident->status_label
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur updateStatus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut'
            ], 500);
        }
    }
}