<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'description', 
        'photo_path', 
        'status'
    ];

    protected $appends = ['photo_url', 'formatted_date', 'status_label'];

    
    protected static $statusLabels = [
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu'
    ];

    protected static $borderClasses = [
        'open' => 'border-red-500',
        'in_progress' => 'border-orange-500',
        'resolved' => 'border-green-500'
    ];

    protected static $badgeClasses = [
        'open' => 'bg-red-100 text-red-800',
        'in_progress' => 'bg-orange-100 text-orange-800',
        'resolved' => 'bg-green-100 text-green-800'
    ];

    
    public function getPhotoUrlAttribute()
    {
        if (empty($this->photo_path)) {
            return null;
        }

        if (!Storage::disk('public')->exists($this->photo_path)) {
            return null;
        }

        return asset('storage/' . $this->photo_path);
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : 'Date inconnue';
    }

  
    public function getStatusLabelAttribute()
    {
        return self::$statusLabels[$this->status] ?? $this->status;
    }

   
    public function getBorderClassAttribute()
    {
        return self::$borderClasses[$this->status] ?? 'border-gray-500';
    }

   
    public function getBadgeClassAttribute()
    {
        return self::$badgeClasses[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}